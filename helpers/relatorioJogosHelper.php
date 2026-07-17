<?php

// Compartilhado entre gerarRelatorioJogosPdf.php e gerarRelatorioJogosCsv.php
// pra manter os dois formatos de relatório sempre com os mesmos filtros e a
// mesma detecção de possíveis duplicados.

function filtrosRelatorioJogosDoGet(array $get): array
{
    return [
        'nome' => $get['nome'] ?? '',
        'preco_de' => $get['preco_de'] ?? '',
        'preco_ate' => $get['preco_ate'] ?? '',
        'duracao_de' => $get['duracao_de'] ?? '',
        'duracao_ate' => $get['duracao_ate'] ?? '',
        'jogadores_de' => $get['jogadores_de'] ?? '',
        'jogadores_ate' => $get['jogadores_ate'] ?? '',
        'idade_de' => $get['idade_de'] ?? '',
        'idade_ate' => $get['idade_ate'] ?? '',
        'dificuldade' => $get['dificuldade'] ?? '',
        'origem' => $get['origem'] ?? '',
        'importado_de' => $get['importado_de'] ?? '',
        'importado_ate' => $get['importado_ate'] ?? '',
        'mostrar_inativos' => isset($get['mostrar_inativos']),
        'somente_incompletos' => isset($get['somente_incompletos']),
        'somente_duplicados' => isset($get['somente_duplicados']),
    ];
}

function dataValidaRelatorio(string $data): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

function buscarJogosRelatorio(mysqli $conexao, array $filtros): ?array
{
    $where = [];

    if ($filtros['nome'] !== '') {
        $nome = mysqli_real_escape_string($conexao, $filtros['nome']);
        $where[] = "nome LIKE '%$nome%'";
    }

    if ($filtros['preco_de'] !== '') $where[] = "preco >= " . (float) $filtros['preco_de'];
    if ($filtros['preco_ate'] !== '') $where[] = "preco <= " . (float) $filtros['preco_ate'];

    if ($filtros['duracao_de'] !== '') $where[] = "duracao_minutos >= " . (int) $filtros['duracao_de'];
    if ($filtros['duracao_ate'] !== '') $where[] = "duracao_minutos <= " . (int) $filtros['duracao_ate'];

    if ($filtros['jogadores_de'] !== '') $where[] = "max_jogadores >= " . (int) $filtros['jogadores_de'];
    if ($filtros['jogadores_ate'] !== '') $where[] = "min_jogadores <= " . (int) $filtros['jogadores_ate'];

    if ($filtros['idade_de'] !== '') $where[] = "idade_minima >= " . (int) $filtros['idade_de'];
    if ($filtros['idade_ate'] !== '') $where[] = "idade_minima <= " . (int) $filtros['idade_ate'];

    if ($filtros['dificuldade'] !== '') {
        $dificuldade = mysqli_real_escape_string($conexao, $filtros['dificuldade']);
        $where[] = "dificuldade = '$dificuldade'";
    }

    if ($filtros['origem'] === 'manual' || $filtros['origem'] === 'ludopedia') {
        $where[] = "origem = '{$filtros['origem']}'";
    }

    if ($filtros['importado_de'] !== '' && dataValidaRelatorio($filtros['importado_de'])) {
        $where[] = "criado_em >= '{$filtros['importado_de']} 00:00:00'";
    }

    if ($filtros['importado_ate'] !== '' && dataValidaRelatorio($filtros['importado_ate'])) {
        $where[] = "criado_em <= '{$filtros['importado_ate']} 23:59:59'";
    }

    if (!$filtros['mostrar_inativos']) {
        $where[] = "ativo = 1";
    }

    if ($filtros['somente_incompletos']) {
        $where[] = "(
            descricao IS NULL OR descricao = ''
            OR preco IS NULL OR preco = 0
            OR min_jogadores IS NULL
            OR max_jogadores IS NULL
            OR idade_minima IS NULL
            OR duracao_minutos IS NULL
        )";
    }

    $whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $resultado = mysqli_query($conexao, "SELECT * FROM jogos $whereSql ORDER BY nome ASC");

    if (!$resultado) {
        return null;
    }

    $jogos = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $jogos[] = $linha;
    }

    marcarJogosDuplicados($jogos);

    if ($filtros['somente_duplicados']) {
        $jogos = array_values(array_filter($jogos, fn($jogo) => $jogo['duplicado']));
    }

    return $jogos;
}

// Marca como possível duplicado quando outro jogo do resultado tem um nome
// muito parecido (não precisa ser idêntico — pega acento, "de" a mais, etc.),
// quando um nome é o outro com um sufixo de edição/ano na frente (ex:
// "Acquire" vs "Acquire (2023 Edition)" — o similar_text() sozinho não pega
// esse caso: nome base curto + sufixo comprido derruba o percentual), ou
// quando têm a mesma descrição.
const LIMIAR_SIMILARIDADE_NOME = 85.0;

function normalizarNomeJogo(string $nome): string
{
    $nome = iconv('UTF-8', 'ASCII//TRANSLIT', $nome) ?: $nome;
    $nome = mb_strtolower($nome);
    $nome = preg_replace('/[^a-z0-9 ]+/', ' ', $nome);
    $nome = preg_replace('/\s+/', ' ', trim($nome));
    return $nome;
}

// Verifica se $curto é o começo de $longo terminando em fronteira de palavra
// (não um trecho solto no meio). Exige tamanho mínimo pra não marcar nomes
// curtos genéricos (ex: "Go") como prefixo de qualquer coisa.
function nomeEhPrefixoDoOutro(string $curto, string $longo): bool
{
    if ($curto === '' || $curto === $longo || mb_strlen($curto) < 4) {
        return false;
    }

    return (bool) preg_match('/^' . preg_quote($curto, '/') . '(\s|$)/', $longo);
}

function marcarJogosDuplicados(array &$jogos): void
{
    $nomesNormalizados = array_map(fn($jogo) => normalizarNomeJogo($jogo['nome'] ?? ''), $jogos);

    $contagemDescricao = [];
    foreach ($jogos as $jogo) {
        $chaveDescricao = trim($jogo['descricao'] ?? '');
        if ($chaveDescricao !== '') {
            $contagemDescricao[$chaveDescricao] = ($contagemDescricao[$chaveDescricao] ?? 0) + 1;
        }
    }

    $total = count($jogos);
    $duplicadoPorIndice = array_fill(0, $total, false);

    for ($i = 0; $i < $total; $i++) {
        if ($nomesNormalizados[$i] === '') continue;

        for ($j = $i + 1; $j < $total; $j++) {
            if ($nomesNormalizados[$j] === '') continue;

            similar_text($nomesNormalizados[$i], $nomesNormalizados[$j], $percentual);

            $nomeParecido = $percentual >= LIMIAR_SIMILARIDADE_NOME
                || nomeEhPrefixoDoOutro($nomesNormalizados[$i], $nomesNormalizados[$j])
                || nomeEhPrefixoDoOutro($nomesNormalizados[$j], $nomesNormalizados[$i]);

            if ($nomeParecido) {
                $duplicadoPorIndice[$i] = true;
                $duplicadoPorIndice[$j] = true;
            }
        }
    }

    foreach ($jogos as $indice => &$jogo) {
        $chaveDescricao = trim($jogo['descricao'] ?? '');

        $jogo['duplicado'] = $duplicadoPorIndice[$indice]
            || ($chaveDescricao !== '' && $contagemDescricao[$chaveDescricao] > 1);
    }
    unset($jogo);
}
