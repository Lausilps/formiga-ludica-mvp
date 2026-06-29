<?php
set_time_limit(0); // sem limite de tempo
ini_set('max_execution_time', 0);
// Proteção simples: só roda via terminal ou com token
if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== 'formiga2024') {
        die("Acesso negado. Use ?token=formiga2024");
    }
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';

// Busca jogos sem embedding
$result = mysqli_query($conexao, "
    SELECT id_jogo, nome, descricao, resumo_regras,
           min_jogadores, max_jogadores, idade_minima,
           duracao_minutos, dificuldade
    FROM jogos
    WHERE ativo = 1 AND embedding IS NULL
");

$total = mysqli_num_rows($result);
echo "Gerando embeddings para {$total} jogos...\n\n";

$stmt = mysqli_prepare($conexao, "
    UPDATE jogos
    SET embedding = ?, embedding_atualizado_em = NOW()
    WHERE id_jogo = ?
");

$processados = 0;
$erros = 0;

while ($jogo = mysqli_fetch_assoc($result)) {
    // Monta texto rico do jogo
    $texto  = "Jogo: {$jogo['nome']}. ";
    $texto .= "Descrição: {$jogo['descricao']}. ";

    if (!empty($jogo['resumo_regras'])) {
        $texto .= "Regras: {$jogo['resumo_regras']}. ";
    }

    $texto .= "Jogadores: {$jogo['min_jogadores']} a {$jogo['max_jogadores']}. ";
    $texto .= "Idade mínima: {$jogo['idade_minima']} anos. ";
    $texto .= "Duração: {$jogo['duracao_minutos']} minutos. ";
    $texto .= "Dificuldade: {$jogo['dificuldade']}.";

    $embedding = geminiEmbedding($texto);

    if (!empty($embedding)) {
        $json = json_encode($embedding);
        mysqli_stmt_bind_param($stmt, 'si', $json, $jogo['id_jogo']);
        mysqli_stmt_execute($stmt);
        $processados++;
        echo "✓ [{$processados}/{$total}] {$jogo['nome']}\n";
    } else {
        $erros++;
        echo "✗ Erro em: {$jogo['nome']}\n";
    }

    // Respeita rate limit do Gemini free tier
    usleep(200000); // 0.2s entre chamadas
}

echo "\n✅ Concluído! {$processados} embeddings gerados. {$erros} erros.\n";
mysqli_close($conexao);