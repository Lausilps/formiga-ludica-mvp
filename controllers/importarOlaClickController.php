<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';

$json = $_POST['json_olaclick'] ?? '';

$dados = json_decode($json, true);

if (!$dados || !isset($dados['data'])) {
    registrarLog('ERRO', 'JSON inválido na importação OlaClick.');
    die('JSON inválido.');
}

$importados = 0;
$ignorados = 0;

foreach ($dados['data'] as $categoria) {

    if (empty($categoria['products'])) {
        continue;
    }

    foreach ($categoria['products'] as $produto) {

        $nome = mysqli_real_escape_string($conexao, $produto['name'] ?? '');
        $descricao = mysqli_real_escape_string($conexao, $produto['description'] ?? '');
        $preco = $produto['product_variants'][0]['price'] ?? 0;
        $imagem = $produto['images'][0]['image_url'] ?? '';

        if (empty($nome) || empty($descricao)) {
            continue;
        }

        preg_match('/Jogadores:\s*(\d+)\s*a\s*(\d+)/i', $descricao, $jogadores);
        preg_match('/Idade:\s*(\d+)/i', $descricao, $idade);
        preg_match('/Tempo.*?:\s*(\d+)/i', $descricao, $tempo);

        $min_jogadores = $jogadores[1] ?? 1;
        $max_jogadores = $jogadores[2] ?? $min_jogadores;
        $idade_minima = $idade[1] ?? 8;
        $duracao_minutos = $tempo[1] ?? 30;

        $verifica = mysqli_query(
            $conexao,
            "SELECT id_jogo FROM jogos WHERE nome = '$nome'"
        );

        if (mysqli_num_rows($verifica) > 0) {
            $ignorados++;
            continue;
        }

        $sql = "INSERT INTO jogos (
                    nome,
                    descricao,
                    preco,
                    min_jogadores,
                    max_jogadores,
                    idade_minima,
                    duracao_minutos,
                    dificuldade,
                    resumo_regras,
                    link_tutorial,
                    imagem,
                    origem,
                    ativo
                ) VALUES (
                    '$nome',
                    '$descricao',
                    '$preco',
                    '$min_jogadores',
                    '$max_jogadores',
                    '$idade_minima',
                    '$duracao_minutos',
                    'facil',
                    '',
                    '',
                    '$imagem',
                    'manual',
                    1
                )";

        if (mysqli_query($conexao, $sql)) {
            $importados++;
        } else {
            registrarLog('ERRO', "Erro ao importar jogo $nome: " . mysqli_error($conexao));
        }
    }
}

registrarLog('INFO', "Importação OlaClick concluída. Importados: $importados | Ignorados: $ignorados");

header("Location: ../views/jogos/listar.php?importados=$importados&ignorados=$ignorados");
exit;