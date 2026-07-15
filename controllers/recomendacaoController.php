<?php
session_start();

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/gemini.php';
require_once __DIR__ . '/../helpers/logHelper.php';
require_once __DIR__ . '/../helpers/recomendacaoHelper.php';
require_once __DIR__ . '/../helpers/jogoHelper.php';

// Recebe dados do formulário
$descricao = trim($_POST['descricao_sessao'] ?? '');
$jogadores = (int)($_POST['jogadores'] ?? 4);
$idade     = (int)($_POST['idade'] ?? 10);
$tempo     = (int)($_POST['tempo'] ?? 999);

$queryDescricao = $descricao;

// Guarda os dados na sessão para repopular o form em caso de erro
$_SESSION['form_recomendacao'] = [
    'descricao_sessao' => $descricao,
    'jogadores'         => $jogadores,
    'idade'             => $idade,
    'tempo'             => $tempo,
];

registrarLog(
    'INFO',
    "Iniciando recomendação RAG | Jogadores: $jogadores | Idade: $idade | Tempo: $tempo | Descrição: $descricao"
);

if (empty($descricao)) {
    header('Location: ../recomendacao_form.php?erro=1');
    exit;
}

try {
    // 1. Monta query semântica
    $queryTexto = montarQueryTextoRecomendacao($descricao, $jogadores, $idade, $tempo);

    // 2. Gera embedding da query
    $queryEmbedding = geminiEmbedding($queryTexto);

    if (empty($queryEmbedding)) {
        registrarLog('ERRO', 'Falha ao gerar embedding da consulta.');
        header('Location: ../recomendacao_form.php?erro=2');
        exit;
    }

    registrarLog('INFO', 'Embedding da consulta gerado com sucesso.');

    // 3. Pré-filtra no banco com filtros estruturados
    $jogos = buscarJogosCandidatos($conexao, $jogadores, $idade, $tempo);

    registrarLog('INFO', 'Quantidade de jogos candidatos encontrados: ' . count($jogos));

    if (empty($jogos)) {
        registrarLog('DIAGNOSTICO', diagnosticarZeroCandidatos($conexao, $jogadores, $idade, $tempo));
        header('Location: ../recomendacao_form.php?erro=3');
        exit;
    }

    // 4. Ordena por similaridade e pega top 12
    $topJogos = rankearJogosPorSimilaridade($jogos, $queryEmbedding, 12);

    registrarLog('INFO', 'Top jogos selecionados: ' . implode(', ', array_column($topJogos, 'nome')));

    // 5. Monta contexto pro Gemini
    $contexto = montarContextoCatalogo($topJogos);
    $qtdMaxima = min(6, count($topJogos));

    $prompt = <<<PROMPT
Você é a Formiguinha, assistente especialista em jogos de tabuleiro da Formiga Lúdica, uma locadora de jogos.
Seu jeito é animado, divertido e descontraído — fala como brasileiro mesmo!

Um cliente está buscando jogos com esse perfil:
"{$queryTexto}"

Com base APENAS nos jogos do catálogo abaixo, escolha até {$qtdMaxima} jogo(s) que melhor combinam com o pedido.
Nunca repita o mesmo jogo mais de uma vez na resposta — se o catálogo abaixo tiver menos de {$qtdMaxima} jogos, devolva só os que existem, sem repetir nenhum.
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

    registrarLog('INFO', 'Enviando prompt para o Gemini.');

    // 6. Chama Gemini (com 1 nova tentativa se a resposta não render recomendações aproveitáveis)
    $resultado = gerarRecomendacoesComRetry($prompt, $topJogos);

    registrarLog('INFO', 'Resposta recebida do Gemini.');

    // 7. Interpreta resposta e enriquece com dados do banco
    if ($resultado['respostaVazia']) {
        registrarLog('ERRO', 'Resposta inválida do Gemini: ' . $resultado['respostaBruta']);
    }

    $recomendacoes = $resultado['recomendacoes'];
    $intro         = $resultado['intro'] ?? 'Aqui estão as minhas recomendações para você!';

    registrarLog('INFO', 'Recomendações finais geradas: ' . count($recomendacoes));

    // 8. Passa dados pra view
    require_once __DIR__ . '/../views/jogos/recomendacao.php';

} catch (Exception $e) {
    if ($e->getMessage() === 'LIMITE_EXCEDIDO') {
        registrarLog('ERRO', 'Limite de uso da API Gemini excedido.');
        header('Location: ../recomendacao_form.php?erro=4');
        exit;
    }

    registrarLog('ERRO', 'Erro inesperado: ' . $e->getMessage());
    header('Location: ../recomendacao_form.php?erro=5');
    exit;
}
