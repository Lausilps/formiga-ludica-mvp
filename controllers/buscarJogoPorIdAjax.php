<?php
require_once __DIR__ . '/../config/conexao.php';
header('Content-Type: application/json');

// Busca um único jogo por ID, no mesmo formato que listarJogosAjax.php usa
// pra cada item — usado pra abrir o modal de detalhes a partir de um link
// direto (?jogo=ID), quando o jogo pode nem estar carregado no grid ainda.
$idJogo = (int) ($_GET['id'] ?? 0);

if ($idJogo <= 0) {
    echo json_encode(['jogo' => null]);
    exit;
}

$stmt = mysqli_prepare($conexao, "
    SELECT id_jogo, nome, imagem, descricao, preco,
           min_jogadores, max_jogadores, idade_minima,
           duracao_minutos, dificuldade, link_ludopedia, link_tutorial
    FROM jogos
    WHERE id_jogo = ? AND ativo = 1
");
mysqli_stmt_bind_param($stmt, 'i', $idJogo);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    echo json_encode(['jogo' => null]);
    exit;
}

$stmtImg = mysqli_prepare($conexao, "SELECT caminho FROM jogos_imagens WHERE id_jogo = ? ORDER BY ordem ASC");
mysqli_stmt_bind_param($stmtImg, 'i', $idJogo);
mysqli_stmt_execute($stmtImg);
$resultImg = mysqli_stmt_get_result($stmtImg);

$imagens = [];
while ($linha = mysqli_fetch_assoc($resultImg)) {
    $imagens[] = $linha['caminho'];
}

$srcImagem = !empty($row['imagem']) ? $row['imagem'] : 'assets/img/sem-imagem.png';

echo json_encode([
    'jogo' => [
        'id'                 => $row['id_jogo'],
        'nome'               => $row['nome'],
        'imagem'             => $srcImagem,
        'descricao'          => $row['descricao'] ?? '',
        'preco'              => $row['preco'],
        'min_jogadores'      => $row['min_jogadores'],
        'max_jogadores'      => $row['max_jogadores'],
        'idade_minima'       => $row['idade_minima'],
        'duracao'            => $row['duracao_minutos'],
        'dificuldade'        => $row['dificuldade'],
        'link_ludopedia'     => $row['link_ludopedia'] ?? '',
        'link_ver_ludopedia' => $row['link_ludopedia'] ?: ($row['link_tutorial'] ?? ''),
        'imagens'            => $imagens ?: [$srcImagem],
    ],
]);
