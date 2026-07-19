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

function uploadImagemJogo(?array $arquivo): ?string
{
    if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    require_once __DIR__ . '/storageHelper.php';

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nomeArquivo = uniqid('jogo_') . '.' . $extensao;
    $chaveObjeto = 'jogos/' . $nomeArquivo;

    $tipoConteudo = mime_content_type($arquivo['tmp_name']) ?: 'application/octet-stream';

    if (!enviarArquivoParaBucket($arquivo['tmp_name'], $chaveObjeto, $tipoConteudo)) {
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
