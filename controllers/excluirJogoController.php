<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/authHelper.php';
require_once '../helpers/jogoImagensHelper.php';

protegerAdmin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_jogo'])) {
    registrarLog('ERRO', 'Tentativa de exclusão sem informar ID do jogo.');
    die('Jogo não informado.');
}

$id_jogo = (int) $_POST['id_jogo'];
$paginaOrigem = (int) ($_POST['pagina'] ?? 1);
$buscaOrigem = $_POST['busca'] ?? '';

$stmt = mysqli_prepare($conexao, "SELECT nome FROM jogos WHERE id_jogo = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_jogo);
mysqli_stmt_execute($stmt);
$jogo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$jogo) {
    registrarLog('ERRO', "Tentativa de excluir jogo inexistente. ID: $id_jogo");
    $paramsRetorno = http_build_query(['erro' => 'nao_encontrado', 'pagina' => $paginaOrigem, 'busca' => $buscaOrigem]);
    header("Location: ../views/jogos/listar.php?$paramsRetorno");
    exit;
}

// Guarda a galeria inteira antes de excluir — jogos_imagens tem
// ON DELETE CASCADE, então depois do DELETE em jogos essas linhas já
// não existem mais no banco (mas os arquivos ainda estariam órfãos no
// bucket se a gente não limpar aqui antes).
$imagensGaleria = listarImagensJogo($conexao, $id_jogo);

$stmt = mysqli_prepare($conexao, "DELETE FROM jogos_categorias WHERE id_jogo = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_jogo);
mysqli_stmt_execute($stmt);

$stmt = mysqli_prepare($conexao, "DELETE FROM jogos WHERE id_jogo = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_jogo);

if (mysqli_stmt_execute($stmt)) {

    foreach ($imagensGaleria as $imagem) {
        removerArquivoDeCaminho($imagem['caminho'] ?? '');
    }

    registrarLog('INFO', "Jogo excluído: {$jogo['nome']} | ID: $id_jogo");
    $paramsRetorno = http_build_query(['sucesso' => 'excluido', 'pagina' => $paginaOrigem, 'busca' => $buscaOrigem]);
    header("Location: ../views/jogos/listar.php?$paramsRetorno");
    exit;

} else {

    $erro = mysqli_error($conexao);

    registrarLog(
        'ERRO',
        "Falha ao excluir jogo '{$jogo['nome']}' | ID: $id_jogo | Erro: $erro"
    );

    $paramsRetorno = http_build_query(['id' => $id_jogo, 'erro' => 'falha_exclusao', 'pagina' => $paginaOrigem, 'busca' => $buscaOrigem]);
    header("Location: ../views/jogos/editar.php?$paramsRetorno");
    exit;
}
