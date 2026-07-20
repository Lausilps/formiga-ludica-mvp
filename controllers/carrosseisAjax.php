<?php
require_once __DIR__ . '/../config/conexao.php';
header('Content-Type: application/json');

// Mesmo formato de card que listarJogosAjax.php devolve, pra reaproveitar
// criarCard() do lado do JS sem duplicar a montagem do card.
function montarJogosParaCarrossel(mysqli $conexao, string $sql): array
{
    $resultado = mysqli_query($conexao, $sql);

    if (!$resultado) {
        return [];
    }

    $linhas = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $linhas[] = $row;
    }

    $idsJogos = array_column($linhas, 'id_jogo');
    $imagensPorJogo = [];

    if (!empty($idsJogos)) {
        $placeholders = implode(',', array_fill(0, count($idsJogos), '?'));
        $tipos = str_repeat('i', count($idsJogos));

        $stmt = mysqli_prepare($conexao, "SELECT id_jogo, caminho FROM jogos_imagens WHERE id_jogo IN ($placeholders) ORDER BY id_jogo, ordem ASC");
        mysqli_stmt_bind_param($stmt, $tipos, ...$idsJogos);
        mysqli_stmt_execute($stmt);
        $resultadoImg = mysqli_stmt_get_result($stmt);

        while ($linhaImg = mysqli_fetch_assoc($resultadoImg)) {
            $imagensPorJogo[$linhaImg['id_jogo']][] = $linhaImg['caminho'];
        }
    }

    $jogos = [];

    foreach ($linhas as $row) {
        $srcImagem = !empty($row['imagem']) ? $row['imagem'] : 'assets/img/sem-imagem.png';

        $jogos[] = [
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
            'imagens'            => $imagensPorJogo[$row['id_jogo']] ?? [$srcImagem],
        ];
    }

    return $jogos;
}

$camposComuns = "id_jogo, nome, imagem, descricao, preco, min_jogadores, max_jogadores,
                  idade_minima, duracao_minutos, dificuldade, link_ludopedia, link_tutorial";

$novidades = montarJogosParaCarrossel($conexao, "
    SELECT $camposComuns
    FROM jogos
    WHERE ativo = 1 AND nome NOT LIKE 'SEM NOME (Ludopedia #%'
    ORDER BY criado_em DESC
    LIMIT 10
");

$destaques = montarJogosParaCarrossel($conexao, "
    SELECT $camposComuns
    FROM jogos
    WHERE ativo = 1 AND nome NOT LIKE 'SEM NOME (Ludopedia #%' AND ordem_destaque IS NOT NULL
    ORDER BY ordem_destaque ASC
");

echo json_encode([
    'novidades' => $novidades,
    'destaques' => $destaques,
]);
