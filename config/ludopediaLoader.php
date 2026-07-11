<?php

// Local: usa config/ludopedia.php (gitignorado, com credenciais reais).
// Railway (produção): esse arquivo não existe, porque nunca é commitado —
// as credenciais vêm de variáveis de ambiente configuradas no painel.
if (file_exists(__DIR__ . '/ludopedia.php')) {
    require_once __DIR__ . '/ludopedia.php';
} else {
    define('LUDOPEDIA_APP_ID',     getenv('LUDOPEDIA_APP_ID')     ?: '');
    define('LUDOPEDIA_APP_SECRET', getenv('LUDOPEDIA_APP_SECRET') ?: '');
    define('LUDOPEDIA_TOKEN',      getenv('LUDOPEDIA_TOKEN')      ?: '');
    define('LUDOPEDIA_CALLBACK',   getenv('LUDOPEDIA_CALLBACK')   ?: '');

    if (LUDOPEDIA_APP_ID === '' || LUDOPEDIA_TOKEN === '') {
        die('Credenciais da Ludopedia não configuradas. Defina LUDOPEDIA_APP_ID, LUDOPEDIA_APP_SECRET, LUDOPEDIA_TOKEN e LUDOPEDIA_CALLBACK nas variáveis de ambiente do Railway.');
    }
}
