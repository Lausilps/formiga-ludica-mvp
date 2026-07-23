<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../helpers/slugHelper.php';
header('Content-Type: application/json');

$busca    = trim($_GET['busca'] ?? '');
$idades   = $_GET['idades']    ?? [];
$jogadores = $_GET['jogadores'] ?? [];
$tempos   = $_GET['tempos']    ?? [];
$offset   = (int)($_GET['offset'] ?? 0);
$limite   = 24;
$temFiltro = !empty($busca) || !empty($idades) || !empty($jogadores) || !empty($tempos);

// Usado pelo carrossel "Recomendações da loja": mesmos filtros do catálogo,
// mas só entre os jogos curados (ordem_destaque preenchido), sem paginação.
$soDestaques = ($_GET['so_destaques'] ?? '') === '1';

// Usado pelo carrossel "Confira as novidades": mesmos filtros do catálogo,
// ordenado por mais recentes, sempre limitado a 10 (sem paginação).
$soNovidades = ($_GET['so_novidades'] ?? '') === '1';

// Monta WHERE
$where = ["ativo = 1", "nome NOT LIKE 'SEM NOME (Ludopedia #%'"];
$params = [];
$types  = '';

if ($soDestaques) {
    $where[] = "ordem_destaque IS NOT NULL";
}

if ($soNovidades) {
    // "Novidades" = os 10 jogos mais recentes de verdade — não os 10 mais
    // recentes ENTRE OS QUE BATEM COM O FILTRO (isso faria um jogo antigo
    // aparecer como novidade só por não ter concorrência no resultado
    // filtrado). O filtro só reduz dentro desse conjunto fixo, nunca
    // forma um "top 10" novo a partir dele. O subselect precisa desse
    // envelope (SELECT ... FROM (SELECT ... LIMIT 10) AS x) porque o
    // MySQL não aceita LIMIT direto dentro de um IN (subquery).
    $where[] = "id_jogo IN (
        SELECT id_jogo FROM (
            SELECT id_jogo FROM jogos
            WHERE ativo = 1 AND nome NOT LIKE 'SEM NOME (Ludopedia #%'
            ORDER BY criado_em DESC
            LIMIT 10
        ) AS novidades_recentes
    )";
}

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

$ordemSQL = $soDestaques ? 'ordem_destaque ASC' : ($soNovidades ? 'criado_em DESC' : 'nome ASC');

// Busca jogos
if ($temFiltro || $soDestaques || $soNovidades) {
    // Com filtro (ou carrossel de destaques/novidades): traz de uma vez, sem paginação
    $sql = "SELECT id_jogo, nome, imagem, descricao, preco,
                   min_jogadores, max_jogadores, idade_minima,
                   duracao_minutos, dificuldade, link_ludopedia, link_tutorial
            FROM jogos {$whereSQL} ORDER BY {$ordemSQL}";
} else {
    // Sem filtro: paginação
    $sql = "SELECT id_jogo, nome, imagem, descricao, preco,
                   min_jogadores, max_jogadores, idade_minima,
                   duracao_minutos, dificuldade, link_ludopedia, link_tutorial
            FROM jogos {$whereSQL} ORDER BY {$ordemSQL} LIMIT ? OFFSET ?";
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

$linhas = [];
while ($row = mysqli_fetch_assoc($result)) {
    $linhas[] = $row;
}

// Galeria de fotos de cada jogo visível nessa página, numa única consulta
// (evita uma query por jogo). Usada pro carrossel no hover do card e no
// modal de detalhes.
$idsJogos = array_column($linhas, 'id_jogo');
$imagensPorJogo = [];

if (!empty($idsJogos)) {
    $placeholders = implode(',', array_fill(0, count($idsJogos), '?'));
    $tiposImg = str_repeat('i', count($idsJogos));

    $stmtImg = mysqli_prepare($conexao, "SELECT id_jogo, caminho FROM jogos_imagens WHERE id_jogo IN ($placeholders) ORDER BY id_jogo, ordem ASC");
    mysqli_stmt_bind_param($stmtImg, $tiposImg, ...$idsJogos);
    mysqli_stmt_execute($stmtImg);
    $resultImg = mysqli_stmt_get_result($stmtImg);

    while ($linhaImg = mysqli_fetch_assoc($resultImg)) {
        $imagensPorJogo[$linhaImg['id_jogo']][] = $linhaImg['caminho'];
    }
}

$jogos = [];
foreach ($linhas as $row) {
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
        'slug'          => gerarSlug($row['nome']),
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
        'imagens'            => $imagensPorJogo[$row['id_jogo']] ?? [$srcImagem],
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