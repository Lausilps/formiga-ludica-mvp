<?php

// Local: usa config/googleDrive.php (gitignorado, com credenciais reais).
// Railway (produção): esse arquivo não existe, porque nunca é commitado —
// as credenciais vêm de variáveis de ambiente configuradas no painel.
if (file_exists(__DIR__ . '/googleDrive.php')) {
    require_once __DIR__ . '/googleDrive.php';
} else {
    define('GOOGLE_DRIVE_CLIENT_ID',     getenv('GOOGLE_DRIVE_CLIENT_ID')     ?: '');
    define('GOOGLE_DRIVE_CLIENT_SECRET', getenv('GOOGLE_DRIVE_CLIENT_SECRET') ?: '');
    define('GOOGLE_DRIVE_REDIRECT_URI',  getenv('GOOGLE_DRIVE_REDIRECT_URI')  ?: '');

    if (GOOGLE_DRIVE_CLIENT_ID === '' || GOOGLE_DRIVE_CLIENT_SECRET === '') {
        die('Credenciais do Google Drive não configuradas. Defina GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET e GOOGLE_DRIVE_REDIRECT_URI nas variáveis de ambiente do Railway.');
    }
}
