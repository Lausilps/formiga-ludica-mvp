<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';
require_once '../../helpers/authHelper.php';

protegerAdmin();

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
            ativo
        FROM jogos
        $where
        ORDER BY nome ASC
        LIMIT $limite OFFSET $offset";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    registrarLog('ERRO', 'Falha ao listar jogos: ' . mysqli_error($conexao));
    die("Ocorreu um erro ao carregar os jogos.");
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

            <button type="button" onclick="rodarEmbeddings()" id="btn-embeddings" class="btn-admin btn-ia">
                🧠 Atualizar IA
            </button>

            <button type="button" onclick="sincronizarLudopedia()" id="btn-ludopedia" class="btn-admin btn-ludopedia">
                🎲 Sincronizar Ludopedia
            </button>

            <span id="status-embeddings" class="status-embeddings"></span>
            <span id="status-ludopedia" class="status-embeddings"></span>
        </section>

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
                            <?php while ($jogo = mysqli_fetch_assoc($resultado)): ?>
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
                                    <td><?= ucfirst($jogo['dificuldade']) ?></td>
                                    <td>R$ <?= number_format($jogo['preco'], 2, ',', '.') ?></td>

                                    <td>
                                        <?php if ($jogo['ativo']): ?>
                                            <span class="badge-status badge-ativo">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-inativo">Inativo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="acoes-tabela">
                                        <a href="editar.php?id=<?= $jogo['id_jogo'] ?>" class="btn-acao">
                                            Editar
                                        </a>

                                        <button
                                            type="button"
                                            class="btn-acao btn-detalhes"
                                            data-nome="<?= htmlspecialchars($jogo['nome']) ?>"
                                            data-imagem="<?= htmlspecialchars($srcImagem) ?>"
                                            data-descricao="<?= htmlspecialchars($jogo['descricao'] ?? 'Sem descrição cadastrada.') ?>"
                                            data-preco="<?= htmlspecialchars(number_format($jogo['preco'], 2, ',', '.')) ?>"
                                            data-min="<?= $jogo['min_jogadores'] ?>"
                                            data-max="<?= $jogo['max_jogadores'] ?>"
                                            data-idade="<?= $jogo['idade_minima'] ?>"
                                            data-duracao="<?= $jogo['duracao_minutos'] ?>"
                                            data-dificuldade="<?= htmlspecialchars(ucfirst($jogo['dificuldade'])) ?>"
                                            data-ativo="<?= $jogo['ativo'] ? '1' : '0' ?>"
                                        >
                                            Detalhes
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
            <img id="detalhes-imagem" src="" alt="">
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

                fetch('../../controllers/gerarEmbeddings.php?token=formiga2024')
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

            if (!confirm('Isso vai sincronizar o catálogo com a Ludopedia, gerando descrição e IA pros jogos novos. Pode levar vários minutos. Continuar?')) return;

            btn.disabled = true;
            btn.style.opacity = '0.6';

            let pagina = 1;
            let totalProcessados = 0;
            let totalInseridos = 0;
            let totalAtualizados = 0;
            let totalErros = 0;

            function proximaPagina() {
                status.textContent = `⏳ Sincronizando... (${totalProcessados} jogo(s) processado(s) até agora)`;

                fetch(`../../controllers/importarLudopediaController.php?token=formiga2024&pagina=${pagina}`)
                    .then(res => res.json())
                    .then(data => {
                        totalProcessados += data.processados;
                        totalInseridos += data.inseridos;
                        totalAtualizados += data.atualizados;
                        totalErros += data.erros;

                        if (data.temMais && pagina < 150) {
                            pagina++;
                            proximaPagina();
                            return;
                        }

                        status.textContent = `✅ Sincronização concluída! ${totalProcessados} jogo(s) — ${totalInseridos} novo(s), ${totalAtualizados} atualizado(s), ${totalErros} erro(s).`;
                        btn.disabled = false;
                        btn.style.opacity = '';
                    })
                    .catch(() => {
                        status.textContent = `❌ Erro ao sincronizar (${totalProcessados} jogo(s) processado(s) antes de falhar).`;
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

        document.querySelectorAll('.btn-detalhes').forEach(function(botao) {
            botao.addEventListener('click', function() {
                document.getElementById('detalhes-imagem').src = botao.dataset.imagem;
                document.getElementById('detalhes-imagem').alt = botao.dataset.nome;
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