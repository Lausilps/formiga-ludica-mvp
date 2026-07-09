<?php

function montarQueryTextoRecomendacao(string $descricao, int $jogadores, int $idade, int $tempo): string
{
    $queryTexto  = "Quero um jogo para: {$descricao}. ";
    $queryTexto .= "Somos {$jogadores} jogadores. ";
    $queryTexto .= "Idade mínima do grupo: {$idade} anos. ";
    $queryTexto .= $tempo < 999
        ? "Tempo disponível: até {$tempo} minutos."
        : "Sem restrição de tempo de jogo.";

    return $queryTexto;
}

function buscarJogosCandidatos($conexao, int $jogadores, int $idade, int $tempo, array $idsExcluidos = []): array
{
    $sql = "SELECT id_jogo, nome, descricao, imagem, preco,
                   min_jogadores, max_jogadores, idade_minima,
                   duracao_minutos, dificuldade, embedding
            FROM jogos
            WHERE ativo = 1
              AND embedding IS NOT NULL
              AND min_jogadores <= ?
              AND max_jogadores >= ?
              AND idade_minima <= ?";

    $tipos  = 'iii';
    $params = [$jogadores, $jogadores, $idade];

    if ($tempo < 999) {
        $sql .= " AND (duracao_minutos <= ? OR duracao_minutos IS NULL)";
        $tipos   .= 'i';
        $params[] = $tempo;
    }

    if (!empty($idsExcluidos)) {
        $placeholders = implode(',', array_fill(0, count($idsExcluidos), '?'));
        $sql .= " AND id_jogo NOT IN ({$placeholders})";

        foreach ($idsExcluidos as $id) {
            $tipos   .= 'i';
            $params[] = (int) $id;
        }
    }

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $jogos = [];

    while ($row = mysqli_fetch_assoc($resultado)) {
        $jogos[] = $row;
    }

    return $jogos;
}

function rankearJogosPorSimilaridade(array $jogos, array $queryEmbedding, int $limite): array
{
    foreach ($jogos as &$jogo) {
        $embJogo           = json_decode($jogo['embedding'], true);
        $jogo['score']      = cosineSimilarity($queryEmbedding, $embJogo);
        $jogo['embedding']  = null;
    }
    unset($jogo);

    usort($jogos, fn($a, $b) => $b['score'] <=> $a['score']);

    return array_slice($jogos, 0, $limite);
}

function montarContextoCatalogo(array $topJogos): string
{
    $contexto = "";

    foreach ($topJogos as $j) {
        $contexto .= "- {$j['nome']}: {$j['descricao']} ";
        $contexto .= "({$j['min_jogadores']}-{$j['max_jogadores']} jogadores, ";
        $contexto .= "{$j['duracao_minutos']}min, dificuldade: {$j['dificuldade']}, ";
        $contexto .= "idade mínima: {$j['idade_minima']} anos)\n";
    }

    return $contexto;
}

function interpretarRespostaGemini(string $respostaTexto, array $topJogos): array
{
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

    return [
        'intro'         => $resposta['intro'] ?? null,
        'recomendacoes' => $recomendacoes,
        'respostaBruta' => $respostaTexto,
        'respostaVazia' => empty($resposta),
    ];
}
