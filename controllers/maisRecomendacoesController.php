<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../helpers/logHelper.php';

$descricao   = trim($_POST['descricao_sessao'] ?? '');
$jogadores   = (int)($_POST['jogadores'] ?? 4);
$idade       = (int)($_POST['idade'] ?? 10);
$tempo       = (int)($_POST['tempo'] ?? 999);
$idsExibidos = $_POST['ids_exibidos'] ?? []; // array de IDs já mostrados

if (empty($descricao) || empty($idsExibidos)) {
    echo json_encode(['erro' => 'parametros_invalidos']);
    exit;
}

$idsExibidos = array_map('intval', $idsExibidos);

try {
    $queryTexto  = "Quero um jogo para: {$descricao}. ";
    $queryTexto .= "Somos {$jogadores} jogadores. ";
    $queryTexto .= "Idade mínima do grupo: {$idade} anos. ";
    if ($tempo < 999) {
        $queryTexto .= "Tempo disponível: até {$tempo} minutos.";
    } else {
        $queryTexto .= "Sem restrição de tempo de jogo.";
    }

    $queryEmbedding = geminiEmbedding($queryTexto);

    if (empty($queryEmbedding)) {
        echo json_encode(['erro' => 'falha_embedding']);
        exit;
    }

    // Monta a exclusão dos IDs já mostrados
    $idsString = implode(',', $idsExibidos);

    $sql = "SELECT id_jogo, nome, descricao, imagem, preco,
                   min_jogadores, max_jogadores, idade_minima,
                   duracao_minutos, dificuldade, embedding
            FROM jogos
            WHERE ativo = 1
              AND embedding IS NOT NULL
              AND min_jogadores <= {$jogadores}
              AND max_jogadores >= {$jogadores}
              AND idade_minima <= {$idade}
              AND id_jogo NOT IN ({$idsString})";

    if ($tempo < 999) {
        $sql .= " AND (duracao_minutos <= {$tempo} OR duracao_minutos IS NULL)";
    }

    $result = mysqli_query($conexao, $sql);
    $jogos  = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $embJogo          = json_decode($row['embedding'], true);
        $row['score']     = cosineSimilarity($queryEmbedding, $embJogo);
        $row['embedding'] = null;
        $jogos[] = $row;
    }

    if (empty($jogos)) {
        echo json_encode(['fim' => true]);
        exit;
    }

    usort($jogos, fn($a, $b) => $b['score'] <=> $a['score']);
    $topJogos = array_slice($jogos, 0, 8);

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

Um cliente já viu algumas recomendações e pediu mais opções para esse perfil:
"{$queryTexto}"

Com base APENAS nos jogos do catálogo abaixo (que ainda não foram mostrados a ele), escolha até 4 que melhor combinam com o pedido.
Para cada um, explique em 2-3 frases descontraídas por que ele é perfeito pra essa situação.

CATÁLOGO:
{$contexto}

Responda SOMENTE em JSON válido, sem texto antes ou depois, sem blocos de código, neste formato:
{
  "recomendacoes": [
    {
      "nome": "Nome exato do jogo como aparece no catálogo",
      "motivo": "Explicação divertida e personalizada"
    }
  ]
}
PROMPT;

    $respostaTexto = geminiChat($prompt);
    $respostaTexto = preg_replace('/```json|```/i', '', $respostaTexto);
    $resposta      = json_decode(trim($respostaTexto), true);

    $recomendacoes = [];
    if (!empty($resposta['recomendacoes'])) {
        foreach ($resposta['recomendacoes'] as $rec) {
            foreach ($topJogos as $j) {
                if (strtolower(trim($j['nome'])) === strtolower(trim($rec['nome']))) {
                    $recomendacoes[] = [
                        'id'            => $j['id_jogo'],
                        'nome'          => $j['nome'],
                        'motivo'        => $rec['motivo'],
                        'imagem'        => !empty($j['imagem']) ? $j['imagem'] : null,
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

    if (empty($recomendacoes)) {
        echo json_encode(['fim' => true]);
        exit;
    }

    echo json_encode(['recomendacoes' => $recomendacoes]);

} catch (Exception $e) {
    if ($e->getMessage() === 'LIMITE_EXCEDIDO') {
        echo json_encode(['erro' => 'limite_excedido']);
        exit;
    }
    echo json_encode(['erro' => 'erro_inesperado']);
}