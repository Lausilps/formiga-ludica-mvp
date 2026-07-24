<?php

require_once 'config/conexao.php';
require_once 'helpers/slugHelper.php';

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
          AND nome NOT LIKE 'SEM NOME (Ludopedia #%'
        ORDER BY nome ASC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao carregar catálogo.");
}

// Prévia de compartilhamento (Open Graph): quando o link vem com
// ?jogo=slug, troca o título/descrição/imagem padrão do site pelos do
// jogo específico, pra quem recebe o link no WhatsApp ver a capa do jogo
// em vez da logo da loja. Precisa ser resolvido aqui no PHP (antes do
// <head>) porque o crawler do WhatsApp só lê o HTML, não roda o JS que
// abre o modal.
$ogTitulo    = 'Formiga Lúdica - Catálogo';
$ogDescricao = 'Encontre o jogo perfeito para sua próxima jogatina!';
$ogImagem    = 'assets/img/logo_formiga_ludica.png';

$slugUrl = trim((string) ($_GET['jogo'] ?? ''));

if ($slugUrl !== '') {
    $resultadoNomes = mysqli_query($conexao, "SELECT nome, descricao, imagem FROM jogos WHERE ativo = 1");
    while ($linha = mysqli_fetch_assoc($resultadoNomes)) {
        if (gerarSlug($linha['nome']) === $slugUrl) {
            $ogTitulo    = $linha['nome'] . ' - Formiga Lúdica';
            $ogDescricao = mb_substr(trim(strip_tags($linha['descricao'] ?? '')), 0, 200);
            if (!empty($linha['imagem'])) {
                $ogImagem = $linha['imagem'];
            }
            break;
        }
    }
}

// og:image e og:url precisam ser URLs absolutas pro WhatsApp/Facebook
// conseguirem buscar a imagem — imagens vindas da Ludopedia já são
// (começam com "http"), as cadastradas manualmente são caminho relativo.
$protocolo = (!empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
$baseUrl   = $protocolo . '://' . $_SERVER['HTTP_HOST'];

if (!str_starts_with($ogImagem, 'http')) {
    $ogImagem = $baseUrl . '/' . ltrim($ogImagem, '/');
}

$ogUrl = $baseUrl . $_SERVER['REQUEST_URI'];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ogTitulo) ?></title>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitulo) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescricao) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImagem) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/png" href="assets/img/logo_formiga_ludica.png">
    <link rel="stylesheet" href="assets/css/catalogo.css">
    <script src="assets/js/carrinho.js"></script>
</head>
<body>

<header class="topo-barra">
    <div class="info-topo">
        <a href="index.php"><img src="assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="logo-topo"></a>
        <div class="texto-topo">
            <span class="titulo-catalogo">CATÁLOGO</span>
            <p>Encontre o jogo perfeito para sua próxima jogatina!</p>
        </div>
    </div>
    <div class="topo-acoes">
        <a href="recomendacao_form.php" class="mini-recomendacao">
            <img src="assets/img/formiguinha-rag.webp" alt="Formiguinha recomendando">
            <span>Recomendar para mim ✨</span>
        </a>
        <button type="button" class="btn-carrinho-topo" id="abrir-modal-pedido" aria-label="Ver pedido">
            🛒
            <span class="carrinho-badge" id="carrinho-badge">0</span>
        </button>
    </div>
</header>

<section class="catalogo-topo-wrap">
    <div class="catalogo-topo">
        <img src="assets/img/formiguinha-estoque.webp" alt="Formiguinha" class="banner-formiguinha-img">
        <div class="banner-texto">
            <span class="banner-label">NÃO SABE O QUE JOGAR HOJE?</span>
            <h2>Deixa que a <span class="destaque">Formiguinha</span> encontra o jogo perfeito para vocês!</h2>
            <a href="recomendacao_form.php" class="btn-recomendar-banner">Recomendar para mim ✨</a>
        </div>
    </div>
</section>

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

<section class="secao-carrossel secao-carrossel-novidades" id="secao-novidades" style="display:none;">
    <div class="secao-carrossel-inner">
        <div class="cabecalho-carrossel">
            <span class="icone-secao">✨</span>
            <div>
                <h2 class="titulo-carrossel">Confira as novidades</h2>
                <p class="subtitulo-carrossel">Os últimos jogos que chegaram por aqui.</p>
            </div>
        </div>
        <div class="carrossel-wrap">
            <button type="button" class="carrossel-nav carrossel-nav-esquerda" data-alvo="carrossel-novidades" aria-label="Anterior">‹</button>
            <div class="carrossel-trilha" id="carrossel-novidades"></div>
            <button type="button" class="carrossel-nav carrossel-nav-direita" data-alvo="carrossel-novidades" aria-label="Próximo">›</button>
        </div>
    </div>
</section>

<section class="secao-carrossel secao-carrossel-destaques" id="secao-destaques" style="display:none;">
    <div class="secao-carrossel-inner">
        <div class="cabecalho-carrossel">
            <span class="icone-secao">👍</span>
            <div>
                <h2 class="titulo-carrossel">Recomendações da loja</h2>
                <p class="subtitulo-carrossel">Jogos selecionados a dedo para garantir a sua diversão.</p>
            </div>
        </div>
        <div class="carrossel-wrap">
            <button type="button" class="carrossel-nav carrossel-nav-esquerda" data-alvo="carrossel-destaques" aria-label="Anterior">‹</button>
            <div class="carrossel-trilha" id="carrossel-destaques"></div>
            <button type="button" class="carrossel-nav carrossel-nav-direita" data-alvo="carrossel-destaques" aria-label="Próximo">›</button>
        </div>
    </div>
</section>

<div class="cabecalho-carrossel cabecalho-todos-jogos">
    <span class="icone-secao">🎲</span>
    <div>
        <h2 class="titulo-carrossel">Todos os jogos</h2>
        <p class="subtitulo-carrossel">Explore nosso catálogo completo.</p>
    </div>
</div>

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
        <div class="modal-imagem-area">
            <img id="modal-imagem" src="" alt="">
            <button type="button" id="modal-imagem-anterior" class="modal-imagem-nav modal-imagem-nav-esquerda" title="Foto anterior" style="display:none;">‹</button>
            <button type="button" id="modal-imagem-proxima" class="modal-imagem-nav modal-imagem-nav-direita" title="Próxima foto" style="display:none;">›</button>
            <div id="modal-imagem-contador" class="modal-imagem-contador" style="display:none;"></div>
        </div>
        <div>
            <h2 id="modal-nome"></h2>
            <p id="modal-descricao"></p>
            <div class="modal-infos">
                <span id="modal-jogadores"></span>
                <span id="modal-tempo"></span>
                <span id="modal-idade"></span>
                <strong id="modal-preco"></strong>
            </div>
            <div class="linha-secundaria-modal" style="margin-top:12px;">
                <div id="modal-ludopedia" style="display:none;">
                    <a id="modal-link-ludopedia" href="#" target="_blank" class="btn-ludopedia">
                        <img src="assets/img/logo-ludopedia.png" alt="Ludopedia">
                        Ver mais sobre o jogo
                    </a>
                </div>
                <button type="button" id="modal-compartilhar" class="btn-compartilhar-icone" title="Compartilhar jogo" aria-label="Compartilhar jogo">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 5l7 7-7 7v-4.1c-5.05.11-8.51 1.66-11 5.1 1-5 4-10 11-11V5z"/></svg>
                </button>
            </div>
            <br>
            <div class="acoes-modal-jogo">
                <button type="button" id="modal-selecionar" class="btn-escolher">Selecionar jogo</button>
            </div>
            <p id="modal-selo-ludopedia" class="selo-ludopedia-rodape" style="display:none;"><img src="assets/img/logo-ludopedia.png" alt="">integrado via Ludopedia</p>
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
            <button type="button" id="confirmar-whatsapp">Enviar pedido pelo WhatsApp</button>
        </div>
    </div>
</div>

<a href="https://wa.me/5537991121992" target="_blank" class="whatsapp-flutuante">
    <img src="assets/img/formiga-whatsapp.gif" alt="Chamar no WhatsApp">
</a>

<button type="button" id="btn-voltar-topo" class="btn-voltar-topo" aria-label="Voltar para os filtros">⬆</button>

<script>
// ============================================================
// ESTADO GLOBAL
// ============================================================
let offset        = 0;
let carregando    = false;
let temMais       = true;
let filtroAtivo   = false;
let debounceTimer = null;

const carrinhoUI = Carrinho.iniciarUI({
    botaoEscolherSeletor: '.btn-escolher',
    aoAlternarSelecao(jogo) {
        if (jogoModalAtual && jogoModalAtual.nome === jogo.nome) atualizarBotaoModal();
    }
});

// ============================================================
// ELEMENTOS
// ============================================================
const grid           = document.getElementById('grid-jogos');
const sentinel       = document.getElementById('sentinel');
const loadingMais    = document.getElementById('loading-mais');
const modalJogo      = document.getElementById('modal-jogo');
const modalImagem    = document.getElementById('modal-imagem');
const modalImagemAnterior = document.getElementById('modal-imagem-anterior');
const modalImagemProxima  = document.getElementById('modal-imagem-proxima');
const modalImagemContador = document.getElementById('modal-imagem-contador');
const modalNome      = document.getElementById('modal-nome');
const modalDescricao = document.getElementById('modal-descricao');
const modalJogadores = document.getElementById('modal-jogadores');
const modalTempo     = document.getElementById('modal-tempo');
const modalIdade     = document.getElementById('modal-idade');
const modalPreco     = document.getElementById('modal-preco');
const modalSelecionar = document.getElementById('modal-selecionar');
const modalLudopedia  = document.getElementById('modal-ludopedia');
const modalLinkLudo   = document.getElementById('modal-link-ludopedia');
const modalSeloLudopedia = document.getElementById('modal-selo-ludopedia');
const modalCompartilhar = document.getElementById('modal-compartilhar');

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
function criarCard(jogo, opcoes = {}) {
    const ocultarDescricao = opcoes.ocultarDescricao || false;

    const descricaoCurta = jogo.descricao.length > 110
        ? jogo.descricao.substring(0, 110) + '...'
        : jogo.descricao;

    const preco = Number(jogo.preco).toLocaleString('pt-BR', {
        style: 'currency', currency: 'BRL'
    });

    const article = document.createElement('article');
    article.className = 'card-jogo';
    article.dataset.id       = jogo.id;
    article.dataset.slug     = jogo.slug;
    article.dataset.nome     = jogo.nome;
    article.dataset.descricao = jogo.descricao;
    article.dataset.imagem   = jogo.imagem;
    article.dataset.preco    = jogo.preco;
    article.dataset.min      = jogo.min_jogadores;
    article.dataset.max      = jogo.max_jogadores;
    article.dataset.idade    = jogo.idade_minima;
    article.dataset.tempo    = jogo.duracao;
    article.dataset.linkLudo = jogo.link_ver_ludopedia ?? '';
    article.dataset.integradoLudopedia = jogo.link_ludopedia ? '1' : '';
    article._imagens = jogo.imagens && jogo.imagens.length ? jogo.imagens : [jogo.imagem];

    // Nome/descrição vêm do banco (jogo cadastrado à mão, ou importado da
    // Ludopedia) e podem conter caracteres que o navegador interpretaria
    // como HTML. Por isso o card monta a estrutura fixa via innerHTML, mas
    // preenche esses campos de texto livre depois, via textContent/src/alt
    // — que tratam o valor sempre como texto puro, nunca como código.
    article.innerHTML = `
        <div class="imagem-card">
            <img class="img-capa-card"
                 onerror="this.src='assets/img/sem-imagem.png'">
        </div>
        <div class="conteudo-card">
            <h2 class="titulo-card"></h2>
            ${ocultarDescricao ? '' : `<p class="descricao-card"></p>`}
            <div class="infos-card">
                <span>👥 ${jogo.min_jogadores} - ${jogo.max_jogadores}</span>
                <span>⏱ ${jogo.duracao} min</span>
                <span>👤 ${jogo.idade_minima}+</span>
            </div>
            <div class="rodape-card">
                <strong>${preco}</strong>
                <button type="button" class="btn-escolher"></button>
            </div>
        </div>
    `;

    const imgCapa = article.querySelector('.img-capa-card');
    imgCapa.src = jogo.imagem;
    imgCapa.alt = jogo.nome;

    article.querySelector('.titulo-card').textContent = jogo.nome;

    if (!ocultarDescricao) {
        article.querySelector('.descricao-card').textContent = descricaoCurta;
    }

    const botaoEscolher = article.querySelector('.btn-escolher');
    botaoEscolher.textContent = 'Escolher';
    botaoEscolher.dataset.nome = jogo.nome;

    // Passa as fotos automaticamente enquanto o mouse fica em cima do card
    // (só se tiver mais de uma foto)
    if (article._imagens.length > 1) {
        const imgCard = article.querySelector('.imagem-card img');
        let indiceHover = 0;
        let intervaloHover = null;

        article.addEventListener('mouseenter', function() {
            indiceHover = 0;
            intervaloHover = setInterval(function() {
                indiceHover = (indiceHover + 1) % article._imagens.length;
                imgCard.src = article._imagens[indiceHover];
            }, 1200);
        });

        article.addEventListener('mouseleave', function() {
            clearInterval(intervaloHover);
            imgCard.src = article._imagens[0];
        });
    }

    // Abre modal ao clicar no card
    article.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-escolher')) return;
        abrirModalJogo(article);
    });

    // Botão escolher
    article.querySelector('.btn-escolher').addEventListener('click', function(e) {
        e.stopPropagation();
        carrinhoUI.alternarJogoSelecionado({ nome: jogo.nome, preco: jogo.preco });
    });

    return article;
}

// ============================================================
// CARREGAR JOGOS
// ============================================================
async function carregarJogos(resetar = false, idsExcluir = new Set()) {
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

        // idsExcluir só vem preenchido quando há busca por nome: jogos já
        // mostrados em "Recomendações da loja"/"Novidades" não repetem
        // aqui (ver atualizarCatalogoFiltrado).
        const jogosFiltrados = idsExcluir.size > 0
            ? data.jogos.filter(jogo => !idsExcluir.has(jogo.id))
            : data.jogos;

        if (resetar) grid.innerHTML = '';

        if (jogosFiltrados.length === 0 && resetar) {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#666;">😕 Nenhum jogo encontrado.</div>';
        } else {
            jogosFiltrados.forEach(jogo => grid.appendChild(criarCard(jogo)));
        }

        offset  = data.offset + data.jogos.length;
        temMais = data.tem_mais;
        carrinhoUI.atualizarBotoesSelecionados();

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
    debounceTimer = setTimeout(() => {
        atualizarCatalogoFiltrado();
    }, 300);
});

document.querySelectorAll('.filtro-idade, .filtro-jogadores, .filtro-tempo').forEach(input => {
    input.addEventListener('change', () => {
        atualizarCatalogoFiltrado();
    });
});

document.getElementById('limpar-filtros').addEventListener('click', () => {
    document.getElementById('busca-jogo').value = '';
    document.querySelectorAll('.filtro-idade, .filtro-jogadores, .filtro-tempo')
        .forEach(i => i.checked = false);
    atualizarCatalogoFiltrado();
});

// ============================================================
// MODAL DO JOGO
// ============================================================
let modalJogoAberto = false;

let modalImagens = [];
let modalIndiceImagem = 0;

function atualizarImagemModal() {
    modalImagem.src = modalImagens[modalIndiceImagem];
    modalImagem.alt = jogoModalAtual.nome;

    const temVarias = modalImagens.length > 1;
    modalImagemAnterior.style.display = temVarias ? 'flex' : 'none';
    modalImagemProxima.style.display  = temVarias ? 'flex' : 'none';
    modalImagemContador.style.display = temVarias ? 'block' : 'none';

    if (temVarias) {
        modalImagemContador.textContent = `${modalIndiceImagem + 1} / ${modalImagens.length}`;
    }
}

function abrirModalJogo(card) {
    jogoModalAtual = {
        id:       card.dataset.id,
        slug:     card.dataset.slug,
        nome:     card.dataset.nome,
        descricao: card.dataset.descricao,
        imagem:   card.dataset.imagem,
        preco:    card.dataset.preco,
        min:      card.dataset.min,
        max:      card.dataset.max,
        idade:    card.dataset.idade,
        tempo:    card.dataset.tempo,
        linkLudo: card.dataset.linkLudo,
        integradoLudopedia: card.dataset.integradoLudopedia,
    };

    modalImagens = card._imagens && card._imagens.length ? card._imagens : [jogoModalAtual.imagem];
    modalIndiceImagem = 0;
    atualizarImagemModal();

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

    modalSeloLudopedia.style.display = jogoModalAtual.integradoLudopedia ? 'block' : 'none';

    atualizarBotaoModal();
    modalJogo.classList.add('ativo');
    modalJogoAberto = true;

    // Reflete o jogo aberto na URL — assim a barra de endereço já vira um
    // link compartilhável (funciona junto com o botão "Copiar link").
    const urlModal = new URL(window.location.href);
    urlModal.searchParams.set('jogo', jogoModalAtual.slug);
    history.pushState({ modal: 'jogo', id: jogoModalAtual.id }, '', urlModal);
}

function fecharModalJogo() {
    modalJogo.classList.remove('ativo');
    if (modalJogoAberto) {
        modalJogoAberto = false;
        history.back();
    }
}

// Voltar do navegador/celular fecha o modal em vez de sair do site
window.addEventListener('popstate', () => {
    modalJogoAberto = false;
    modalJogo.classList.remove('ativo');
});

modalImagemAnterior.addEventListener('click', () => {
    modalIndiceImagem = (modalIndiceImagem - 1 + modalImagens.length) % modalImagens.length;
    atualizarImagemModal();
});

modalImagemProxima.addEventListener('click', () => {
    modalIndiceImagem = (modalIndiceImagem + 1) % modalImagens.length;
    atualizarImagemModal();
});

// ============================================================
// SELEÇÃO DE JOGOS (MODAL DE DETALHES)
// ============================================================
function atualizarBotaoModal() {
    if (!jogoModalAtual) return;
    const selecionado = carrinhoUI.selecionados.some(j => j.nome === jogoModalAtual.nome);
    modalSelecionar.textContent = selecionado ? 'Remover da seleção' : 'Selecionar jogo';
}

modalSelecionar.addEventListener('click', () => {
    if (!jogoModalAtual) return;
    carrinhoUI.alternarJogoSelecionado({ nome: jogoModalAtual.nome, preco: jogoModalAtual.preco });
});

modalCompartilhar.addEventListener('click', async () => {
    if (!jogoModalAtual) return;

    const url = new URL(window.location.href);
    url.searchParams.set('jogo', jogoModalAtual.slug);
    const link = url.toString();

    // No celular/navegadores compatíveis, abre o menu nativo de
    // compartilhamento (WhatsApp, copiar link, etc). Sem suporte, cai pro
    // comportamento antigo de só copiar o link.
    if (navigator.share) {
        try {
            await navigator.share({ title: jogoModalAtual.nome, text: `Confira ${jogoModalAtual.nome} na Formiga Lúdica!`, url: link });
        } catch (e) {
            // usuário cancelou o compartilhamento — nada a fazer
        }
        return;
    }

    navigator.clipboard.writeText(link).then(() => {
        const iconeOriginal = modalCompartilhar.innerHTML;
        modalCompartilhar.innerHTML = '✅';
        modalCompartilhar.setAttribute('aria-label', 'Link copiado!');
        setTimeout(() => {
            modalCompartilhar.innerHTML = iconeOriginal;
            modalCompartilhar.setAttribute('aria-label', 'Compartilhar jogo');
        }, 2000);
    });
});

// ============================================================
// EVENTOS GERAIS
// ============================================================
document.getElementById('fechar-modal-jogo').addEventListener('click', fecharModalJogo);
modalJogo.addEventListener('click', e => { if (e.target === modalJogo) fecharModalJogo(); });

// ============================================================
// BOTÃO VOLTAR PARA OS FILTROS (aparece ao rolar, some no topo)
// ============================================================
const btnVoltarTopo = document.getElementById('btn-voltar-topo');

window.addEventListener('scroll', () => {
    btnVoltarTopo.classList.toggle('visivel', window.scrollY > 300);
});

btnVoltarTopo.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// ============================================================
// CARROSSÉIS (novidades + destaques da loja)
// ============================================================
function iniciarCarrossel(idTrilha) {
    const trilha = document.getElementById(idTrilha);
    if (!trilha) return;

    let intervalo = null;

    function distanciaCard() {
        const card = trilha.querySelector('.card-jogo');
        return card ? card.offsetWidth + 24 : 260;
    }

    function avancar() {
        const noFim = trilha.scrollLeft + trilha.clientWidth >= trilha.scrollWidth - 10;
        trilha.scrollTo({
            left: noFim ? 0 : trilha.scrollLeft + distanciaCard(),
            behavior: 'smooth'
        });
    }

    function voltar() {
        trilha.scrollTo({ left: trilha.scrollLeft - distanciaCard(), behavior: 'smooth' });
    }

    function iniciarAutoplay() {
        pararAutoplay();
        intervalo = setInterval(avancar, 3500);
    }

    function pararAutoplay() {
        if (intervalo) clearInterval(intervalo);
    }

    trilha.addEventListener('mouseenter', pararAutoplay);
    trilha.addEventListener('mouseleave', iniciarAutoplay);
    trilha.addEventListener('touchstart', pararAutoplay, { passive: true });

    document.querySelectorAll(`.carrossel-nav[data-alvo="${idTrilha}"]`).forEach(botao => {
        botao.addEventListener('click', () => {
            pararAutoplay();
            botao.classList.contains('carrossel-nav-direita') ? avancar() : voltar();
            iniciarAutoplay();
        });
    });

    iniciarAutoplay();
}

let destaquesCarrosselIniciado = false;
let novidadesCarrosselIniciado = false;

function popularCarrossel(idSecao, idTrilha, jogos, mostrarSeloNovo = false) {
    if (!jogos.length) return;

    const trilha = document.getElementById(idTrilha);

    jogos.forEach(jogo => {
        const card = criarCard(jogo, { ocultarDescricao: true });

        if (mostrarSeloNovo) {
            const selo = document.createElement('span');
            selo.className = 'selo-novo';
            selo.textContent = 'NOVO';
            card.querySelector('.imagem-card').appendChild(selo);
        }

        trilha.appendChild(card);
    });

    document.getElementById(idSecao).style.display = 'block';
    iniciarCarrossel(idTrilha);
}

fetch('controllers/carrosseisAjax.php')
    .then(res => res.json())
    .then(data => {
        popularCarrossel('secao-novidades', 'carrossel-novidades', data.novidades || [], true);
        popularCarrossel('secao-destaques', 'carrossel-destaques', data.destaques || []);
        novidadesCarrosselIniciado = (data.novidades || []).length > 0;
        destaquesCarrosselIniciado = (data.destaques || []).length > 0;
    })
    .catch(() => {});

// ============================================================
// NOVIDADES — segue os mesmos filtros do catálogo
// ============================================================
async function carregarNovidadesFiltradas(idsExcluir = new Set()) {
    const { busca, idades, jogadores, tempos } = pegarFiltros();

    const params = new URLSearchParams();
    params.set('busca', busca);
    params.set('so_novidades', '1');
    idades.forEach(i => params.append('idades[]', i));
    jogadores.forEach(j => params.append('jogadores[]', j));
    tempos.forEach(t => params.append('tempos[]', t));

    const secaoNovidades  = document.getElementById('secao-novidades');
    const trilhaNovidades = document.getElementById('carrossel-novidades');

    try {
        const res  = await fetch(`controllers/listarJogosAjax.php?${params}`);
        const data = await res.json();

        // idsExcluir vem preenchido quando há busca por nome e o jogo já
        // apareceu em "Recomendações da loja" (ver atualizarCatalogoFiltrado).
        const jogosFiltrados = data.jogos.filter(jogo => !idsExcluir.has(jogo.id));

        trilhaNovidades.innerHTML = '';
        trilhaNovidades.scrollLeft = 0;

        if (jogosFiltrados.length === 0) {
            secaoNovidades.style.display = 'none';
            return [];
        }

        jogosFiltrados.forEach(jogo => {
            const card = criarCard(jogo, { ocultarDescricao: true });
            const selo = document.createElement('span');
            selo.className = 'selo-novo';
            selo.textContent = 'NOVO';
            card.querySelector('.imagem-card').appendChild(selo);
            trilhaNovidades.appendChild(card);
        });
        secaoNovidades.style.display = 'block';

        if (!novidadesCarrosselIniciado) {
            iniciarCarrossel('carrossel-novidades');
            novidadesCarrosselIniciado = true;
        }

        return jogosFiltrados.map(jogo => jogo.id);
    } catch (e) {
        console.error('Erro ao filtrar novidades:', e);
        return [];
    }
}

// Coordena os três carregamentos quando os filtros mudam. Com busca por
// nome, mostra cada jogo numa seção só — a mais "curada" possível (destaque
// da loja > novidade > catálogo geral) — pra não repetir o mesmo resultado
// três vezes na tela quando a pessoa já está atrás de um jogo específico.
// Sem busca por nome (só idade/jogadores/tempo), continua tudo
// independente como antes: esses filtros são de navegação, não de "já
// achei o jogo que queria", então repetir entre as seções está OK.
async function atualizarCatalogoFiltrado() {
    const { busca } = pegarFiltros();

    if (busca.trim() === '') {
        carregarJogos(true);
        carregarDestaquesFiltrados();
        carregarNovidadesFiltradas();
        return;
    }

    const idsDestaques = await carregarDestaquesFiltrados();
    const idsExibidos = new Set(idsDestaques);

    const idsNovidades = await carregarNovidadesFiltradas(idsExibidos);
    idsNovidades.forEach(id => idsExibidos.add(id));

    carregarJogos(true, idsExibidos);
}

// ============================================================
// RECOMENDAÇÕES DA LOJA — segue os mesmos filtros do catálogo
// ============================================================
async function carregarDestaquesFiltrados() {
    const { busca, idades, jogadores, tempos } = pegarFiltros();

    const params = new URLSearchParams();
    params.set('busca', busca);
    params.set('so_destaques', '1');
    idades.forEach(i => params.append('idades[]', i));
    jogadores.forEach(j => params.append('jogadores[]', j));
    tempos.forEach(t => params.append('tempos[]', t));

    const secaoDestaques   = document.getElementById('secao-destaques');
    const trilhaDestaques  = document.getElementById('carrossel-destaques');

    try {
        const res  = await fetch(`controllers/listarJogosAjax.php?${params}`);
        const data = await res.json();

        trilhaDestaques.innerHTML = '';
        trilhaDestaques.scrollLeft = 0;

        if (data.jogos.length === 0) {
            secaoDestaques.style.display = 'none';
            return [];
        }

        data.jogos.forEach(jogo => trilhaDestaques.appendChild(criarCard(jogo, { ocultarDescricao: true })));
        secaoDestaques.style.display = 'block';

        if (!destaquesCarrosselIniciado) {
            iniciarCarrossel('carrossel-destaques');
            destaquesCarrosselIniciado = true;
        }

        return data.jogos.map(jogo => jogo.id);
    } catch (e) {
        console.error('Erro ao filtrar recomendações da loja:', e);
        return [];
    }
}

// ============================================================
// LINK DIRETO PRO JOGO (?jogo=slug) — abre o modal dele automaticamente
// ============================================================
async function abrirJogoPorLinkDireto() {
    const slugJogo = new URLSearchParams(window.location.search).get('jogo');
    if (!slugJogo) return;

    // Zera o "estado base" da URL (sem o parâmetro) antes de abrir o modal,
    // pra fechar (history.back()) voltar pro catálogo em vez de sair do
    // site inteiro — quem chegou aqui por um link direto ainda não tem
    // nenhum estado nosso no histórico do navegador.
    const urlBase = new URL(window.location.href);
    urlBase.searchParams.delete('jogo');
    history.replaceState(null, '', urlBase);

    try {
        const res  = await fetch(`controllers/buscarJogoPorIdAjax.php?slug=${encodeURIComponent(slugJogo)}`);
        const data = await res.json();
        if (!data.jogo) return;

        abrirModalJogo(criarCard(data.jogo));
    } catch (e) {
        console.error('Erro ao abrir jogo pelo link direto:', e);
    }
}

// ============================================================
// INICIA
// ============================================================
carregarJogos(true);
abrirJogoPorLinkDireto();
</script>

<?php include 'views/partials/footer.php'; ?>

</body>
</html>