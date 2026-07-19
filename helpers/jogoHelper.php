<?php

function camposObrigatoriosPreenchidos(array $campos): bool
{
    foreach ($campos as $valor) {
        if (empty($valor)) {
            return false;
        }
    }

    return true;
}

function jogoNomeDuplicado($conexao, string $nome, ?int $idExcluir = null): bool
{
    if ($idExcluir !== null) {
        $stmt = mysqli_prepare($conexao, "SELECT id_jogo FROM jogos WHERE nome = ? AND id_jogo <> ?");
        mysqli_stmt_bind_param($stmt, 'si', $nome, $idExcluir);
    } else {
        $stmt = mysqli_prepare($conexao, "SELECT id_jogo FROM jogos WHERE nome = ?");
        mysqli_stmt_bind_param($stmt, 's', $nome);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    return mysqli_num_rows($resultado) > 0;
}

// Redimensiona (se for maior que o necessário) e recomprime em JPEG antes
// de mandar pro bucket — foto de celular sem tratamento facilmente passa
// de 5-8MB, o que deixa o envio lento e come espaço do bucket à toa. Se o
// formato não for reconhecido pelo GD (raro — a maioria é JPEG/PNG/WEBP),
// devolve o arquivo original sem mexer, pra nunca bloquear um upload.
function prepararImagemParaUpload(string $caminhoOrigem, string $nomeOriginal): array
{
    // Sem a extensão GD (ex: ambiente local ainda sem ela habilitada), não
    // dá pra comprimir — manda o arquivo original em vez de travar o upload.
    if (!extension_loaded('gd')) {
        return [
            $caminhoOrigem,
            strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION)) ?: 'jpg',
            mime_content_type($caminhoOrigem) ?: 'application/octet-stream',
        ];
    }

    $info = @getimagesize($caminhoOrigem);

    $criadores = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ];

    if ($info === false || !isset($criadores[$info[2]])) {
        return [
            $caminhoOrigem,
            strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION)) ?: 'jpg',
            mime_content_type($caminhoOrigem) ?: 'application/octet-stream',
        ];
    }

    [$largura, $altura, $tipo] = $info;
    $imagem = @($criadores[$tipo])($caminhoOrigem);

    if (!$imagem) {
        return [
            $caminhoOrigem,
            strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION)) ?: 'jpg',
            mime_content_type($caminhoOrigem) ?: 'application/octet-stream',
        ];
    }

    $larguraMaxima = 1600;

    if ($largura > $larguraMaxima) {
        $novaAltura = (int) round($altura * ($larguraMaxima / $largura));
        $redimensionada = imagecreatetruecolor($larguraMaxima, $novaAltura);
        imagecopyresampled($redimensionada, $imagem, 0, 0, 0, 0, $larguraMaxima, $novaAltura, $largura, $altura);
        imagedestroy($imagem);
        $imagem = $redimensionada;
    }

    $caminhoTemp = tempnam(sys_get_temp_dir(), 'foto_');
    imagejpeg($imagem, $caminhoTemp, 82);
    imagedestroy($imagem);

    return [$caminhoTemp, 'jpg', 'image/jpeg'];
}

function uploadImagemJogo(?array $arquivo): ?string
{
    if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    require_once __DIR__ . '/storageHelper.php';

    [$caminhoFinal, $extensao, $tipoConteudo] = prepararImagemParaUpload($arquivo['tmp_name'], $arquivo['name']);

    $chaveObjeto = 'jogos/' . uniqid('jogo_') . '.' . $extensao;

    $sucesso = enviarArquivoParaBucket($caminhoFinal, $chaveObjeto, $tipoConteudo);

    if ($caminhoFinal !== $arquivo['tmp_name']) {
        @unlink($caminhoFinal);
    }

    if (!$sucesso) {
        return null;
    }

    return 'imagem.php?arquivo=' . urlencode($chaveObjeto);
}

function formatarDificuldade(?string $dificuldade): string
{
    return $dificuldade === 'nao_informada' ? '-' : ucfirst((string)$dificuldade);
}

function redirecionarComDados(string $url, string $erro, array $dados): void
{
    $dados['erro'] = $erro;

    header("Location: $url?" . http_build_query($dados));
    exit;
}
