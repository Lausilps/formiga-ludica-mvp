<?php

// OAuth2 + upload pro Google Drive pessoal de cada admin. Só 3 chamadas
// REST no total (troca de código, renovação de token, upload multipart) —
// por isso curl puro, no mesmo estilo de config/gemini.php, em vez do
// pacote google/apiclient (que traria Guzzle + centenas de classes geradas
// pra usar 1% delas).

function montarUrlAutorizacaoGoogle(string $state): string
{
    $parametros = [
        'client_id'     => GOOGLE_DRIVE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_DRIVE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/drive.file',
        'access_type'   => 'offline',
        // Força o Google a reemitir refresh_token toda vez — sem isso, uma
        // reconexão depois de um token perdido/revogado volta sem
        // refresh_token e a conexão parece ter funcionado mas não funcionou.
        'prompt'        => 'consent',
        'state'         => $state,
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($parametros);
}

function trocarCodigoPorTokens(string $code): ?array
{
    $body = [
        'code'          => $code,
        'client_id'     => GOOGLE_DRIVE_CLIENT_ID,
        'client_secret' => GOOGLE_DRIVE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_DRIVE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ];

    return chamarTokenEndpointGoogle($body);
}

function renovarAccessToken(string $refreshToken): ?array
{
    $body = [
        'refresh_token' => $refreshToken,
        'client_id'     => GOOGLE_DRIVE_CLIENT_ID,
        'client_secret' => GOOGLE_DRIVE_CLIENT_SECRET,
        'grant_type'    => 'refresh_token',
    ];

    return chamarTokenEndpointGoogle($body);
}

function chamarTokenEndpointGoogle(array $body): ?array
{
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($body),
        CURLOPT_SSL_VERIFYPEER => false, // corrige problema SSL do XAMPP
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw      = curl_exec($ch);
    $resposta = json_decode($raw, true);
    curl_close($ch);

    if (empty($resposta['access_token'])) {
        registrarLog('ERRO', 'Falha ao trocar/renovar token do Google Drive: ' . $raw);
        return null;
    }

    return $resposta;
}

function salvarTokensGoogleDrive(mysqli $conexao, int $idUsuario, string $accessToken, ?string $refreshToken, int $expiresIn, string $escopo): void
{
    $expiraEm = date('Y-m-d H:i:s', time() + $expiresIn);

    if ($refreshToken !== null) {
        $stmt = mysqli_prepare($conexao, "
            INSERT INTO usuarios_google_drive (id_usuario, access_token, refresh_token, token_expira_em, escopo)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                token_expira_em = VALUES(token_expira_em),
                escopo = VALUES(escopo),
                atualizado_em = NOW()
        ");
        mysqli_stmt_bind_param($stmt, 'issss', $idUsuario, $accessToken, $refreshToken, $expiraEm, $escopo);
    } else {
        // Renovação de access_token: mantém o refresh_token já guardado.
        $stmt = mysqli_prepare($conexao, "
            UPDATE usuarios_google_drive
            SET access_token = ?, token_expira_em = ?, atualizado_em = NOW()
            WHERE id_usuario = ?
        ");
        mysqli_stmt_bind_param($stmt, 'ssi', $accessToken, $expiraEm, $idUsuario);
    }

    mysqli_stmt_execute($stmt);
}

function tabelaGoogleDriveExiste(mysqli $conexao): bool
{
    // Se a migração ainda não rodou (ex: código já subiu, SQL ainda não foi
    // executado no Railway), trata como "ninguém conectado" em vez de dar
    // erro fatal — mesmo espírito do fallback em sessaoHelper.php.
    return @mysqli_query($conexao, "SELECT 1 FROM usuarios_google_drive LIMIT 1") !== false;
}

function usuarioTemGoogleDriveConectado(mysqli $conexao, int $idUsuario): bool
{
    if (!tabelaGoogleDriveExiste($conexao)) {
        return false;
    }

    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM usuarios_google_drive WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt)->num_rows > 0;
}

function obterTokenValidoGoogleDrive(mysqli $conexao, int $idUsuario): ?string
{
    if (!tabelaGoogleDriveExiste($conexao)) {
        return null;
    }

    $stmt = mysqli_prepare($conexao, "SELECT access_token, refresh_token, token_expira_em FROM usuarios_google_drive WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
    mysqli_stmt_execute($stmt);
    $linha = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($linha === null) {
        return null;
    }

    // Renova se faltar menos de 1 minuto pra expirar (ou já tiver expirado).
    if (strtotime($linha['token_expira_em']) - time() > 60) {
        return $linha['access_token'];
    }

    $tokens = renovarAccessToken($linha['refresh_token']);

    if ($tokens === null) {
        registrarLog('ERRO', "Falha ao renovar token do Google Drive do usuário id_usuario=$idUsuario.");
        return null;
    }

    salvarTokensGoogleDrive($conexao, $idUsuario, $tokens['access_token'], null, $tokens['expires_in'], '');

    return $tokens['access_token'];
}

function enviarArquivoParaGoogleDrive(string $accessToken, string $caminhoLocal, string $nomeArquivo): string|false
{
    $limite = '-------314159265358979323846';

    $metadados = json_encode(['name' => $nomeArquivo]);
    $conteudoArquivo = file_get_contents($caminhoLocal);

    $corpo = "--$limite\r\n"
           . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
           . "$metadados\r\n"
           . "--$limite\r\n"
           . "Content-Type: application/sql\r\n\r\n"
           . "$conteudoArquivo\r\n"
           . "--$limite--";

    $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            "Content-Type: multipart/related; boundary=$limite",
        ],
        CURLOPT_POSTFIELDS     => $corpo,
        CURLOPT_SSL_VERIFYPEER => false, // corrige problema SSL do XAMPP
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw      = curl_exec($ch);
    $resposta = json_decode($raw, true);
    curl_close($ch);

    if (empty($resposta['id'])) {
        registrarLog('ERRO', 'Falha ao enviar backup pro Google Drive: ' . $raw);
        return false;
    }

    return $resposta['id'];
}
