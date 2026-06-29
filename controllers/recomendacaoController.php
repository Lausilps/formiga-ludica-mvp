<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../helpers/logHelper.php';

registrarLog(
    'INFO',
    "Iniciando RAG. Jogadores: $jogadores | Idade: $idade | Tempo: $tempo | Texto: $descricao"
);

// Recebe dados do formulário
$descricao = trim($_POST['descricao_sessao'] ?? '');
$jogadores = (int)($_POST['jogadores'] ?? 4);
$idade     = (int)($_POST['idade'] ?? 10);
$tempo     = (int)($_POST['tempo'] ?? 999);

if (empty($descricao)) {
    header('Location: ../recomendacao.php?erro=1');
    exit;
}

// 1. Monta query semântica
$queryTexto  = "Quero um jogo para: {$descricao}. ";
$queryTexto .= "Somos {$jogadores} jogadores. ";
$queryTexto .= "Idade mínima do grupo: {$idade} anos. ";
if ($tempo < 999) {
    $queryTexto .= "Tempo disponível: até {$tempo} minutos.";
} else {
    $queryTexto .= "Sem restrição de tempo de jogo.";
}

// 2. Gera embedding da query
$queryEmbedding = geminiEmbedding($queryTexto);

if (empty($queryEmbedding)) {
    header('Location: ../recomendacao.php?erro=2');
    exit;
}

// 3. Pré-filtra no banco com filtros estruturados
$sql = "SELECT id_jogo, nome, descricao, imagem, preco,
               min_jogadores, max_jogadores, idade_minima,
               duracao_minutos, dificuldade, embedding
        FROM jogos
        WHERE ativo = 1
          AND embedding IS NOT NULL
          AND min_jogadores <= {$jogadores}
          AND max_jogadores >= {$jogadores}
          AND idade_minima <= {$idade}";

if ($tempo < 999) {
    $sql .= " AND (duracao_minutos <= {$tempo} OR duracao_minutos IS NULL)";
}

registrarLog('INFO', 'Embedding da consulta gerado com sucesso.');

$result = mysqli_query($conexao, $sql);
$jogos  = [];

while ($row = mysqli_fetch_assoc($result)) {
    $embJogo       = json_decode($row['embedding'], true);
    $row['score']  = cosineSimilarity($queryEmbedding, $embJogo);
    $row['embedding'] = null; // libera memória
    $jogos[] = $row;
}

if (empty($jogos)) {
    header('Location: ../recomendacao.php?erro=3');
    exit;
}

// 4. Ordena por similaridade e pega top 8
usort($jogos, fn($a, $b) => $b['score'] <=> $a['score']);
$topJogos = array_slice($jogos, 0, 8);

// 5. Monta contexto pro Gemini
$contexto = "";
foreach ($topJogos as $j) {
    $contexto .= "- {$j['nome']}: {$j['descricao']} ";
    $contexto .= "({$j['min_jogadores']}-{$j['max_jogadores']} jogadores, ";
    $contexto .= "{$j['duracao_minutos']}min, dificuldade: {$j['dificuldade']}, ";
    $contexto .= "idade mínima: {$j['idade_minima']} anos)\n";
}

$prompt = <<<PROMPT
Você é a Formiguinha, assistente especialista em jogos de tabuleiro da Formiga Lúdica, uma locadora de jogos.
Seu jeito é animado, divertido e descontraído — fala como brasileiro mesmo!

Um cliente está buscando jogos com esse perfil:
"{$queryTexto}"

Com base APENAS nos jogos do catálogo abaixo, escolha os 3 que melhor combinam com o pedido.
Para cada um, explique em 2-3 frases descontraídas por que ele é perfeito pra essa situação.

CATÁLOGO:
{$contexto}

Responda SOMENTE em JSON válido, sem texto antes ou depois, sem blocos de código, neste formato:
{
  "intro": "Uma frase animada de introdução personalizada para o cliente",
  "recomendacoes": [
    {
      "nome": "Nome exato do jogo como aparece no catálogo",
      "motivo": "Explicação divertida e personalizada"
    }
  ]
}
PROMPT;

// 6. Chama Gemini
$respostaTexto = geminiChat($prompt);
$respostaTexto = preg_replace('/```json|```/i', '', $respostaTexto);
$resposta      = json_decode(trim($respostaTexto), true);

// 7. Enriquece com dados do banco
$recomendacoes = [];
if (!empty($resposta['recomendacoes'])) {
    foreach ($resposta['recomendacoes'] as $rec) {
        foreach ($topJogos as $j) {
            if (strtolower(trim($j['nome'])) === strtolower(trim($rec['nome']))) {
                $recomendacoes[] = [
                    'nome'          => $j['nome'],
                    'motivo'        => $rec['motivo'],
                    'imagem'        => $j['imagem'],
                    'preco'         => $j['preco'],
                    'duracao'       => $j['duracao_minutos'],
                    'dificuldade'   => $j['dificuldade'],
                    'min_jogadores' => $j['min_jogadores'],
                    'max_jogadores' => $j['max_jogadores'],
                ];
                break;
            }
        }
    }
}

$intro = $resposta['intro'] ?? 'Aqui estão as minhas recomendações para você!';

// 8. Passa dados pra view
require_once __DIR__ . '/../views/jogos/recomendacao.php';