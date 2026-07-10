<?php
set_time_limit(0); // sem limite de tempo
ini_set('max_execution_time', 0);

$isCli = php_sapi_name() === 'cli';

// Proteção simples: só roda via terminal ou com token
if (!$isCli) {
    $token = $_GET['token'] ?? '';
    if ($token !== 'formiga2024') {
        die("Acesso negado. Use ?token=formiga2024");
    }
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../helpers/recomendacaoHelper.php';

// Via CLI processa tudo de uma vez (sem risco de timeout de servidor web).
// Via HTTP processa só um lote pequeno por requisição — quem chama (o botão
// "Atualizar IA" no admin) encadeia as chamadas até não sobrar nada.
$lote = $isCli ? 100000 : 8;

$result = mysqli_query($conexao, "
    SELECT id_jogo, nome, descricao, resumo_regras,
           min_jogadores, max_jogadores, idade_minima,
           duracao_minutos, dificuldade
    FROM jogos
    WHERE ativo = 1 AND embedding IS NULL
    LIMIT {$lote}
");

$total = mysqli_num_rows($result);

if ($isCli) {
    echo "Gerando embeddings para {$total} jogos...\n\n";
}

$stmt = mysqli_prepare($conexao, "
    UPDATE jogos
    SET embedding = ?, embedding_atualizado_em = NOW()
    WHERE id_jogo = ?
");

$processados = 0;
$erros = 0;

while ($jogo = mysqli_fetch_assoc($result)) {
    $texto = montarTextoEmbeddingJogo($jogo);
    $embedding = geminiEmbedding($texto);

    if (!empty($embedding)) {
        $json = json_encode($embedding);
        mysqli_stmt_bind_param($stmt, 'si', $json, $jogo['id_jogo']);
        mysqli_stmt_execute($stmt);
        $processados++;
        if ($isCli) echo "✓ [{$processados}/{$total}] {$jogo['nome']}\n";
    } else {
        $erros++;
        if ($isCli) echo "✗ Erro em: {$jogo['nome']}\n";
    }

    // Respeita rate limit do Gemini free tier
    sleep(1); // 1 segundo entre cada chamada
}

$restantesResult = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM jogos WHERE ativo = 1 AND embedding IS NULL");
$restantes = (int)(mysqli_fetch_assoc($restantesResult)['total'] ?? 0);

if ($isCli) {
    echo "\n✅ Concluído! {$processados} embeddings gerados. {$erros} erros. Restantes sem embedding: {$restantes}\n";
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'processados' => $processados,
        'erros'       => $erros,
        'restantes'   => $restantes,
    ]);
}

mysqli_close($conexao);
