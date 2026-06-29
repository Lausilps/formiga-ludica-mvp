<?php

require_once '../config/conexao.php';
require_once '../config/gemini.php';
require_once '../helpers/logHelper.php';

$jogadores = $_POST['jogadores'];
$idade = $_POST['idade'];
$tempo = $_POST['tempo'];
$experiencia = $_POST['experiencia'];
$ocasiao = $_POST['ocasiao'];

$sql = "SELECT
            nome,
            descricao,
            preco,
            min_jogadores,
            max_jogadores,
            idade_minima,
            duracao_minutos,
            dificuldade
        FROM jogos
        WHERE ativo = 1
          AND min_jogadores <= '$jogadores'
          AND max_jogadores >= '$jogadores'
          AND idade_minima <= '$idade'
          AND duracao_minutos <= '$tempo'
        ORDER BY nome ASC
        LIMIT 20";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    registrarLog('ERRO', 'Erro ao buscar jogos para recomendação: ' . mysqli_error($conexao));
    die('Erro ao buscar jogos.');
}

$jogos = [];

while ($jogo = mysqli_fetch_assoc($resultado)) {
    $jogos[] = $jogo;
}

if (count($jogos) == 0) {
    echo "Nenhum jogo compatível encontrado.";
    exit;
}

$listaJogos = "";

foreach ($jogos as $jogo) {
    $listaJogos .= "
Nome: {$jogo['nome']}
Descrição: {$jogo['descricao']}
Jogadores: {$jogo['min_jogadores']} a {$jogo['max_jogadores']}
Idade: {$jogo['idade_minima']}+
Duração: {$jogo['duracao_minutos']} minutos
Dificuldade: {$jogo['dificuldade']}
Preço: R$ {$jogo['preco']}
---
";
}

$prompt = "
Você é a Formiguinha da Formiga Lúdica, uma assistente simpática que recomenda jogos de tabuleiro.

O cliente informou:
- Jogadores: $jogadores
- Idade mínima do grupo: $idade
- Tempo disponível: $tempo minutos
- Experiência: $experiencia
- Ocasião: $ocasiao

Com base SOMENTE nos jogos abaixo, escolha os 5 melhores jogos para recomendar.
Explique de forma curta e amigável por que cada jogo combina com o perfil.
Depois, liste os demais jogos compatíveis sem explicação longa.

Jogos disponíveis:
$listaJogos
";

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY;

$dados = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));

$resposta = curl_exec($ch);

if (curl_errno($ch)) {
    registrarLog('ERRO', 'Erro CURL Gemini: ' . curl_error($ch));
    die('Erro ao consultar a IA.');
}

curl_close($ch);

$respostaArray = json_decode($resposta, true);

$textoIA = $respostaArray['candidates'][0]['content']['parts'][0]['text'] ?? 'Não foi possível gerar recomendação.';

registrarLog('INFO', 'Recomendação por Gemini gerada.');

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resultado da Recomendação</title>
    <link rel="stylesheet" href="../assets/css/catalogo.css">
</head>
<body>

<header class="catalogo-topo">
    <div class="info-topo">
        <img src="../assets/img/logo-formiga-ludica.png" alt="Formiga Lúdica" class="logo-topo">

        <div class="texto-topo">
            <span class="titulo-catalogo">RECOMENDAÇÃO</span>
            <p>A Formiguinha escolheu algumas opções para você.</p>
        </div>
    </div>
</header>

<section class="filtros-catalogo">
    <div style="white-space: pre-line; line-height: 1.7;">
        <?= htmlspecialchars($textoIA) ?>
    </div>
</section>

<p style="text-align:center; margin-bottom:40px;">
    <a href="../recomendacao.php">← Fazer nova recomendação</a>
    |
    <a href="../index.php">Voltar ao catálogo</a>
</p>

</body>
</html>