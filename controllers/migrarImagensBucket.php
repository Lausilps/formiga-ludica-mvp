<?php
set_time_limit(0);
ini_set('max_execution_time', 0);

$isCli = php_sapi_name() === 'cli';

// Proteção simples: só roda via terminal ou com token
if (!$isCli) {
    $tokenEsperado = getenv('ADMIN_IMPORT_TOKEN') ?: '';
    $token = $_GET['token'] ?? '';
    if ($tokenEsperado === '' || $token !== $tokenEsperado) {
        die("Acesso negado.");
    }
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../helpers/logHelper.php';
require_once __DIR__ . '/../helpers/jogoImagensHelper.php';

header('Content-Type: text/plain; charset=UTF-8');

// Migra pro bucket:
// - fotos que ainda estão em disco local (uploads/jogos/... de antes do bucket existir)
// - fotos hotlinkadas de fora que NÃO são da Ludopedia (ex: OlaClick — link que
//   pode sumir a qualquer momento, sem aviso, se o catálogo deles mudar)
//
// Fotos da Ludopedia (origem = 'ludopedia', caminho http) ficam como estão,
// de propósito — são mantidas sincronizadas ativamente pela importação.

echo "🐜 Iniciando migração de imagens pro bucket...\n\n";

$resultado = mysqli_query($conexao, "
    SELECT ji.id_imagem, ji.id_jogo, ji.caminho, j.nome, j.origem
    FROM jogos_imagens ji
    JOIN jogos j ON j.id_jogo = ji.id_jogo
");

if (!$resultado) {
    die("Erro ao consultar imagens: " . mysqli_error($conexao));
}

$migradas = 0;
$puladas = 0;
$erros = 0;
$jogosAfetados = [];

while ($linha = mysqli_fetch_assoc($resultado)) {

    $caminho = $linha['caminho'];

    if ($caminho === '' || str_starts_with($caminho, 'imagem.php?arquivo=')) {
        $puladas++;
        continue;
    }

    if (str_starts_with($caminho, 'http') && $linha['origem'] === 'ludopedia') {
        $puladas++;
        continue;
    }

    if (str_starts_with($caminho, 'http')) {
        // Link externo que não é da Ludopedia (OlaClick, ou link colado à mão)
        $conteudo = @file_get_contents($caminho);

        if ($conteudo === false) {
            echo "  ✗ Falha ao baixar: {$linha['nome']} ($caminho)\n";
            registrarLog('ERRO', "Falha ao baixar imagem externa na migração pro bucket: {$linha['nome']} | $caminho");
            $erros++;
            continue;
        }

        $extensao = strtolower(pathinfo(parse_url($caminho, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';

    } else {
        // Arquivo em disco local (de antes do bucket existir)
        $caminhoLocal = __DIR__ . '/../' . $caminho;

        if (!is_file($caminhoLocal)) {
            echo "  ✗ Arquivo local não encontrado: {$linha['nome']} ($caminho)\n";
            $erros++;
            continue;
        }

        $conteudo = file_get_contents($caminhoLocal);
        $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) ?: 'jpg';
    }

    $arquivoTemp = tempnam(sys_get_temp_dir(), 'migr_');
    file_put_contents($arquivoTemp, $conteudo);

    $tipoConteudo = mime_content_type($arquivoTemp) ?: 'image/jpeg';
    $chaveObjeto = 'jogos/' . uniqid('jogo_') . '.' . $extensao;

    $sucesso = enviarArquivoParaBucket($arquivoTemp, $chaveObjeto, $tipoConteudo);
    unlink($arquivoTemp);

    if (!$sucesso) {
        echo "  ✗ Falha ao enviar pro bucket: {$linha['nome']}\n";
        $erros++;
        continue;
    }

    $novoCaminho = 'imagem.php?arquivo=' . urlencode($chaveObjeto);

    $stmt = mysqli_prepare($conexao, "UPDATE jogos_imagens SET caminho = ? WHERE id_imagem = ?");
    mysqli_stmt_bind_param($stmt, 'si', $novoCaminho, $linha['id_imagem']);
    mysqli_stmt_execute($stmt);

    $jogosAfetados[$linha['id_jogo']] = true;

    echo "  ✓ Migrada: {$linha['nome']}\n";
    $migradas++;
}

foreach (array_keys($jogosAfetados) as $idJogo) {
    sincronizarCapaJogo($conexao, (int) $idJogo);
}

$resumo = "Migração pro bucket concluída. Migradas: $migradas | Puladas (Ludopedia ou já migradas): $puladas | Erros: $erros";
echo "\n✅ $resumo\n";
registrarLog('INFO', $resumo);
