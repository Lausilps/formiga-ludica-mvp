<?php

// Gerencia a galeria de fotos de um jogo (tabela jogos_imagens). A foto de
// ordem 0 é sempre a capa, e jogos.imagem é mantida como um espelho dela —
// assim todas as outras telas do sistema (listagem, catálogo, relatórios,
// recomendação) continuam lendo jogos.imagem normalmente, sem precisar
// saber que a galeria existe.

function listarImagensJogo(mysqli $conexao, int $idJogo): array
{
    $stmt = mysqli_prepare($conexao, "SELECT id_imagem, caminho, ordem FROM jogos_imagens WHERE id_jogo = ? ORDER BY ordem ASC, id_imagem ASC");
    mysqli_stmt_bind_param($stmt, 'i', $idJogo);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $imagens = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $imagens[] = $linha;
    }

    return $imagens;
}

function sincronizarCapaJogo(mysqli $conexao, int $idJogo): void
{
    $stmt = mysqli_prepare($conexao, "SELECT caminho FROM jogos_imagens WHERE id_jogo = ? ORDER BY ordem ASC, id_imagem ASC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $idJogo);
    mysqli_stmt_execute($stmt);
    $capa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $caminho = $capa['caminho'] ?? null;

    $stmt = mysqli_prepare($conexao, "UPDATE jogos SET imagem = ? WHERE id_jogo = ?");
    mysqli_stmt_bind_param($stmt, 'si', $caminho, $idJogo);
    mysqli_stmt_execute($stmt);
}

function reordenarSequencialmente(mysqli $conexao, array $imagens): void
{
    foreach ($imagens as $posicao => $imagem) {
        $stmt = mysqli_prepare($conexao, "UPDATE jogos_imagens SET ordem = ? WHERE id_imagem = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $posicao, $imagem['id_imagem']);
        mysqli_stmt_execute($stmt);
    }
}

function adicionarImagemJogo(mysqli $conexao, int $idJogo, string $caminho): void
{
    $stmt = mysqli_prepare($conexao, "SELECT COALESCE(MAX(ordem), -1) + 1 AS proxima FROM jogos_imagens WHERE id_jogo = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idJogo);
    mysqli_stmt_execute($stmt);
    $proxima = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['proxima'];

    $stmt = mysqli_prepare($conexao, "INSERT INTO jogos_imagens (id_jogo, caminho, ordem) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isi', $idJogo, $caminho, $proxima);
    mysqli_stmt_execute($stmt);

    sincronizarCapaJogo($conexao, $idJogo);
}

function removerImagemJogo(mysqli $conexao, int $idJogo, int $idImagem): void
{
    $stmt = mysqli_prepare($conexao, "SELECT caminho FROM jogos_imagens WHERE id_imagem = ? AND id_jogo = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $idImagem, $idJogo);
    mysqli_stmt_execute($stmt);
    $imagem = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$imagem) {
        return;
    }

    $stmt = mysqli_prepare($conexao, "DELETE FROM jogos_imagens WHERE id_imagem = ? AND id_jogo = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $idImagem, $idJogo);
    mysqli_stmt_execute($stmt);

    if (!empty($imagem['caminho']) && !str_starts_with($imagem['caminho'], 'http')) {
        $caminhoArquivo = '../' . $imagem['caminho'];
        if (is_file($caminhoArquivo)) {
            unlink($caminhoArquivo);
        }
    }

    reordenarSequencialmente($conexao, listarImagensJogo($conexao, $idJogo));
    sincronizarCapaJogo($conexao, $idJogo);
}

function definirCapaJogo(mysqli $conexao, int $idJogo, int $idImagem): void
{
    $imagens = listarImagensJogo($conexao, $idJogo);

    $escolhida = null;
    $restante = [];

    foreach ($imagens as $imagem) {
        if ((int) $imagem['id_imagem'] === $idImagem) {
            $escolhida = $imagem;
        } else {
            $restante[] = $imagem;
        }
    }

    if (!$escolhida) {
        return;
    }

    reordenarSequencialmente($conexao, array_merge([$escolhida], $restante));
    sincronizarCapaJogo($conexao, $idJogo);
}

function moverImagemJogo(mysqli $conexao, int $idJogo, int $idImagem, string $direcao): void
{
    $imagens = listarImagensJogo($conexao, $idJogo);

    $posicaoAtual = null;
    foreach ($imagens as $indice => $imagem) {
        if ((int) $imagem['id_imagem'] === $idImagem) {
            $posicaoAtual = $indice;
            break;
        }
    }

    if ($posicaoAtual === null) {
        return;
    }

    $posicaoAlvo = $direcao === 'esquerda' ? $posicaoAtual - 1 : $posicaoAtual + 1;

    if ($posicaoAlvo < 0 || $posicaoAlvo >= count($imagens)) {
        return;
    }

    [$imagens[$posicaoAtual], $imagens[$posicaoAlvo]] = [$imagens[$posicaoAlvo], $imagens[$posicaoAtual]];

    reordenarSequencialmente($conexao, $imagens);
    sincronizarCapaJogo($conexao, $idJogo);
}
