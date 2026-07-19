<?php

// Proxy pras fotos guardadas no Storage Bucket (privado) do Railway.
// jogos.imagem / jogos_imagens.caminho guardam "imagem.php?arquivo=..."
// pra essas fotos, do mesmo jeito que guardavam "uploads/jogos/..." antes —
// as telas de exibição não precisam saber a diferença.

require_once 'helpers/storageHelper.php';

$arquivo = $_GET['arquivo'] ?? '';

if ($arquivo === '') {
    http_response_code(404);
    exit;
}

streamArquivoDoBucket($arquivo);
