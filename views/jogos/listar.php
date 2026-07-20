<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';
require_once '../../helpers/authHelper.php';
require_once '../../helpers/jogoHelper.php';

protegerAdmin();

$tokenAdmin = getenv('ADMIN_IMPORT_TOKEN') ?: '';

$busca = $_GET['busca'] ?? '';
$pagina = (int)($_GET['pagina'] ?? 1);

if ($pagina < 1) {
    $pagina = 1;
}

$limite = 20;
$offset = ($pagina - 1) * $limite;

$where = "";

if (!empty($busca)) {
    $buscaSql = mysqli_real_escape_string($conexao, $busca);
    $where = "WHERE nome LIKE '%$buscaSql%'";
}

$sqlTotal = "SELECT COUNT(*) AS total FROM jogos $where";
$resultadoTotal = mysqli_query($conexao, $sqlTotal);

if (!$resultadoTotal) {
    registrarLog('ERRO', 'Falha ao contar jogos: ' . mysqli_error($conexao));
    die("Ocorreu um erro ao contar os jogos.");
}

$totalRegistros = mysqli_fetch_assoc($resultadoTotal)['total'];
$totalPaginas = ceil($totalRegistros / $limite);

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
            dificuldade,
            ativo,
            origem,
            link_ludopedia
        FROM jogos
        $where
        ORDER BY nome ASC
        LIMIT $limite OFFSET $offset";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    registrarLog('ERRO', 'Falha ao listar jogos: ' . mysqli_error($conexao));
    die("Ocorreu um erro ao carregar os jogos.");
}

$linhasJogos = [];
while ($linha = mysqli_fetch_assoc($resultado)) {
    $linhasJogos[] = $linha;
}

// Galeria de fotos de cada jogo desta página, numa única consulta extra
// (evita uma query por jogo). Usada pro carrossel no modal de detalhes.
$idsJogosPagina = array_column($linhasJogos, 'id_jogo');
$imagensPorJogoAdmin = [];

if (!empty($idsJogosPagina)) {
    $placeholdersImg = implode(',', array_fill(0, count($idsJogosPagina), '?'));
    $tiposImgAdmin = str_repeat('i', count($idsJogosPagina));

    $stmtImgAdmin = mysqli_prepare($conexao, "SELECT id_jogo, caminho FROM jogos_imagens WHERE id_jogo IN ($placeholdersImg) ORDER BY id_jogo, ordem ASC");
    mysqli_stmt_bind_param($stmtImgAdmin, $tiposImgAdmin, ...$idsJogosPagina);
    mysqli_stmt_execute($stmtImgAdmin);
    $resultImgAdmin = mysqli_stmt_get_result($stmtImgAdmin);

    while ($linhaImgAdmin = mysqli_fetch_assoc($resultImgAdmin)) {
        $caminhoImg = $linhaImgAdmin['caminho'];
        $srcImg = str_starts_with($caminhoImg, 'http') ? $caminhoImg : '../../' . $caminhoImg;
        $imagensPorJogoAdmin[$linhaImgAdmin['id_jogo']][] = $srcImg;
    }
}

$buscaUrl = urlencode($busca);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Admin - Jogos cadastrados</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo_formiga_ludica.png">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/listar.css">
</head>
<body class="admin-body">

    <?php
        $tituloPagina = 'Jogos cadastrados';
        $subtituloPagina = 'Gerencie o catálogo da Formiga Lúdica.';
        $mostrarLogout = true;
        include '../partials/admin_header.php';
    ?>

    <main class="admin-container">

        <section class="card-admin card-busca-admin">
            <form method="GET" action="listar.php" class="form-busca">
                <input
                    type="text"
                    name="busca"
                    placeholder="Pesquisar jogo pelo nome"
                    value="<?= htmlspecialchars($busca) ?>"
                >

                <button type="submit">Pesquisar</button>

                <a href="listar.php" class="btn-limpar">Limpar</a>
            </form>
        </section>

        <section class="card-admin acoes-admin">
            <a href="cadastrar.php" class="btn-admin btn-roxo">
                + Cadastrar novo jogo
            </a>

            <a href="importar_olaclick.php" class="btn-admin btn-amarelo">
                Importar OlaClick
            </a>

            <a href="relatorio.php" class="btn-admin btn-contorno">
                Gerar relatório
            </a>

            <a href="destaques.php" class="btn-admin btn-contorno">
                ⭐ Destaques da loja
            </a>

            <a href="../usuarios/criar.php" class="btn-admin btn-contorno">
                + Novo usuário
            </a>

            <button type="button" onclick="rodarEmbeddings()" id="btn-embeddings" class="btn-admin btn-ia">
                🧠 Atualizar IA
            </button>

            <button type="button" onclick="sincronizarLudopedia()" id="btn-ludopedia" class="btn-admin btn-ludopedia">
                🎲 Sincronizar Ludopedia
            </button>

            <span id="status-embeddings" class="status-embeddings"></span>
            <span id="status-ludopedia" class="status-embeddings"></span>
        </section>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'excluido'): ?>
            <div class="alerta alerta-sucesso">
                Jogo excluído com sucesso.
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($resultado) == 0): ?>

            <div class="alerta alerta-erro">
                Nenhum jogo cadastrado até o momento.
            </div>

        <?php else: ?>

            <section class="card-admin tabela-card">

                <div class="tabela-topo">
                    <div>
                        <h2>Catálogo administrativo</h2>
                        <p>
                            <?= $totalRegistros ?> jogo(s) encontrado(s)
                        </p>
                    </div>

                    <span class="badge-sistema">Sistema online</span>
                </div>

                <div class="tabela-responsiva">
                    <table class="tabela-jogos">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Jogadores</th>
                                <th>Idade</th>
                                <th>Duração</th>
                                <th>Dificuldade</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($linhasJogos as $jogo): ?>
                                <?php
                                    if (!empty($jogo['imagem'])) {
                                        $srcImagem = str_starts_with($jogo['imagem'], 'http')
                                            ? $jogo['imagem']
                                            : "../../" . $jogo['imagem'];
                                    } else {
                                        $srcImagem = "../../assets/img/sem-imagem.png";
                                    }
                                ?>
                                <tr>
                                    <td class="tooltip-imagem coluna-nome">
                                        <?= htmlspecialchars($jogo['nome']) ?>

                                        <?php if ($jogo['origem'] === 'ludopedia'): ?>
                                            <img src="../../assets/img/logo-ludopedia.png" alt="Ludopedia" title="Importado da Ludopedia" class="icone-origem-ludopedia">
                                        <?php endif; ?>

                                        <?php if (!empty($jogo['imagem'])): ?>
                                            <span class="imagem-preview">
                                                <img src="<?= htmlspecialchars($srcImagem) ?>" alt="<?= htmlspecialchars($jogo['nome']) ?>">

                                                <p class="descricao-preview">
                                                    <?= htmlspecialchars(mb_strimwidth($jogo['descricao'] ?? '', 0, 250, '...')) ?>
                                                </p>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= $jogo['min_jogadores'] ?> a <?= $jogo['max_jogadores'] ?></td>
                                    <td><?= $jogo['idade_minima'] ?>+</td>
                                    <td><?= $jogo['duracao_minutos'] ?> min</td>
                                    <td><?= formatarDificuldade($jogo['dificuldade']) ?></td>
                                    <td>R$ <?= number_format($jogo['preco'], 2, ',', '.') ?></td>

                                    <td>
                                        <?php if ($jogo['ativo']): ?>
                                            <span class="badge-status badge-ativo">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-inativo">Inativo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="acoes-tabela">
                                        <a href="editar.php?id=<?= $jogo['id_jogo'] ?>&pagina=<?= $pagina ?>&busca=<?= $buscaUrl ?>" class="btn-acao">
                                            Editar
                                        </a>

                                        <button
                                            type="button"
                                            class="btn-acao btn-detalhes"
                                            data-nome="<?= htmlspecialchars($jogo['nome']) ?>"
                                            data-imagem="<?= htmlspecialchars($srcImagem) ?>"
                                            data-imagens="<?= htmlspecialchars(json_encode($imagensPorJogoAdmin[$jogo['id_jogo']] ?? [$srcImagem])) ?>"
                                            data-descricao="<?= htmlspecialchars($jogo['descricao'] ?? 'Sem descrição cadastrada.') ?>"
                                            data-preco="<?= htmlspecialchars(number_format($jogo['preco'], 2, ',', '.')) ?>"
                                            data-min="<?= $jogo['min_jogadores'] ?>"
                                            data-max="<?= $jogo['max_jogadores'] ?>"
                                            data-idade="<?= $jogo['idade_minima'] ?>"
                                            data-duracao="<?= $jogo['duracao_minutos'] ?>"
                                            data-dificuldade="<?= htmlspecialchars(formatarDificuldade($jogo['dificuldade'])) ?>"
                                            data-ativo="<?= $jogo['ativo'] ? '1' : '0' ?>"
                                            data-link-ludopedia="<?= htmlspecialchars($jogo['link_ludopedia'] ?? '') ?>"
                                        >
                                            Detalhes
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </section>

            <?php if ($totalPaginas > 1): ?>

                <div class="paginacao">

                    <?php if ($pagina > 1): ?>
                        <a href="listar.php?pagina=<?= $pagina - 1 ?>&busca=<?= $buscaUrl ?>">
                            ← Anterior
                        </a>
                    <?php endif; ?>

                    <?php
                        $grupoAtual = ceil($pagina / 5);
                        $inicio = (($grupoAtual - 1) * 5) + 1;
                        $fim = min($inicio + 4, $totalPaginas);
                    ?>

                    <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                        <a
                            href="listar.php?pagina=<?= $i ?>&busca=<?= $buscaUrl ?>"
                            class="<?= $i == $pagina ? 'pagina-atual' : '' ?>"
                        >
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($fim < $totalPaginas): ?>
                        <span class="reticencias">...</span>
                    <?php endif; ?>

                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="listar.php?pagina=<?= $pagina + 1 ?>&busca=<?= $buscaUrl ?>">
                            Próxima →
                        </a>
                    <?php endif; ?>

                    <span class="info-paginacao">
                        Página <?= $pagina ?> de <?= $totalPaginas ?>
                    </span>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </main>

    <!-- Modal de detalhes do jogo -->
    <div class="modal" id="modal-detalhes-jogo">
        <div class="modal-conteudo">
            <button type="button" class="fechar-modal" id="fechar-modal-detalhes">×</button>
            <div class="modal-imagem-area">
                <img id="detalhes-imagem" src="" alt="">
                <button type="button" id="detalhes-imagem-anterior" class="modal-imagem-nav modal-imagem-nav-esquerda" title="Foto anterior" style="display:none;">‹</button>
                <button type="button" id="detalhes-imagem-proxima" class="modal-imagem-nav modal-imagem-nav-direita" title="Próxima foto" style="display:none;">›</button>
                <div id="detalhes-imagem-contador" class="modal-imagem-contador" style="display:none;"></div>
            </div>
            <div>
                <span class="badge-status" id="detalhes-status"></span>
                <h2 id="detalhes-nome"></h2>
                <p id="detalhes-descricao"></p>
                <div class="modal-infos">
                    <span id="detalhes-jogadores"></span>
                    <span id="detalhes-tempo"></span>
                    <span id="detalhes-idade"></span>
                    <span id="detalhes-dificuldade"></span>
                    <strong id="detalhes-preco"></strong>
                </div>

                <div id="detalhes-ludopedia" style="display:none; margin-top:12px;">
                    <a id="detalhes-link-ludopedia" href="#" target="_blank" class="btn-ludopedia">
                        <img src="../../assets/img/logo-ludopedia.png" alt="Ludopedia">
                        Ver na Ludopedia
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function rodarEmbeddings() {
            const btn = document.getElementById('btn-embeddings');
            const status = document.getElementById('status-embeddings');

            if (!confirm('Isso vai gerar embeddings para os jogos novos. Pode levar alguns minutos. Continuar?')) return;

            btn.disabled = true;
            btn.style.opacity = '0.6';

            let totalProcessados = 0;
            let totalErros = 0;
            let semProgresso = 0;

            function proximoLote() {
                status.textContent = `⏳ Processando... (${totalProcessados} gerado(s) até agora)`;

                fetch('../../controllers/gerarEmbeddings.php?token=<?= urlencode($tokenAdmin) ?>')
                    .then(res => res.json())
                    .then(data => {
                        totalProcessados += data.processados;
                        totalErros += data.erros;
                        semProgresso = (data.processados === 0 && data.erros === 0) ? semProgresso + 1 : 0;

                        if (data.restantes > 0 && semProgresso < 3) {
                            proximoLote();
                            return;
                        }

                        status.textContent = `✅ Concluído! ${totalProcessados} embedding(s) gerado(s), ${totalErros} erro(s).` +
                            (data.restantes > 0 ? ` ${data.restantes} ainda sem embedding — tenta rodar de novo.` : '');
                        btn.disabled = false;
                        btn.style.opacity = '';
                    })
                    .catch(() => {
                        status.textContent = `❌ Erro ao processar (${totalProcessados} gerado(s) antes de falhar).`;
                        btn.disabled = false;
                        btn.style.opacity = '';
                    });
            }

            proximoLote();
        }

        function sincronizarLudopedia() {
            const btn = document.getElementById('btn-ludopedia');
            const status = document.getElementById('status-ludopedia');

            const CHAVE_RETOMADA = 'formigaludica_ludopedia_sync_pagina';
            const paginaSalva = parseInt(localStorage.getItem(CHAVE_RETOMADA), 10);
            const paginaInicial = Number.isInteger(paginaSalva) && paginaSalva > 0 ? paginaSalva : 1;

            const aviso = paginaInicial > 1
                ? `Isso vai continuar a sincronização de onde parou (página ${paginaInicial}). Pode levar vários minutos. Continuar?`
                : 'Isso vai sincronizar o catálogo com a Ludopedia, gerando descrição e IA pros jogos novos. Pode levar vários minutos. Continuar?';

            if (!confirm(aviso)) return;

            btn.disabled = true;
            btn.style.opacity = '0.6';

            let pagina = paginaInicial;
            let totalProcessados = 0;
            let totalInseridos = 0;
            let totalAtualizados = 0;
            let totalPulados = 0;
            let totalErros = 0;

            function proximaPagina() {
                status.textContent = `⏳ Sincronizando... (${totalProcessados} jogo(s) processado(s) até agora, na página ${pagina})`;

                fetch(`../../controllers/importarLudopediaController.php?token=<?= urlencode($tokenAdmin) ?>&pagina=${pagina}`)
                    .then(res => res.json())
                    .then(data => {
                        totalProcessados += data.processados;
                        totalInseridos += data.inseridos;
                        totalAtualizados += data.atualizados;
                        totalPulados += data.pulados;
                        totalErros += data.erros;

                        // Falhou por rate limit (não é fim real da coleção): para
                        // aqui, sem insistir sozinho, e guarda a página pra
                        // continuar dali da próxima vez que clicar no botão.
                        if (data.falhaColecao) {
                            localStorage.setItem(CHAVE_RETOMADA, String(pagina));
                            status.textContent = `⚠️ Parou na página ${pagina} (rate limit da Ludopedia) — ${totalProcessados} jogo(s) processado(s). Espere um pouco e clique em "Sincronizar Ludopedia" de novo: vai continuar daqui, não do zero.`;
                            btn.disabled = false;
                            btn.style.opacity = '';
                            return;
                        }

                        if (data.temMais && pagina < 150) {
                            pagina++;
                            localStorage.setItem(CHAVE_RETOMADA, String(pagina));
                            proximaPagina();
                            return;
                        }

                        // Chegou ao fim de verdade: limpa a retomada, próxima
                        // sincronização volta a começar do 1.
                        localStorage.removeItem(CHAVE_RETOMADA);
                        status.textContent = `✅ Sincronização concluída! ${totalProcessados} jogo(s) — ${totalInseridos} novo(s), ${totalAtualizados} atualizado(s), ${totalPulados} já cadastrado(s) (pulado(s)), ${totalErros} erro(s).`;
                        btn.disabled = false;
                        btn.style.opacity = '';
                    })
                    .catch(() => {
                        localStorage.setItem(CHAVE_RETOMADA, String(pagina));
                        status.textContent = `❌ Erro ao sincronizar (${totalProcessados} jogo(s) processado(s) antes de falhar, parou na página ${pagina}). Clique em "Sincronizar Ludopedia" de novo pra continuar daqui.`;
                        btn.disabled = false;
                        btn.style.opacity = '';
                    });
            }

            proximaPagina();
        }

        // ============================================================
        // MODAL DE DETALHES DO JOGO
        // ============================================================
        const modalDetalhes = document.getElementById('modal-detalhes-jogo');
        const detalhesImagem = document.getElementById('detalhes-imagem');
        const detalhesImagemAnterior = document.getElementById('detalhes-imagem-anterior');
        const detalhesImagemProxima  = document.getElementById('detalhes-imagem-proxima');
        const detalhesImagemContador = document.getElementById('detalhes-imagem-contador');

        let detalhesImagens = [];
        let detalhesIndiceImagem = 0;

        function atualizarImagemDetalhes() {
            detalhesImagem.src = detalhesImagens[detalhesIndiceImagem];

            const temVarias = detalhesImagens.length > 1;
            detalhesImagemAnterior.style.display = temVarias ? 'flex' : 'none';
            detalhesImagemProxima.style.display  = temVarias ? 'flex' : 'none';
            detalhesImagemContador.style.display = temVarias ? 'block' : 'none';

            if (temVarias) {
                detalhesImagemContador.textContent = `${detalhesIndiceImagem + 1} / ${detalhesImagens.length}`;
            }
        }

        detalhesImagemAnterior.addEventListener('click', () => {
            detalhesIndiceImagem = (detalhesIndiceImagem - 1 + detalhesImagens.length) % detalhesImagens.length;
            atualizarImagemDetalhes();
        });

        detalhesImagemProxima.addEventListener('click', () => {
            detalhesIndiceImagem = (detalhesIndiceImagem + 1) % detalhesImagens.length;
            atualizarImagemDetalhes();
        });

        document.querySelectorAll('.btn-detalhes').forEach(function(botao) {
            botao.addEventListener('click', function() {
                detalhesImagens = JSON.parse(botao.dataset.imagens || '[]');
                if (!detalhesImagens.length) detalhesImagens = [botao.dataset.imagem];
                detalhesIndiceImagem = 0;
                atualizarImagemDetalhes();
                detalhesImagem.alt = botao.dataset.nome;
                document.getElementById('detalhes-nome').textContent = botao.dataset.nome;
                document.getElementById('detalhes-descricao').textContent = botao.dataset.descricao;
                document.getElementById('detalhes-jogadores').textContent = `👥 ${botao.dataset.min} - ${botao.dataset.max} jogadores`;
                document.getElementById('detalhes-tempo').textContent = `⏱ ${botao.dataset.duracao} min`;
                document.getElementById('detalhes-idade').textContent = `👤 ${botao.dataset.idade}+`;
                document.getElementById('detalhes-dificuldade').textContent = `🎯 ${botao.dataset.dificuldade}`;
                document.getElementById('detalhes-preco').textContent = `R$ ${botao.dataset.preco}`;

                const status = document.getElementById('detalhes-status');
                if (botao.dataset.ativo === '1') {
                    status.textContent = 'Ativo';
                    status.className = 'badge-status badge-ativo';
                } else {
                    status.textContent = 'Inativo';
                    status.className = 'badge-status badge-inativo';
                }

                const linkLudopedia = document.getElementById('detalhes-link-ludopedia');
                const blocoLudopedia = document.getElementById('detalhes-ludopedia');

                if (botao.dataset.linkLudopedia) {
                    linkLudopedia.href = botao.dataset.linkLudopedia;
                    blocoLudopedia.style.display = 'block';
                } else {
                    blocoLudopedia.style.display = 'none';
                }

                modalDetalhes.classList.add('ativo');
            });
        });

        document.getElementById('fechar-modal-detalhes').addEventListener('click', function() {
            modalDetalhes.classList.remove('ativo');
        });

        modalDetalhes.addEventListener('click', function(e) {
            if (e.target === modalDetalhes) modalDetalhes.classList.remove('ativo');
        });
    </script>

</body>
</html>