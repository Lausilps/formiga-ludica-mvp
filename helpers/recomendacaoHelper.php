<?php

// Texto rico usado tanto pra gerar embedding em lote (gerarEmbeddings.php)
// quanto na sincronização com a Ludopedia — mantém as duas fontes consistentes.
function montarTextoEmbeddingJogo(array $jogo): string
{
    $texto  = "Jogo: {$jogo['nome']}. ";
    $texto .= "Descrição: {$jogo['descricao']}. ";

    if (!empty($jogo['resumo_regras'])) {
        $texto .= "Regras: {$jogo['resumo_regras']}. ";
    }

    $texto .= "Jogadores: {$jogo['min_jogadores']} a {$jogo['max_jogadores']}. ";
    $texto .= "Idade mínima: {$jogo['idade_minima']} anos. ";
    $texto .= "Duração: {$jogo['duracao_minutos']} minutos. ";

    if (!empty($jogo['dificuldade']) && $jogo['dificuldade'] !== 'nao_informada') {
        $texto .= "Dificuldade: {$jogo['dificuldade']}.";
    }

    return $texto;
}

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

// Usado só quando buscarJogosCandidatos() não acha nada, pra saber se é
// "não tem jogo com esse perfil" ou "tem jogo, mas falta gerar embedding".
function diagnosticarZeroCandidatos($conexao, int $jogadores, int $idade, int $tempo): string
{
    $sql = "SELECT COUNT(*) AS total,
                   SUM(CASE WHEN embedding IS NULL THEN 1 ELSE 0 END) AS sem_embedding
            FROM jogos
            WHERE ativo = 1
              AND min_jogadores <= ?
              AND idade_minima <= ?";

    $tipos  = 'ii';
    $params = [$jogadores, $idade];

    if (!grupoGrande($jogadores)) {
        $sql .= " AND max_jogadores >= ?";
        $tipos .= 'i';
        $params[] = $jogadores;
    }

    if ($tempo < 999) {
        $sql .= " AND (duracao_minutos <= ? OR duracao_minutos IS NULL)";
        $tipos   .= 'i';
        $params[] = $tempo;
    }

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $row       = mysqli_fetch_assoc($resultado);

    $total        = (int)($row['total'] ?? 0);
    $semEmbedding = (int)($row['sem_embedding'] ?? 0);

    if ($total === 0) {
        return "Nenhum jogo ativo no catálogo bate com jogadores={$jogadores}/idade={$idade}/tempo={$tempo} — não é problema de embedding, é filtro sem correspondência mesmo.";
    }

    if ($semEmbedding > 0) {
        return "{$total} jogo(s) bateriam com esse filtro, mas {$semEmbedding} está(ão) SEM embedding gerado — rode 'Atualizar IA' no admin pra esses jogos aparecerem.";
    }

    return "{$total} jogo(s) bateriam com esse filtro e já têm embedding — investigar outra causa.";
}

// Acima desse número de pessoas, não faz sentido exigir um jogo que caiba
// o grupo inteiro numa mesa só — na prática um grupo desse tamanho se
// divide em várias mesas jogando coisas diferentes ao mesmo tempo.
const LIMITE_GRUPO_GRANDE = 16;

function grupoGrande(int $jogadores): bool
{
    return $jogadores > LIMITE_GRUPO_GRANDE;
}

function buscarJogosCandidatos($conexao, int $jogadores, int $idade, int $tempo, array $idsExcluidos = []): array
{
    // Trava extra contra jogo placeholder "SEM NOME (Ludopedia #...)": ele
    // já deveria estar com ativo=0, mas se um bug de sincronização deixar
    // reativado por engano, essa condição impede que vire candidato de
    // recomendação mesmo assim.
    $sql = "SELECT id_jogo, nome, descricao, imagem, preco,
                   min_jogadores, max_jogadores, idade_minima,
                   duracao_minutos, dificuldade, embedding,
                   link_ludopedia, link_tutorial
            FROM jogos
            WHERE ativo = 1
              AND nome NOT LIKE 'SEM NOME (Ludopedia #%'
              AND embedding IS NOT NULL
              AND min_jogadores <= ?
              AND idade_minima <= ?";

    $tipos  = 'ii';
    $params = [$jogadores, $idade];

    // Grupo "normal": exige que o jogo caiba todo mundo (comportamento de
    // sempre). Grupo grande: só exige gente suficiente pra abrir uma mesa
    // (min_jogadores) — sem travar no max_jogadores, que nenhum jogo comum
    // alcança pra grupos assim.
    if (!grupoGrande($jogadores)) {
        $sql .= " AND max_jogadores >= ?";
        $tipos .= 'i';
        $params[] = $jogadores;
    }

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
        $contexto .= "{$j['duracao_minutos']}min, ";

        if (!empty($j['dificuldade']) && $j['dificuldade'] !== 'nao_informada') {
            $contexto .= "dificuldade: {$j['dificuldade']}, ";
        }

        $contexto .= "idade mínima: {$j['idade_minima']} anos)\n";
    }

    return $contexto;
}

function interpretarRespostaGemini(string $respostaTexto, array $topJogos): array
{
    $respostaTexto = preg_replace('/```json|```/i', '', $respostaTexto);
    $resposta      = json_decode(trim($respostaTexto), true);

    $recomendacoes = [];
    $idsUsados     = [];

    if (!empty($resposta['recomendacoes'])) {
        foreach ($resposta['recomendacoes'] as $rec) {
            foreach ($topJogos as $j) {
                if (strtolower(trim($j['nome'])) === strtolower(trim($rec['nome']))) {
                    // Evita que o mesmo jogo apareça repetido, caso o Gemini
                    // devolva o mesmo nome mais de uma vez (ex: só existe 1 candidato).
                    if (in_array($j['id_jogo'], $idsUsados, true)) {
                        break;
                    }
                    $idsUsados[] = $j['id_jogo'];

                    $recomendacoes[] = [
                        'id'                 => $j['id_jogo'],
                        'nome'               => $j['nome'],
                        'motivo'             => $rec['motivo'],
                        'imagem'             => !empty($j['imagem']) ? $j['imagem'] : null,
                        'preco'              => $j['preco'],
                        'duracao'            => $j['duracao_minutos'],
                        'dificuldade'        => $j['dificuldade'],
                        'min_jogadores'      => $j['min_jogadores'],
                        'max_jogadores'      => $j['max_jogadores'],
                        // Mesmo fallback do catálogo: link_ludopedia (jogo
                        // sincronizado) ou, na falta dele, link_tutorial
                        // (jogo manual/OlaClick com o link colado à mão).
                        'link_ver_ludopedia' => $j['link_ludopedia'] ?: ($j['link_tutorial'] ?? ''),
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

function gerarRecomendacoesComRetry(string $prompt, array $topJogos, int $tentativas = 2): array
{
    $resultado = null;

    for ($i = 1; $i <= $tentativas; $i++) {
        $respostaTexto = geminiChat($prompt);
        $resultado     = interpretarRespostaGemini($respostaTexto, $topJogos);

        if (!empty($resultado['recomendacoes'])) {
            return $resultado;
        }

        registrarLog(
            'ALERTA',
            "Tentativa {$i}/{$tentativas} sem recomendações aproveitáveis na resposta do Gemini: {$resultado['respostaBruta']}"
        );
    }

    return $resultado;
}
