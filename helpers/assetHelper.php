<?php

// Gera "?v=<data de modificação do arquivo>" pra anexar nos links de CSS/JS.
// Assim, a cada deploy que muda um desses arquivos, o navegador e a
// Cloudflare enxergam uma URL diferente e buscam a versão nova sozinhos,
// sem precisar de purge manual de cache.
function assetVersao(string $caminhoRaiz): string
{
    $caminhoAbsoluto = dirname(__DIR__) . '/' . ltrim($caminhoRaiz, '/');
    $versao = file_exists($caminhoAbsoluto) ? filemtime($caminhoAbsoluto) : time();

    return '?v=' . $versao;
}
