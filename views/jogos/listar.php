<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';

require_once '../../helpers/authHelper.php';
protegerAdmin();

$busca = $_GET['busca'] ?? '';
$pagina = $_GET['pagina'] ?? 1;

$limite = 20;
$offset = ($pagina - 1) * $limite;

$where = "";

if (!empty($busca)) {
    $where = "WHERE nome LIKE '%$busca%'";
}

$sqlTotal = "SELECT COUNT(*) AS total FROM jogos $where";
$resultadoTotal = mysqli_query($conexao, $sqlTotal);

if (!$resultadoTotal) {
    registrarLog(
        'ERRO',
        'Falha ao contar jogos: ' . mysqli_error($conexao)
    );

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
    registrarLog(
        'ERRO',
        'Falha ao listar jogos: ' . mysqli_error($conexao)
    );

    die("Ocorreu um erro ao carregar os jogos.");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Jogos</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/listar.css">

</head>
<body>

    <h1>Jogos cadastrados</h1>

    <form method="GET" action="listar.php" class="form-busca">
        <input
            type="text"
            name="busca"
            placeholder="Pesquisar jogo pelo nome"
            value="<?= $busca ?>"
        >

        <button type="submit">Pesquisar</button>

        <a href="listar.php">Limpar</a>
    </form>

    <p>
            <a href="cadastrar.php">+ Cadastrar novo jogo</a>
            |
            <a href="importar_olaclick.php">Importar OlaClick</a>
            |
            <a href="relatorio.php">Gerar relatório</a>
    </p>

    <?php if (mysqli_num_rows($resultado) == 0): ?>

        <div class="alerta alerta-erro">
            Nenhum jogo cadastrado até o momento.
        </div>

    <?php else: ?>

        <table border="1" cellpadding="10" cellspacing="0">
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
                        <td class="tooltip-imagem">

                            <?= $jogo['nome'] ?>

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
                                    <img src="<?= $srcImagem ?>" alt="<?= $jogo['nome'] ?>">

                                    <p class="descricao-preview">
                                        <?= mb_strimwidth($jogo['descricao'] ?? '', 0, 250, '...') ?>
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
                            <?= $jogo['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </td>
                        <td>
                            <a href="editar.php?id=<?= $jogo['id_jogo'] ?>">Editar</a>
                            |
                            <a href="detalhes.php?id=<?= $jogo['id_jogo'] ?>">Detalhes</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

               <?php if ($totalPaginas > 1): ?>

    <div class="paginacao">

        <?php if ($pagina > 1): ?>
                    <a href="listar.php?pagina=<?= $pagina - 1 ?>&busca=<?= $busca ?>">
                        &laquo;
                    </a>
                <?php endif; ?>

                <?php

                $inicio = $pagina;
                $fim = $pagina + 2;

                if ($fim > $totalPaginas) {
                    $fim = $totalPaginas;
                    $inicio = max(1, $fim - 2);
                }

                for ($i = $inicio; $i <= $fim; $i++):
                ?>

                    <a
                        href="listar.php?pagina=<?= $i ?>&busca=<?= $busca ?>"
                        class="<?= $i == $pagina ? 'pagina-atual' : '' ?>"
                    >
                        <?= $i ?>
                    </a>

                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a href="listar.php?pagina=<?= $pagina + 1 ?>&busca=<?= $busca ?>">
                        &raquo;
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

    <br>
    <br>

        <div class="sair-admin">

        <a href="../../logout.php" class="btn-logout">
            Sair
        </a>

</body>
</html>