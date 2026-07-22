<?php

require_once '../config/conexao.php';
require_once '../helpers/authHelper.php';
require_once '../helpers/logHelper.php';
require_once '../config/googleDriveLoader.php';
require_once '../helpers/googleDriveHelper.php';

protegerAdmin('../login.php');

if (isset($_GET['error'])) {
    registrarLog('INFO', "Conexão com Google Drive cancelada pelo usuário: {$_SESSION['email']}");
    header('Location: ../views/jogos/listar.php?erro=google_drive_negado');
    exit;
}

$stateEsperado = $_SESSION['google_drive_oauth_state'] ?? null;
unset($_SESSION['google_drive_oauth_state']); // state é de uso único

if ($stateEsperado === null || !hash_equals($stateEsperado, $_GET['state'] ?? '')) {
    registrarLog('SEGURANCA', "Callback do Google Drive com state inválido/ausente (usuário: {$_SESSION['email']}).");
    header('Location: ../views/jogos/listar.php?erro=google_drive_state_invalido');
    exit;
}

$tokens = trocarCodigoPorTokens($_GET['code'] ?? '');

if ($tokens === null || empty($tokens['refresh_token'])) {
    registrarLog('ERRO', "Falha ao trocar código por token do Google Drive (usuário: {$_SESSION['email']}).");
    header('Location: ../views/jogos/listar.php?erro=google_drive_falha');
    exit;
}

salvarTokensGoogleDrive(
    $conexao,
    $_SESSION['id_usuario'],
    $tokens['access_token'],
    $tokens['refresh_token'],
    $tokens['expires_in'],
    'https://www.googleapis.com/auth/drive.file'
);

registrarLog('INFO', "Google Drive conectado: {$_SESSION['email']}");
header('Location: ../views/jogos/listar.php?sucesso=google_drive_conectado');
exit;
