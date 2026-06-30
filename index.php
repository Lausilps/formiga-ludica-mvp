<?php

require_once 'config/conexao.php';

$sql = "SELECT
            id_jogo,
            nome,
            imagem,
            descricao,
            preco,
            min_jogadores,
            max_jogadores,
            idade_minima,
            duracao_minutos,
            dificuldade
        FROM jogos
        WHERE ativo = 1
        ORDER BY nome ASC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao carregar catálogo.");
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Formiga Lúdica - Catálogo</title>
    <link rel="stylesheet" href="assets/css/catalogo.css">
</head>
<body>

    <header class="catalogo-topo">
        <div class="info-topo">

            <img
                src="assets/img/logo_formiga_ludica.png"
                alt="Formiga Lúdica"
                class="logo-topo">

            <div class="texto-topo">
                <span class="titulo-catalogo">CATÁLOGO</span>

                <p>Encontre o jogo perfeito para sua próxima jogatina!</p>
            </div>

        </div>

        <div class="box-recomendacao">

            <img
                src="assets/img/formiguinha-rag.gif"
                alt="Formiguinha recomendando"
                class="gif-recomendacao">

            <div class="texto-recomendacao">
                <span>Não sabe o que jogar hoje?</span>

                <a href="recomendacao_form.php" class="btn-recomendar">
                    ✨ Recomendar para mim
                </a>
            </div>

        </div>
    </header>

    <section class="filtros-catalogo">
        <input type="text" id="busca-jogo" placeholder="Buscar jogos...">

        <div class="grupo-filtro">
            <strong>Idade</strong>

            <div class="opcoes-filtro">
                <label><input type="checkbox" class="filtro-idade" value="8"> 8+</label>
                <label><input type="checkbox" class="filtro-idade" value="10"> 10+</label>
                <label><input type="checkbox" class="filtro-idade" value="12"> 12+</label>
                <label><input type="checkbox" class="filtro-idade" value="14"> 14+</label>
            </div>
        </div>

        <div class="grupo-filtro">
            <strong>Jogadores</strong>

            <div class="opcoes-filtro">
                <label><input type="checkbox" class="filtro-jogadores" value="2"> 2</label>
                <label><input type="checkbox" class="filtro-jogadores" value="4"> 4</label>
                <label><input type="checkbox" class="filtro-jogadores" value="6"> 6</label>
                <label><input type="checkbox" class="filtro-jogadores" value="8"> 8+</label>
            </div>
        </div>

        <div class="grupo-filtro">
            <strong>Tempo</strong>

            <div class="opcoes-filtro">
                <label><input type="checkbox" class="filtro-tempo" data-min="0" data-max="30"> até 30 min</label>
                <label><input type="checkbox" class="filtro-tempo" data-min="31" data-max="60"> 30-60 min</label>
                <label><input type="checkbox" class="filtro-tempo" data-min="61" data-max="999"> 60+ min</label>
            </div>
        </div>

        <button type="button" id="limpar-filtros">Limpar filtros</button>
    </section>

    <main class="grid-jogos">

        <?php while ($jogo = mysqli_fetch_assoc($resultado)): ?>

            <?php
                $imagem = $jogo['imagem'];

                if (!empty($imagem) && str_starts_with($imagem, 'http')) {
                    $srcImagem = $imagem;
                } elseif (!empty($imagem)) {
                    $srcImagem = $imagem;
                } else {
                    $srcImagem = 'assets/img/sem-imagem.png';
                }
            ?>

            <article
                class="card-jogo"
                data-nome="<?= htmlspecialchars($jogo['nome']) ?>"
                data-nome-busca="<?= strtolower($jogo['nome']) ?>"
                data-descricao="<?= htmlspecialchars($jogo['descricao'] ?? '') ?>"
                data-imagem="<?= $srcImagem ?>"
                data-preco="<?= $jogo['preco'] ?>"
                data-min="<?= $jogo['min_jogadores'] ?>"
                data-max="<?= $jogo['max_jogadores'] ?>"
                data-idade="<?= $jogo['idade_minima'] ?>"
                data-tempo="<?= $jogo['duracao_minutos'] ?>"
            >

                <div class="imagem-card">
                    <img src="<?= $srcImagem ?>" alt="<?= $jogo['nome'] ?>">
                </div>

                <div class="conteudo-card">
                    <h2><?= $jogo['nome'] ?></h2>

                    <p class="descricao-card">
                        <?= mb_strimwidth($jogo['descricao'] ?? '', 0, 110, '...') ?>
                    </p>

                    <div class="infos-card">
                        <span>👥 <?= $jogo['min_jogadores'] ?> - <?= $jogo['max_jogadores'] ?></span>
                        <span>⏱ <?= $jogo['duracao_minutos'] ?> min</span>
                        <span>👤 <?= $jogo['idade_minima'] ?>+</span>
                    </div>

                    <div class="rodape-card">
                        <strong>R$ <?= number_format($jogo['preco'], 2, ',', '.') ?></strong>
                        <button type="button" class="btn-escolher" data-nome="<?= $jogo['nome'] ?>">
                            Escolher
                        </button>
                    </div>
                </div>

            </article>

        <?php endwhile; ?>

    </main>

    <div class="modal" id="modal-jogo">
        <div class="modal-conteudo">
            <button type="button" class="fechar-modal" id="fechar-modal-jogo">×</button>

            <img id="modal-imagem" src="" alt="">

            <div>
                <h2 id="modal-nome"></h2>
                <p id="modal-descricao"></p>

                <div class="modal-infos">
                    <span id="modal-jogadores"></span>
                    <span id="modal-tempo"></span>
                    <span id="modal-idade"></span>
                    <strong id="modal-preco"></strong>
                </div>

                <button type="button" id="modal-selecionar" class="btn-escolher">
                    Selecionar jogo
                </button>
            </div>
        </div>
    </div>

    <div class="barra-whatsapp" id="barra-whatsapp" style="display:none;">
        <span id="qtd-selecionados">0 jogos selecionados</span>

        <button type="button" id="abrir-modal-pedido">
            Enviar pedido
        </button>
    </div>

    <div class="modal" id="modal-pedido">
        <div class="modal-conteudo">
            <button type="button" class="fechar-modal" id="fechar-modal-pedido">×</button>

            <h2>Conferir pedido</h2>
            <p>Confira os jogos selecionados antes de enviar pelo WhatsApp.</p>

            <div id="lista-pedido"></div>

            <strong id="total-pedido"></strong>

            <div class="acoes-modal">
                <button type="button" id="continuar-escolhendo">Adicionar mais jogos</button>
                <button type="button" id="confirmar-whatsapp">Enviar para WhatsApp</button>
            </div>
        </div>
    </div>

    <script>
    const busca = document.getElementById('busca-jogo');
    const cards = document.querySelectorAll('.card-jogo');

    const selecionados = [];
    const barra = document.getElementById('barra-whatsapp');
    const qtdSelecionados = document.getElementById('qtd-selecionados');

    const modalJogo = document.getElementById('modal-jogo');
    const modalPedido = document.getElementById('modal-pedido');

    const modalImagem = document.getElementById('modal-imagem');
    const modalNome = document.getElementById('modal-nome');
    const modalDescricao = document.getElementById('modal-descricao');
    const modalJogadores = document.getElementById('modal-jogadores');
    const modalTempo = document.getElementById('modal-tempo');
    const modalIdade = document.getElementById('modal-idade');
    const modalPreco = document.getElementById('modal-preco');
    const modalSelecionar = document.getElementById('modal-selecionar');

    let jogoModalAtual = null;

    function pegarSelecionados(classe) {
        return Array.from(document.querySelectorAll(classe + ':checked'))
            .map(input => Number(input.value));
    }

    function aplicarFiltros() {
        const termo = busca.value.toLowerCase();

        const idades = pegarSelecionados('.filtro-idade');
        const jogadores = pegarSelecionados('.filtro-jogadores');

        const tempos = Array.from(document.querySelectorAll('.filtro-tempo:checked'))
            .map(input => ({
                min: Number(input.dataset.min),
                max: Number(input.dataset.max)
            }));

        cards.forEach(card => {
            const nome = card.dataset.nomeBusca;
            const idade = Number(card.dataset.idade);
            const min = Number(card.dataset.min);
            const max = Number(card.dataset.max);
            const tempo = Number(card.dataset.tempo);

            const passaNome = nome.includes(termo);

            const passaIdade =
                idades.length === 0 ||
                idades.includes(idade);

            const passaJogadores =
                jogadores.length === 0 ||
                jogadores.some(qtd => min <= qtd && max >= qtd);

            const passaTempo =
                tempos.length === 0 ||
                tempos.some(intervalo => tempo >= intervalo.min && tempo <= intervalo.max);

            card.style.display =
                passaNome && passaIdade && passaJogadores && passaTempo
                    ? 'block'
                    : 'none';
        });
    }

    function formatarPreco(valor) {
        return Number(valor).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function atualizarBarraPedido() {
        if (selecionados.length > 0) {
            barra.style.display = 'flex';
            qtdSelecionados.textContent = selecionados.length + ' jogo(s) selecionado(s)';
        } else {
            barra.style.display = 'none';
        }
    }

    function atualizarBotoesSelecionados() {
        document.querySelectorAll('.btn-escolher').forEach(botao => {
            const nome = botao.dataset.nome;

            if (selecionados.some(jogo => jogo.nome === nome)) {
                botao.textContent = 'Selecionado';
                botao.classList.add('selecionado');
            } else {
                botao.textContent = 'Escolher';
                botao.classList.remove('selecionado');
            }
        });
    }

    function alternarJogoSelecionado(jogo) {
        const index = selecionados.findIndex(item => item.nome === jogo.nome);

        if (index === -1) {
            selecionados.push(jogo);
        } else {
            selecionados.splice(index, 1);
        }

        atualizarBarraPedido();
        atualizarBotoesSelecionados();

        if (jogoModalAtual && jogoModalAtual.nome === jogo.nome) {
            atualizarBotaoModal();
        }
    }

    function atualizarBotaoModal() {
        if (!jogoModalAtual) return;

        const jaSelecionado = selecionados.some(jogo => jogo.nome === jogoModalAtual.nome);

        modalSelecionar.textContent = jaSelecionado
            ? 'Remover da seleção'
            : 'Selecionar jogo';
    }

    function abrirModalJogo(card) {
        jogoModalAtual = {
            nome: card.dataset.nome,
            descricao: card.dataset.descricao,
            imagem: card.dataset.imagem,
            preco: card.dataset.preco,
            min: card.dataset.min,
            max: card.dataset.max,
            idade: card.dataset.idade,
            tempo: card.dataset.tempo
        };

        modalImagem.src = jogoModalAtual.imagem;
        modalImagem.alt = jogoModalAtual.nome;

        modalNome.textContent = jogoModalAtual.nome;
        modalDescricao.textContent = jogoModalAtual.descricao;

        modalJogadores.textContent = `👥 ${jogoModalAtual.min} - ${jogoModalAtual.max} jogadores`;
        modalTempo.textContent = `⏱ ${jogoModalAtual.tempo} min`;
        modalIdade.textContent = `👤 ${jogoModalAtual.idade}+`;
        modalPreco.textContent = formatarPreco(jogoModalAtual.preco);

        atualizarBotaoModal();

        modalJogo.classList.add('ativo');
    }

    function fecharModais() {
        modalJogo.classList.remove('ativo');
        modalPedido.classList.remove('ativo');
    }

    function montarListaPedido() {
        const listaPedido = document.getElementById('lista-pedido');
        const totalPedido = document.getElementById('total-pedido');

        listaPedido.innerHTML = '';

        let total = 0;

        selecionados.forEach((jogo, index) => {
            total += Number(jogo.preco);

            const item = document.createElement('div');
            item.classList.add('item-pedido');

            item.innerHTML = `
                <span>${jogo.nome} - ${formatarPreco(jogo.preco)}</span>
                <button type="button" data-index="${index}">Remover</button>
            `;

            listaPedido.appendChild(item);
        });

        totalPedido.textContent = `Total estimado: ${formatarPreco(total)}`;

        document.querySelectorAll('#lista-pedido button').forEach(botao => {
            botao.addEventListener('click', function () {
                const index = Number(this.dataset.index);

                selecionados.splice(index, 1);

                montarListaPedido();
                atualizarBarraPedido();
                atualizarBotoesSelecionados();

                if (selecionados.length === 0) {
                    modalPedido.classList.remove('ativo');
                }
            });
        });
    }

    busca.addEventListener('input', aplicarFiltros);

    document.querySelectorAll('.filtro-idade, .filtro-jogadores, .filtro-tempo').forEach(input => {
        input.addEventListener('change', aplicarFiltros);
    });

    document.getElementById('limpar-filtros').addEventListener('click', function () {
        busca.value = '';

        document.querySelectorAll('.filtro-idade, .filtro-jogadores, .filtro-tempo').forEach(input => {
            input.checked = false;
        });

        aplicarFiltros();
    });

    cards.forEach(card => {
        card.addEventListener('click', function (event) {
            if (event.target.classList.contains('btn-escolher')) return;

            abrirModalJogo(card);
        });
    });

    document.querySelectorAll('.btn-escolher').forEach(botao => {
        botao.addEventListener('click', function (event) {
            event.stopPropagation();

            const card = this.closest('.card-jogo');

            const jogo = {
                nome: card.dataset.nome,
                preco: card.dataset.preco
            };

            alternarJogoSelecionado(jogo);
        });
    });

    modalSelecionar.addEventListener('click', function () {
        if (!jogoModalAtual) return;

        alternarJogoSelecionado({
            nome: jogoModalAtual.nome,
            preco: jogoModalAtual.preco
        });
    });

    document.getElementById('fechar-modal-jogo').addEventListener('click', fecharModais);
    document.getElementById('fechar-modal-pedido').addEventListener('click', fecharModais);

    document.getElementById('abrir-modal-pedido').addEventListener('click', function () {
        montarListaPedido();
        modalPedido.classList.add('ativo');
    });

    document.getElementById('continuar-escolhendo').addEventListener('click', function () {
        modalPedido.classList.remove('ativo');
    });

    document.getElementById('confirmar-whatsapp').addEventListener('click', function () {
        const total = selecionados.reduce((soma, jogo) => soma + Number(jogo.preco), 0);

        const mensagem = `Olá! Tenho interesse em alugar os jogos:\n\n- ${selecionados.map(jogo => jogo.nome).join('\n- ')}\n\nTotal estimado: ${formatarPreco(total)}\n\nPode me passar disponibilidade?`;

        const telefone = '5537999139354';
        const url = `https://wa.me/${telefone}?text=${encodeURIComponent(mensagem)}`;

        window.open(url, '_blank');
    });

    modalJogo.addEventListener('click', function (event) {
        if (event.target === modalJogo) fecharModais();
    });

    modalPedido.addEventListener('click', function (event) {
        if (event.target === modalPedido) fecharModais();
    });
    </script>

    <a href="https://wa.me/5537999139354"
    target="_blank"
    class="whatsapp-flutuante">

        <img
            src="assets/img/formiga-whatsapp.gif"
            alt="Chamar no WhatsApp">

    </a>

    <div class="modal" id="modal-pedido">
        <div class="modal-conteudo">
            <button type="button" class="fechar-modal" id="fechar-modal-pedido">×</button>

            <h2>Conferir pedido</h2>
            <p>Confira os jogos selecionados antes de enviar pelo WhatsApp.</p>

            <div id="lista-pedido"></div>

            <strong id="total-pedido"></strong>

            <div class="acoes-modal">
                <button type="button" id="continuar-escolhendo">Adicionar mais jogos</button>
                <button type="button" id="confirmar-whatsapp">Enviar para WhatsApp</button>
            </div>
        </div>
    </div>

</body>
</html>