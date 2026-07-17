<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/authHelper.php';

protegerAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_jogo'])) {
    registrarLog('ERRO', 'Tentativa de exclusão sem informar ID do jogo.');
    die('Jogo não informado.');
}

$id_jogo = (int) $_POST['id_jogo'];

$stmt = mysqli_prepare($conexao, "SELECT nome, imagem FROM jogos WHERE id_jogo = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_jogo);
mysqli_stmt_execute($stmt);
$jogo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$jogo) {
    registrarLog('ERRO', "Tentativa de excluir jogo inexistente. ID: $id_jogo");
    header("Location: ../views/jogos/listar.php?erro=nao_encontrado");
    exit;
}

$stmt = mysqli_prepare($conexao, "DELETE FROM jogos_categorias WHERE id_jogo = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_jogo);
mysqli_stmt_execute($stmt);

$stmt = mysqli_prepare($conexao, "DELETE FROM jogos WHERE id_jogo = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_jogo);

if (mysqli_stmt_execute($stmt)) {

    if (!empty($jogo['imagem']) && !str_starts_with($jogo['imagem'], 'http')) {
        $caminhoImagem = '../' . $jogo['imagem'];
        if (is_file($caminhoImagem)) {
            unlink($caminhoImagem);
        }
    }

    registrarLog('INFO', "Jogo excluído: {$jogo['nome']} | ID: $id_jogo");
    header("Location: ../views/jogos/listar.php?sucesso=excluido");
    exit;

} else {

    $erro = mysqli_error($conexao);

    registrarLog(
        'ERRO',
        "Falha ao excluir jogo '{$jogo['nome']}' | ID: $id_jogo | Erro: $erro"
    );

    header("Location: ../views/jogos/editar.php?id=$id_jogo&erro=falha_exclusao");
    exit;
}
