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

header('Content-Type: text/plain; charset=UTF-8');

// Mesma lógica do INSERT que rodamos pelo console do Railway (que travou
// duas vezes por volta de 100 linhas, provavelmente por limite/timeout do
// console) — só que rodando via PHP/mysqli, sem esse limite.
// Só adiciona jogo que ainda não tem NENHUMA linha em jogos_imagens.

echo "🐜 Completando a galeria com os jogos que ainda não têm nenhuma foto cadastrada...\n\n";

$sql = "
    INSERT INTO jogos_imagens (id_jogo, caminho, ordem)
    SELECT j.id_jogo, j.imagem, 0
    FROM jogos j
    LEFT JOIN jogos_imagens ji ON ji.id_jogo = j.id_jogo
    WHERE j.imagem IS NOT NULL AND j.imagem <> ''
      AND ji.id_imagem IS NULL
";

if (!mysqli_query($conexao, $sql)) {
    die("Erro ao completar a galeria: " . mysqli_error($conexao));
}

$inseridos = mysqli_affected_rows($conexao);

$resultado = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM jogos_imagens");
$total = mysqli_fetch_assoc($resultado)['total'] ?? '?';

$resumo = "Galeria completada. Linhas novas inseridas: $inseridos | Total na galeria agora: $total";
echo "✅ $resumo\n";
registrarLog('INFO', $resumo);
