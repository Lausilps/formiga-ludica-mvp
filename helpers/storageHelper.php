<?php

// __DIR__ em vez de caminho relativo puro: esse helper é chamado a partir
// de profundidades diferentes (controllers/, views/jogos/), e um caminho
// relativo tipo '../vendor/autoload.php' é resolvido pelo PHP com base no
// diretório do script de entrada (que muda a cada chamador), não com base
// nesse arquivo — quebrava a edição de jogo, que fica 2 pastas mais fundo
// que os controllers.
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;

// Envolve o Storage Bucket do Railway (S3-compatível). O bucket é privado —
// não existe link público direto — então toda leitura passa pelo nosso
// próprio proxy (imagem.php), que busca o arquivo aqui e devolve pro
// navegador.
//
// Nomes de variável no padrão AWS (é o que o Railway injetou pra esse
// bucket): AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION,
// AWS_ENDPOINT_URL, AWS_S3_BUCKET_NAME.

function clienteBucket(): S3Client
{
    static $cliente = null;

    if ($cliente === null) {
        $cliente = new S3Client([
            'version' => 'latest',
            'region' => getenv('AWS_DEFAULT_REGION') ?: 'auto',
            'endpoint' => getenv('AWS_ENDPOINT_URL') ?: 'https://storage.railway.app',
            'credentials' => [
                'key' => getenv('AWS_ACCESS_KEY_ID') ?: '',
                'secret' => getenv('AWS_SECRET_ACCESS_KEY') ?: '',
            ],
        ]);
    }

    return $cliente;
}

function nomeBucket(): string
{
    return getenv('AWS_S3_BUCKET_NAME') ?: '';
}

function enviarArquivoParaBucket(string $caminhoLocalTemp, string $chaveObjeto, string $tipoConteudo): bool
{
    if (nomeBucket() === '') {
        registrarLog('ERRO', 'BUCKET não configurado (variável de ambiente vazia) — não deu pra enviar ' . $chaveObjeto);
        return false;
    }

    try {
        clienteBucket()->putObject([
            'Bucket'      => nomeBucket(),
            'Key'         => $chaveObjeto,
            'SourceFile'  => $caminhoLocalTemp,
            'ContentType' => $tipoConteudo,
        ]);

        return true;
    } catch (\Throwable $e) {
        registrarLog('ERRO', 'Falha ao enviar arquivo pro bucket: ' . $e->getMessage());
        return false;
    }
}

function removerArquivoDoBucket(string $chaveObjeto): void
{
    if (nomeBucket() === '') {
        registrarLog('ERRO', 'BUCKET não configurado (variável de ambiente vazia) — não deu pra remover ' . $chaveObjeto);
        return;
    }

    try {
        clienteBucket()->deleteObject([
            'Bucket' => nomeBucket(),
            'Key'    => $chaveObjeto,
        ]);
    } catch (\Throwable $e) {
        registrarLog('ERRO', 'Falha ao remover arquivo do bucket: ' . $e->getMessage());
    }
}

// Usado pelo imagem.php — busca o objeto no bucket e já manda pro navegador.
function streamArquivoDoBucket(string $chaveObjeto): void
{
    if (nomeBucket() === '') {
        http_response_code(500);
        exit;
    }

    try {
        $resultado = clienteBucket()->getObject([
            'Bucket' => nomeBucket(),
            'Key'    => $chaveObjeto,
        ]);
    } catch (\Throwable $e) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . ($resultado['ContentType'] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=604800');
    echo $resultado['Body'];
    exit;
}
