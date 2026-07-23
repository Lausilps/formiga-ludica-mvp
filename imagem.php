<?php

// Proxy pras fotos guardadas no Storage Bucket (privado) do Railway.
// jogos.imagem / jogos_imagens.caminho guardam "imagem.php?arquivo=..."
// pra essas fotos, do mesmo jeito que guardavam "uploads/jogos/..." antes —
// as telas de exibição não precisam saber a diferença.

require_once 'config/conexao.php';
require_once 'helpers/storageHelper.php';

$arquivo = $_GET['arquivo'] ?? '';

if ($arquivo === '') {
    http_response_code(404);
    exit;
}

// Esse endpoint é público (sem login) — sem essa checagem, ele serviria
// qualquer objeto do bucket privado pra quem soubesse/adivinhasse a chave,
// não só fotos de jogos de verdade. Só segue se o caminho pedido realmente
// estiver associado a alguma foto cadastrada.
$caminhoEsperado = 'imagem.php?arquivo=' . urlencode($arquivo);

$stmt = mysqli_prepare($conexao, "SELECT 1 FROM jogos_imagens WHERE caminho = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $caminhoEsperado);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_get_result($stmt)->num_rows === 0) {
    http_response_code(404);
    exit;
}

streamArquivoDoBucket($arquivo);
