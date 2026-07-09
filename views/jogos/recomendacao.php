<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recomendações - Formiga Lúdica</title>
    <link rel="stylesheet" href="../assets/css/catalogo.css">
    <link rel="stylesheet" href="../assets/css/recomendacao_resultado.css">
    <script src="../assets/js/carrinho.js"></script>
</head>
<body>

<header class="catalogo-topo">
    <div class="info-topo">
        <img src="../assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="logo-topo">
        <div class="texto-topo">
            <span class="titulo-catalogo">RECOMENDAÇÕES</span>
            <p>A Formiguinha escolheu especialmente pra você 🐜✨</p>
        </div>
    </div>
</header>

<?php if (!empty($recomendacoes)): ?>

    <section class="fala-formiguinha">
        <img src="../assets/img/formiguinha-falando.png" alt="Formiguinha" class="formiguinha-fala">

        <div class="balao-fala">
            <h2>🐜 E aí, formigão!</h2>
            <p><?= htmlspecialchars($intro) ?></p>
        </div>
    </section>

    <?php if (!empty($recomendacoes)): ?>

    <div id="container-recomendacoes">
        <div class="recomendacoes-grid" id="grid-recomendacoes">
            <?php foreach ($recomendacoes as $jogo): ?>
                <div class="card-recomendacao" data-id="<?= $jogo['id'] ?>" data-nome="<?= htmlspecialchars($jogo['nome']) ?>" data-preco="<?= $jogo['preco'] ?>">
                    <?php
                        $imgSrc = !empty($jogo['imagem'])
                            ? htmlspecialchars($jogo['imagem'])
                            : '../assets/img/sem-imagem.png';
                    ?>

                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($jogo['nome']) ?>">

                    <div class="card-body">
                        <h3><?= htmlspecialchars($jogo['nome']) ?></h3>

                        <p class="card-motivo">
                            <?= htmlspecialchars($jogo['motivo']) ?>
                        </p>

                        <p class="card-meta">
                            👥 <?= $jogo['min_jogadores'] ?>–<?= $jogo['max_jogadores'] ?> jogadores &nbsp;|&nbsp;
                            ⏱ <?= $jogo['duracao'] ?> min &nbsp;|&nbsp;
                            🎯 <?= ucfirst($jogo['dificuldade']) ?>
                        </p>

                        <div class="rodape-card">
                            <span class="card-preco">R$ <?= number_format($jogo['preco'], 2, ',', '.') ?>/dia</span>
                            <button type="button" class="btn-escolher" data-nome="<?= htmlspecialchars($jogo['nome']) ?>">Escolher</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <div class="acoes-mais-recomendacoes">
            <button id="btn-mais-recomendacoes" class="btn-mais">
                🐜 + Recomendações
            </button>
            <p id="msg-fim-catalogo" style="display:none; color:#777; margin-top:12px;">
                🐜 A Formiguinha já trouxe tudo que tinha no catálogo pra esse perfil!
                Se não gostou de nenhuma, fala com o <strong>Jander</strong> que ele te ajuda pessoalmente. 💛
            </p>
        </div>

        <!-- dados ocultos para a busca incremental -->
        <input type="hidden" id="dados-descricao" value="<?= htmlspecialchars($queryDescricao ?? '') ?>">
        <input type="hidden" id="dados-jogadores" value="<?= htmlspecialchars($jogadores ?? '') ?>">
        <input type="hidden" id="dados-idade" value="<?= htmlspecialchars($idade ?? '') ?>">
        <input type="hidden" id="dados-tempo" value="<?= htmlspecialchars($tempo ?? '') ?>">
    </div>

    <!-- Modal do pedido -->
    <div class="modal" id="modal-pedido">
        <div class="modal-conteudo">
            <button type="button" class="fechar-modal" id="fechar-modal-pedido">×</button>
            <h2>Conferir pedido</h2>
            <p>Confira os jogos selecionados antes de enviar pelo WhatsApp.</p>
            <div id="lista-pedido"></div>
            <strong id="total-pedido"></strong>
            <div class="acoes-modal">
                <button type="button" id="continuar-escolhendo">Continuar escolhendo</button>
                <button type="button" id="confirmar-whatsapp">Enviar para WhatsApp</button>
            </div>
        </div>
    </div>

    <div class="barra-whatsapp" id="barra-whatsapp" style="display:none;">
        <span id="qtd-selecionados">0 jogos selecionados</span>
        <button type="button" id="abrir-modal-pedido">Enviar pedido</button>
    </div>

    <script>
    document.getElementById('btn-mais-recomendacoes').addEventListener('click', function() {
        const btn = this;
        const msgFim = document.getElementById('msg-fim-catalogo');
        const grid = document.getElementById('grid-recomendacoes');

        // Pega os IDs já exibidos na tela
        const idsExibidos = Array.from(document.querySelectorAll('.card-recomendacao'))
            .map(card => card.dataset.id);

        btn.disabled = true;
        btn.textContent = '🐜 Procurando mais opções...';

        const formData = new FormData();
        formData.append('descricao_sessao', document.getElementById('dados-descricao').value);
        formData.append('jogadores', document.getElementById('dados-jogadores').value);
        formData.append('idade', document.getElementById('dados-idade').value);
        formData.append('tempo', document.getElementById('dados-tempo').value);
        idsExibidos.forEach(id => formData.append('ids_exibidos[]', id));

        fetch('maisRecomendacoesController.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = '🐜 + Recomendações';

            if (data.fim) {
                btn.style.display = 'none';
                msgFim.style.display = 'block';
                return;
            }

            if (data.erro === 'limite_excedido') {
                alert('A Formiguinha está bombando de pedidos agora! Tenta de novo em alguns segundos.');
                return;
            }

            if (data.erro) {
                alert('Ops, algo deu errado. Tenta novamente!');
                return;
            }

            // Adiciona os novos cards no grid
            data.recomendacoes.forEach(jogo => {
                const imgSrc = jogo.imagem ? jogo.imagem : '../assets/img/sem-imagem.png';
                const card = document.createElement('div');
                card.className = 'card-recomendacao';
                card.dataset.id = jogo.id;
                card.dataset.nome = jogo.nome;
                card.dataset.preco = jogo.preco;
                card.innerHTML = `
                    <img src="${imgSrc}" alt="${jogo.nome}">
                    <div class="card-body">
                        <h3>${jogo.nome}</h3>
                        <p class="card-motivo">${jogo.motivo}</p>
                        <p class="card-meta">
                            👥 ${jogo.min_jogadores}–${jogo.max_jogadores} jogadores &nbsp;|&nbsp;
                            ⏱ ${jogo.duracao} min &nbsp;|&nbsp;
                            🎯 ${jogo.dificuldade.charAt(0).toUpperCase() + jogo.dificuldade.slice(1)}
                        </p>
                        <div class="rodape-card">
                            <span class="card-preco">R$ ${parseFloat(jogo.preco).toFixed(2).replace('.', ',')}/dia</span>
                            <button type="button" class="btn-escolher" data-nome="${jogo.nome}">Escolher</button>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
            atualizarBotoesSelecionados();
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = '🐜 + Recomendações';
            alert('Erro ao buscar mais recomendações. Tenta novamente!');
        });
    });

    // ============================================================
    // CARRINHO / SELEÇÃO DE JOGOS PARA O WHATSAPP
    // ============================================================
    const selecionados = Carrinho.obter();

    const barra          = document.getElementById('barra-whatsapp');
    const qtdSelecionados = document.getElementById('qtd-selecionados');
    const modalPedido     = document.getElementById('modal-pedido');
    const gridRecomendacoes = document.getElementById('grid-recomendacoes');

    function formatarPreco(valor) {
        return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
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
        document.querySelectorAll('#grid-recomendacoes .btn-escolher').forEach(botao => {
            const nome = botao.dataset.nome;
            if (selecionados.some(j => j.nome === nome)) {
                botao.textContent = 'Selecionado';
                botao.classList.add('selecionado');
            } else {
                botao.textContent = 'Escolher';
                botao.classList.remove('selecionado');
            }
        });
    }

    function alternarJogoSelecionado(jogo) {
        const index = selecionados.findIndex(i => i.nome === jogo.nome);
        if (index === -1) {
            selecionados.push(jogo);
        } else {
            selecionados.splice(index, 1);
        }
        Carrinho.salvar(selecionados);
        atualizarBarraPedido();
        atualizarBotoesSelecionados();
    }

    // Delegação de evento: cobre os cards já renderizados e os que forem adicionados depois
    gridRecomendacoes.addEventListener('click', function(e) {
        const botao = e.target.closest('.btn-escolher');
        if (!botao) return;
        const card = botao.closest('.card-recomendacao');
        alternarJogoSelecionado({ nome: card.dataset.nome, preco: card.dataset.preco });
    });

    function montarListaPedido() {
        const listaPedido = document.getElementById('lista-pedido');
        const totalPedido = document.getElementById('total-pedido');
        listaPedido.innerHTML = '';
        let total = 0;

        selecionados.forEach((jogo, index) => {
            total += Number(jogo.preco);
            const item = document.createElement('div');
            item.className = 'item-pedido';
            item.innerHTML = `
                <span>${jogo.nome} - ${formatarPreco(jogo.preco)}</span>
                <button type="button" data-index="${index}">Remover</button>
            `;
            listaPedido.appendChild(item);
        });

        totalPedido.textContent = `Total estimado: ${formatarPreco(total)}`;

        document.querySelectorAll('#lista-pedido button').forEach(botao => {
            botao.addEventListener('click', function() {
                selecionados.splice(Number(this.dataset.index), 1);
                Carrinho.salvar(selecionados);
                montarListaPedido();
                atualizarBarraPedido();
                atualizarBotoesSelecionados();
                if (selecionados.length === 0) modalPedido.classList.remove('ativo');
            });
        });
    }

    document.getElementById('abrir-modal-pedido').addEventListener('click', () => {
        montarListaPedido();
        modalPedido.classList.add('ativo');
    });

    document.getElementById('fechar-modal-pedido').addEventListener('click', () => {
        modalPedido.classList.remove('ativo');
    });

    document.getElementById('continuar-escolhendo').addEventListener('click', () => {
        modalPedido.classList.remove('ativo');
    });

    modalPedido.addEventListener('click', e => {
        if (e.target === modalPedido) modalPedido.classList.remove('ativo');
    });

    document.getElementById('confirmar-whatsapp').addEventListener('click', () => {
        const total = selecionados.reduce((soma, j) => soma + Number(j.preco), 0);
        const mensagem = `Olá! Tenho interesse em alugar os jogos:\n\n- ${selecionados.map(j => j.nome).join('\n- ')}\n\nTotal estimado: ${formatarPreco(total)}\n\nPode me passar disponibilidade?`;
        window.open(`https://wa.me/5537999139354?text=${encodeURIComponent(mensagem)}`, '_blank');
    });

    atualizarBarraPedido();
    atualizarBotoesSelecionados();
    </script>

    <?php endif; ?>

<?php else: ?>
    <div class="sem-resultado">
        <p>😕 Não encontrei recomendações certinhas pra esse perfil.</p>
        <p>Tenta ajustar o número de jogadores ou o tempo disponível!</p>
    </div>
<?php endif; ?>

<div class="acoes-finais">
    <a class="btn-voltar" href="../recomendacao_form.php">← Tentar outra busca</a>
    <a class="btn-voltar" href="../index.php">← Voltar ao catálogo</a>
</div>

</body>
</html>