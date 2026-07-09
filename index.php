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
        <img src="assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="logo-topo">
        <div class="texto-topo">
            <span class="titulo-catalogo">CATÁLOGO</span>
            <p>Encontre o jogo perfeito para sua próxima jogatina!</p>
        </div>
    </div>
    <div class="box-recomendacao">
        <img src="assets/img/formiguinha-rag.gif" alt="Formiguinha recomendando" class="gif-recomendacao">
        <div class="texto-recomendacao">
            <span>Não sabe o que jogar hoje?</span>
            <a href="recomendacao_form.php" class="btn-recomendar">✨ Recomendar para mim</a>
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
            <label><input type="checkbox" class="filtro-tempo" value="0-30"> até 30 min</label>
            <label><input type="checkbox" class="filtro-tempo" value="31-60"> 30-60 min</label>
            <label><input type="checkbox" class="filtro-tempo" value="61-999"> 60+ min</label>
        </div>
    </div>

    <button type="button" id="limpar-filtros">Limpar filtros</button>
</section>

<main class="grid-jogos" id="grid-jogos">
    <div id="loading-inicial" style="grid-column:1/-1; text-align:center; padding:40px; color:#666;">
        🐜 Carregando jogos...
    </div>
</main>

<div id="sentinel" style="height:1px;"></div>

<div id="loading-mais" style="display:none; text-align:center; padding:20px; color:#666;">
    🐜 Carregando mais jogos...
</div>

<!-- Modal do jogo -->
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
            <div id="modal-ludopedia" style="display:none; margin-top:12px;">
                <a id="modal-link-ludopedia" href="#" target="_blank" class="btn-ludopedia">
                    <img src="assets/img/logo-ludopedia.png" alt="Ludopedia">
                    Ver na Ludopedia
                </a>
            </div>
            <br>
            <button type="button" id="modal-selecionar" class="btn-escolher">Selecionar jogo</button>
        </div>
    </div>
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
            <button type="button" id="continuar-escolhendo">Adicionar mais jogos</button>
            <button type="button" id="confirmar-whatsapp">Enviar para WhatsApp</button>
        </div>
    </div>
</div>

<div class="barra-whatsapp" id="barra-whatsapp" style="display:none;">
    <span id="qtd-selecionados">0 jogos selecionados</span>
    <button type="button" id="abrir-modal-pedido">Enviar pedido</button>
</div>

<a href="https://wa.me/5537999139354" target="_blank" class="whatsapp-flutuante">
    <img src="assets/img/formiga-whatsapp.gif" alt="Chamar no WhatsApp">
</a>

<script>
// ============================================================
// ESTADO GLOBAL
// ============================================================
let offset        = 0;
let carregando    = false;
let temMais       = true;
let filtroAtivo   = false;
let debounceTimer = null;

const selecionados = [];

// ============================================================
// ELEMENTOS
// ============================================================
const grid           = document.getElementById('grid-jogos');
const sentinel       = document.getElementById('sentinel');
const loadingMais    = document.getElementById('loading-mais');
const barra          = document.getElementById('barra-whatsapp');
const qtdSelecionados = document.getElementById('qtd-selecionados');
const modalJogo      = document.getElementById('modal-jogo');
const modalPedido    = document.getElementById('modal-pedido');
const modalImagem    = document.getElementById('modal-imagem');
const modalNome      = document.getElementById('modal-nome');
const modalDescricao = document.getElementById('modal-descricao');
const modalJogadores = document.getElementById('modal-jogadores');
const modalTempo     = document.getElementById('modal-tempo');
const modalIdade     = document.getElementById('modal-idade');
const modalPreco     = document.getElementById('modal-preco');
const modalSelecionar = document.getElementById('modal-selecionar');
const modalLudopedia  = document.getElementById('modal-ludopedia');
const modalLinkLudo   = document.getElementById('modal-link-ludopedia');

let jogoModalAtual = null;

// ============================================================
// FUNÇÕES DE FILTRO
// ============================================================
function pegarFiltros() {
    const busca = document.getElementById('busca-jogo').value.trim();

    const idades = Array.from(document.querySelectorAll('.filtro-idade:checked'))
        .map(i => i.value);

    const jogadores = Array.from(document.querySelectorAll('.filtro-jogadores:checked'))
        .map(i => i.value);

    const tempos = Array.from(document.querySelectorAll('.filtro-tempo:checked'))
        .map(i => i.value);

    return { busca, idades, jogadores, tempos };
}

function temFiltroAtivo() {
    const { busca, idades, jogadores, tempos } = pegarFiltros();
    return busca !== '' || idades.length > 0 || jogadores.length > 0 || tempos.length > 0;
}

// ============================================================
// CRIAR CARD HTML
// ============================================================
function criarCard(jogo) {
    const descricaoCurta = jogo.descricao.length > 110
        ? jogo.descricao.substring(0, 110) + '...'
        : jogo.descricao;

    const preco = Number(jogo.preco).toLocaleString('pt-BR', {
        style: 'currency', currency: 'BRL'
    });

    const badgeLudo = jogo.link_ludopedia
    ? `<a href="https://ludopedia.com.br" target="_blank" class="badge-ludopedia" title="Ver na Ludopedia">
           <img src="assets/img/logo-ludopedia.png" alt="Ludopedia">
           <span>
               <span class="badge-via">integrado via</span>
               <span class="badge-nome">Ludopedia</span>
           </span>
       </a>`
    : '';

    const article = document.createElement('article');
    article.className = 'card-jogo';
    article.dataset.id       = jogo.id;
    article.dataset.nome     = jogo.nome;
    article.dataset.descricao = jogo.descricao;
    article.dataset.imagem   = jogo.imagem;
    article.dataset.preco    = jogo.preco;
    article.dataset.min      = jogo.min_jogadores;
    article.dataset.max      = jogo.max_jogadores;
    article.dataset.idade    = jogo.idade_minima;
    article.dataset.tempo    = jogo.duracao;
    article.dataset.linkLudo = jogo.link_ludopedia ?? '';

    article.innerHTML = `
        <div class="imagem-card">
            <img src="${jogo.imagem}" alt="${jogo.nome}"
                 onerror="this.src='assets/img/sem-imagem.png'">
        </div>
        <div class="conteudo-card">
            <h2>${jogo.nome}</h2>
            <p class="descricao-card">${descricaoCurta}</p>
            <div class="infos-card">
                <span>👥 ${jogo.min_jogadores} - ${jogo.max_jogadores}</span>
                <span>⏱ ${jogo.duracao} min</span>
                <span>👤 ${jogo.idade_minima}+</span>
                ${badgeLudo}
            </div>
            <div class="rodape-card">
                <strong>${preco}</strong>
                <button type="button" class="btn-escolher" data-nome="${jogo.nome}">Escolher</button>
            </div>
        </div>
    `;

    // Abre modal ao clicar no card
    article.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-escolher')) return;
        abrirModalJogo(article);
    });

    // Botão escolher
    article.querySelector('.btn-escolher').addEventListener('click', function(e) {
        e.stopPropagation();
        alternarJogoSelecionado({ nome: jogo.nome, preco: jogo.preco });
    });

    return article;
}

// ============================================================
// CARREGAR JOGOS
// ============================================================
async function carregarJogos(resetar = false) {
    if (carregando) return;
    carregando = true;

    if (resetar) {
        offset = 0;
        temMais = true;
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#666;">🐜 Carregando jogos...</div>';
    }

    const { busca, idades, jogadores, tempos } = pegarFiltros();
    filtroAtivo = temFiltroAtivo();

    const params = new URLSearchParams();
    params.set('busca', busca);
    params.set('offset', offset);
    idades.forEach(i => params.append('idades[]', i));
    jogadores.forEach(j => params.append('jogadores[]', j));
    tempos.forEach(t => params.append('tempos[]', t));

    try {
        const res  = await fetch(`controllers/listarJogosAjax.php?${params}`);
        const data = await res.json();

        if (resetar) grid.innerHTML = '';

        if (data.jogos.length === 0 && resetar) {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#666;">😕 Nenhum jogo encontrado.</div>';
        } else {
            data.jogos.forEach(jogo => grid.appendChild(criarCard(jogo)));
        }

        offset  = data.offset + data.jogos.length;
        temMais = data.tem_mais;
        atualizarBotoesSelecionados();

    } catch(e) {
        console.error('Erro ao carregar jogos:', e);
    }

    loadingMais.style.display = 'none';
    carregando = false;
}

// ============================================================
// INFINITE SCROLL
// ============================================================
const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && temMais && !filtroAtivo && !carregando) {
        loadingMais.style.display = 'block';
        carregarJogos();
    }
}, { rootMargin: '200px' });

observer.observe(sentinel);

// ============================================================
// FILTROS — dispara recarregamento
// ============================================================
document.getElementById('busca-jogo').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => carregarJogos(true), 300);
});

document.querySelectorAll('.filtro-idade, .filtro-jogadores, .filtro-tempo').forEach(input => {
    input.addEventListener('change', () => carregarJogos(true));
});

document.getElementById('limpar-filtros').addEventListener('click', () => {
    document.getElementById('busca-jogo').value = '';
    document.querySelectorAll('.filtro-idade, .filtro-jogadores, .filtro-tempo')
        .forEach(i => i.checked = false);
    carregarJogos(true);
});

// ============================================================
// MODAL DO JOGO
// ============================================================
function abrirModalJogo(card) {
    jogoModalAtual = {
        nome:     card.dataset.nome,
        descricao: card.dataset.descricao,
        imagem:   card.dataset.imagem,
        preco:    card.dataset.preco,
        min:      card.dataset.min,
        max:      card.dataset.max,
        idade:    card.dataset.idade,
        tempo:    card.dataset.tempo,
        linkLudo: card.dataset.linkLudo,
    };

    modalImagem.src          = jogoModalAtual.imagem;
    modalImagem.alt          = jogoModalAtual.nome;
    modalNome.textContent    = jogoModalAtual.nome;
    modalDescricao.textContent = jogoModalAtual.descricao;
    modalJogadores.textContent = `👥 ${jogoModalAtual.min} - ${jogoModalAtual.max} jogadores`;
    modalTempo.textContent   = `⏱ ${jogoModalAtual.tempo} min`;
    modalIdade.textContent   = `👤 ${jogoModalAtual.idade}+`;
    modalPreco.textContent   = Number(jogoModalAtual.preco).toLocaleString('pt-BR', {
        style: 'currency', currency: 'BRL'
    });

    // Badge Ludopedia no modal
    if (jogoModalAtual.linkLudo) {
        modalLinkLudo.href = jogoModalAtual.linkLudo;
        modalLudopedia.style.display = 'block';
    } else {
        modalLudopedia.style.display = 'none';
    }

    atualizarBotaoModal();
    modalJogo.classList.add('ativo');
}

function fecharModais() {
    modalJogo.classList.remove('ativo');
    modalPedido.classList.remove('ativo');
}

// ============================================================
// SELEÇÃO DE JOGOS
// ============================================================
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
    document.querySelectorAll('.btn-escolher').forEach(botao => {
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

function atualizarBotaoModal() {
    if (!jogoModalAtual) return;
    const selecionado = selecionados.some(j => j.nome === jogoModalAtual.nome);
    modalSelecionar.textContent = selecionado ? 'Remover da seleção' : 'Selecionar jogo';
}

function alternarJogoSelecionado(jogo) {
    const index = selecionados.findIndex(i => i.nome === jogo.nome);
    if (index === -1) {
        selecionados.push(jogo);
    } else {
        selecionados.splice(index, 1);
    }
    atualizarBarraPedido();
    atualizarBotoesSelecionados();
    if (jogoModalAtual && jogoModalAtual.nome === jogo.nome) atualizarBotaoModal();
}

modalSelecionar.addEventListener('click', () => {
    if (!jogoModalAtual) return;
    alternarJogoSelecionado({ nome: jogoModalAtual.nome, preco: jogoModalAtual.preco });
});

// ============================================================
// MODAL DO PEDIDO
// ============================================================
function montarListaPedido() {
    const listaPedido  = document.getElementById('lista-pedido');
    const totalPedido  = document.getElementById('total-pedido');
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
            montarListaPedido();
            atualizarBarraPedido();
            atualizarBotoesSelecionados();
            if (selecionados.length === 0) modalPedido.classList.remove('ativo');
        });
    });
}

// ============================================================
// EVENTOS GERAIS
// ============================================================
document.getElementById('fechar-modal-jogo').addEventListener('click', fecharModais);
document.getElementById('fechar-modal-pedido').addEventListener('click', fecharModais);
modalJogo.addEventListener('click', e => { if (e.target === modalJogo) fecharModais(); });
modalPedido.addEventListener('click', e => { if (e.target === modalPedido) fecharModais(); });

document.getElementById('abrir-modal-pedido').addEventListener('click', () => {
    montarListaPedido();
    modalPedido.classList.add('ativo');
});

document.getElementById('continuar-escolhendo').addEventListener('click', () => {
    modalPedido.classList.remove('ativo');
});

document.getElementById('confirmar-whatsapp').addEventListener('click', () => {
    const total = selecionados.reduce((soma, j) => soma + Number(j.preco), 0);
    const mensagem = `Olá! Tenho interesse em alugar os jogos:\n\n- ${selecionados.map(j => j.nome).join('\n- ')}\n\nTotal estimado: ${formatarPreco(total)}\n\nPode me passar disponibilidade?`;
    window.open(`https://wa.me/5537999139354?text=${encodeURIComponent(mensagem)}`, '_blank');
});

// ============================================================
// INICIA
// ============================================================
carregarJogos(true);
</script>

</body>
</html>