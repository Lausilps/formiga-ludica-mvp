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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Jogos cadastrados</title>

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
                                <tr>
                                    <td class="tooltip-imagem coluna-nome">
                                        <?= htmlspecialchars($jogo['nome']) ?>

                                        <?php if (!empty($jogo['imagem'])): ?>

                                            <?php
                                                $imagem = $jogo['imagem'];

                                                if (str_starts_with($imagem, 'http')) {
                                                    $srcImagem = $imagem;
                                                } else {
                                                    $srcImagem = "../../" . $imagem;
                                                }
                                            ?>

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

                                        <a href="detalhes.php?id=<?= $jogo['id_jogo'] ?>" class="btn-acao">
                                            Detalhes
                                        </a>
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

    <script>
        function rodarEmbeddings() {
            const btn = document.getElementById('btn-embeddings');
            const status = document.getElementById('status-embeddings');

            if (!confirm('Isso vai gerar embeddings para os jogos novos. Pode levar alguns minutos. Continuar?')) return;

            btn.disabled = true;
            btn.style.opacity = '0.6';
            status.textContent = '⏳ Processando... aguarde.';

            fetch('../../controllers/gerarEmbeddings.php?token=formiga2024')
                .then(res => res.text())
                .then(txt => {
                    const match = txt.match(/✅ Concluído!.*/);
                    status.textContent = match ? match[0] : '✅ Concluído!';
                    btn.disabled = false;
                    btn.style.opacity = '';
                })
                .catch(() => {
                    status.textContent = '❌ Erro ao processar.';
                    btn.disabled = false;
                    btn.style.opacity = '';
                });
        }

        function sincronizarLudopedia() {
            const btn = document.getElementById('btn-ludopedia');
            const status = document.getElementById('status-ludopedia');

            if (!confirm('Isso vai sincronizar o catálogo com a Ludopedia. Pode levar alguns minutos. Continuar?')) return;

            btn.disabled = true;
            btn.style.opacity = '0.6';
            status.textContent = '⏳ Sincronizando com Ludopedia...';

            fetch('../../controllers/importarLudopediaController.php?token=formiga2024')
                .then(res => res.text())
                .then(txt => {
                    const match = txt.match(/✅ Importação concluída!.*/);
                    status.textContent = match ? match[0] : '✅ Sincronização concluída!';
                    btn.disabled = false;
                    btn.style.opacity = '';
                })
                .catch(() => {
                    status.textContent = '❌ Erro ao sincronizar.';
                    btn.disabled = false;
                    btn.style.opacity = '';
                });
        }
    </script>

</body>
</html>