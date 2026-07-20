<?php

// Gerencia os jogos "em destaque" (carrossel de recomendação da loja, no
// catálogo público). ordem_destaque NULL = não está em destaque; um
// número = está, nessa posição — igual à lógica de ordem da galeria de
// fotos (jogoImagensHelper.php), só que na tabela jogos direto.

function listarDestaques(mysqli $conexao): array
{
    $resultado = mysqli_query($conexao, "
        SELECT id_jogo, nome, imagem, ordem_destaque
        FROM jogos
        WHERE ordem_destaque IS NOT NULL
        ORDER BY ordem_destaque ASC
    ");

    $jogos = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $jogos[] = $linha;
    }

    return $jogos;
}

function buscarJogosParaDestaque(mysqli $conexao, string $termo): array
{
    $stmt = mysqli_prepare($conexao, "
        SELECT id_jogo, nome, imagem
        FROM jogos
        WHERE nome LIKE ? AND ativo = 1 AND ordem_destaque IS NULL
        ORDER BY nome ASC
        LIMIT 8
    ");
    $termoBusca = '%' . $termo . '%';
    mysqli_stmt_bind_param($stmt, 's', $termoBusca);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $jogos = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $jogos[] = $linha;
    }

    return $jogos;
}

function reordenarDestaquesSequencialmente(mysqli $conexao, array $jogos): void
{
    foreach ($jogos as $posicao => $jogo) {
        $stmt = mysqli_prepare($conexao, "UPDATE jogos SET ordem_destaque = ? WHERE id_jogo = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $posicao, $jogo['id_jogo']);
        mysqli_stmt_execute($stmt);
    }
}

function adicionarDestaque(mysqli $conexao, int $idJogo): void
{
    $resultado = mysqli_query($conexao, "SELECT COALESCE(MAX(ordem_destaque), -1) + 1 AS proxima FROM jogos WHERE ordem_destaque IS NOT NULL");
    $proxima = (int) mysqli_fetch_assoc($resultado)['proxima'];

    $stmt = mysqli_prepare($conexao, "UPDATE jogos SET ordem_destaque = ? WHERE id_jogo = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $proxima, $idJogo);
    mysqli_stmt_execute($stmt);
}

function removerDestaque(mysqli $conexao, int $idJogo): void
{
    $stmt = mysqli_prepare($conexao, "UPDATE jogos SET ordem_destaque = NULL WHERE id_jogo = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idJogo);
    mysqli_stmt_execute($stmt);

    reordenarDestaquesSequencialmente($conexao, listarDestaques($conexao));
}

function moverDestaque(mysqli $conexao, int $idJogo, string $direcao): void
{
    $jogos = listarDestaques($conexao);

    $posicaoAtual = null;
    foreach ($jogos as $indice => $jogo) {
        if ((int) $jogo['id_jogo'] === $idJogo) {
            $posicaoAtual = $indice;
            break;
        }
    }

    if ($posicaoAtual === null) {
        return;
    }

    $posicaoAlvo = $direcao === 'esquerda' ? $posicaoAtual - 1 : $posicaoAtual + 1;

    if ($posicaoAlvo < 0 || $posicaoAlvo >= count($jogos)) {
        return;
    }

    [$jogos[$posicaoAtual], $jogos[$posicaoAlvo]] = [$jogos[$posicaoAlvo], $jogos[$posicaoAtual]];

    reordenarDestaquesSequencialmente($conexao, $jogos);
}
