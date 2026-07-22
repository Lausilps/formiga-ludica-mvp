<?php

require_once '../config/conexao.php';
require_once '../helpers/authHelper.php';
require_once '../config/googleDriveLoader.php';
require_once '../helpers/googleDriveHelper.php';

protegerAdmin('../login.php');

$state = bin2hex(random_bytes(16));
$_SESSION['google_drive_oauth_state'] = $state;

header('Location: ' . montarUrlAutorizacaoGoogle($state));
exit;
