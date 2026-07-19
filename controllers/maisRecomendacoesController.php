<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../helpers/logHelper.php';
require_once __DIR__ . '/../helpers/recomendacaoHelper.php';
require_once __DIR__ . '/../helpers/sessaoHelper.php';
iniciarSessaoPersistente();

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
    $queryTexto = montarQueryTextoRecomendacao($descricao, $jogadores, $idade, $tempo);

    $queryEmbedding = geminiEmbedding($queryTexto);

    if (empty($queryEmbedding)) {
        echo json_encode(['erro' => 'falha_embedding']);
        exit;
    }

    $jogos = buscarJogosCandidatos($conexao, $jogadores, $idade, $tempo, $idsExibidos);

    if (empty($jogos)) {
        echo json_encode(['fim' => true]);
        exit;
    }

    $topJogos = rankearJogosPorSimilaridade($jogos, $queryEmbedding, 8);

    $contexto = montarContextoCatalogo($topJogos);
    $qtdMaxima = min(4, count($topJogos));

    // Mesma observação do fluxo principal (recomendacaoController.php):
    // grupo grande não cabe todo mundo num jogo só, então os "motivo"
    // devem deixar isso claro em vez de fingir que serve pro grupo inteiro.
    $observacaoGrupoGrande = grupoGrande($jogadores)
        ? "\nATENÇÃO: o grupo tem {$jogadores} pessoas — nenhum jogo de tabuleiro comum comporta todo mundo numa partida só. Os jogos abaixo servem pra mesas menores dentro desse grupão. Mencione nos \"motivo\" quantas pessoas cada jogo comporta por mesa."
        : '';

    $prompt = <<<PROMPT
Você é a Formiguinha, assistente especialista em jogos de tabuleiro da Formiga Lúdica, uma locadora de jogos.
Seu jeito é animado, divertido e descontraído — fala como brasileiro mesmo!

Um cliente já viu algumas recomendações e pediu mais opções para esse perfil:
"{$queryTexto}"
{$observacaoGrupoGrande}

Com base APENAS nos jogos do catálogo abaixo (que ainda não foram mostrados a ele), escolha até {$qtdMaxima} jogo(s) que melhor combinam com o pedido.
Nunca repita o mesmo jogo mais de uma vez na resposta — se o catálogo abaixo tiver menos de {$qtdMaxima} jogos, devolva só os que existem, sem repetir nenhum.
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

    $resultado     = gerarRecomendacoesComRetry($prompt, $topJogos);
    $recomendacoes = $resultado['recomendacoes'];

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
