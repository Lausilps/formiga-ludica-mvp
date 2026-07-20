<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../helpers/authHelper.php';
require_once __DIR__ . '/../helpers/destaquesHelper.php';

protegerAdmin('../login.php');

header('Content-Type: application/json');

$termo = trim($_GET['termo'] ?? '');

if ($termo === '') {
    echo json_encode([]);
    exit;
}

$jogos = buscarJogosParaDestaque($conexao, $termo);

$resposta = array_map(function ($jogo) {
    $imagem = !empty($jogo['imagem'])
        ? (str_starts_with($jogo['imagem'], 'http') ? $jogo['imagem'] : '../../' . $jogo['imagem'])
        : '../../assets/img/sem-imagem.png';

    return [
        'id'     => (int) $jogo['id_jogo'],
        'nome'   => $jogo['nome'],
        'imagem' => $imagem,
    ];
}, $jogos);

echo json_encode($resposta);
