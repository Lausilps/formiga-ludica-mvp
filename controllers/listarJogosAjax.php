<?php
require_once __DIR__ . '/../config/conexao.php';
header('Content-Type: application/json');

$busca    = trim($_GET['busca'] ?? '');
$idades   = $_GET['idades']    ?? [];
$jogadores = $_GET['jogadores'] ?? [];
$tempos   = $_GET['tempos']    ?? [];
$offset   = (int)($_GET['offset'] ?? 0);
$limite   = 24;
$temFiltro = !empty($busca) || !empty($idades) || !empty($jogadores) || !empty($tempos);

// Monta WHERE
$where = ["ativo = 1", "nome NOT LIKE 'SEM NOME (Ludopedia #%'"];
$params = [];
$types  = '';

if (!empty($busca)) {
    $where[] = "nome LIKE ?";
    $params[] = "%{$busca}%";
    $types   .= 's';
}

if (!empty($idades)) {
    $placeholders = implode(',', array_fill(0, count($idades), '?'));
    $where[] = "idade_minima IN ({$placeholders})";
    foreach ($idades as $i) {
        $params[] = (int)$i;
        $types   .= 'i';
    }
}

if (!empty($jogadores)) {
    $condicoes = [];
    foreach ($jogadores as $qtd) {
        $condicoes[] = "(min_jogadores <= ? AND max_jogadores >= ?)";
        $params[] = (int)$qtd;
        $params[] = (int)$qtd;
        $types   .= 'ii';
    }
    $where[] = '(' . implode(' OR ', $condicoes) . ')';
}

if (!empty($tempos)) {
    $condicoesTempo = [];
    foreach ($tempos as $t) {
        [$min, $max] = explode('-', $t);
        if ((int)$max === 999) {
            $condicoesTempo[] = "duracao_minutos >= ?";
            $params[] = (int)$min;
            $types   .= 'i';
        } else {
            $condicoesTempo[] = "(duracao_minutos >= ? AND duracao_minutos <= ?)";
            $params[] = (int)$min;
            $params[] = (int)$max;
            $types   .= 'ii';
        }
    }
    $where[] = '(' . implode(' OR ', $condicoesTempo) . ')';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Conta total
$sqlTotal = "SELECT COUNT(*) as total FROM jogos {$whereSQL}";
$stmtTotal = mysqli_prepare($conexao, $sqlTotal);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmtTotal, $types, ...$params);
}
mysqli_stmt_execute($stmtTotal);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotal))['total'];

// Busca jogos
if ($temFiltro) {
    // Com filtro: traz todos de uma vez
    $sql = "SELECT id_jogo, nome, imagem, descricao, preco,
                   min_jogadores, max_jogadores, idade_minima,
                   duracao_minutos, dificuldade, link_ludopedia, link_tutorial
            FROM jogos {$whereSQL} ORDER BY nome ASC";
} else {
    // Sem filtro: paginação
    $sql = "SELECT id_jogo, nome, imagem, descricao, preco,
                   min_jogadores, max_jogadores, idade_minima,
                   duracao_minutos, dificuldade, link_ludopedia, link_tutorial
            FROM jogos {$whereSQL} ORDER BY nome ASC LIMIT ? OFFSET ?";
    $params[] = $limite;
    $params[] = $offset;
    $types   .= 'ii';
}

$stmt = mysqli_prepare($conexao, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$jogos = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Trata imagem
    if (!empty($row['imagem']) && str_starts_with($row['imagem'], 'http')) {
        $srcImagem = $row['imagem'];
    } elseif (!empty($row['imagem'])) {
        $srcImagem = $row['imagem'];
    } else {
        $srcImagem = 'assets/img/sem-imagem.png';
    }

    $jogos[] = [
        'id'            => $row['id_jogo'],
        'nome'          => $row['nome'],
        'imagem'        => $srcImagem,
        'descricao'     => $row['descricao'] ?? '',
        'preco'         => $row['preco'],
        'min_jogadores' => $row['min_jogadores'],
        'max_jogadores' => $row['max_jogadores'],
        'idade_minima'  => $row['idade_minima'],
        'duracao'       => $row['duracao_minutos'],
        'dificuldade'   => $row['dificuldade'],
        // 'link_ludopedia' fica reservado pro selo "integrado via Ludopedia"
        // (só jogos sincronizados de verdade). O botão "Ver na Ludopedia" do
        // modal usa esse fallback pra também funcionar em jogos cadastrados
        // manualmente ou importados do OlaClick, onde o link é colado à mão
        // no campo "Link do tutorial".
        'link_ludopedia'     => $row['link_ludopedia'] ?? '',
        'link_ver_ludopedia' => $row['link_ludopedia'] ?: ($row['link_tutorial'] ?? ''),
    ];
}

echo json_encode([
    'jogos'      => $jogos,
    'total'      => (int)$total,
    'offset'     => $offset,
    'limite'     => $limite,
    'tem_filtro' => $temFiltro,
    'tem_mais'   => !$temFiltro && ($offset + $limite) < $total,
]);