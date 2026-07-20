<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/authHelper.php';
require_once '../helpers/destaquesHelper.php';

protegerAdmin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Requisição inválida.');
}

$acao = $_POST['acao'] ?? '';
$id_jogo = (int) ($_POST['id_jogo'] ?? 0);

switch ($acao) {

    case 'adicionar':
        adicionarDestaque($conexao, $id_jogo);
        registrarLog('INFO', "Jogo adicionado aos destaques da loja. ID: $id_jogo");
        break;

    case 'remover':
        removerDestaque($conexao, $id_jogo);
        registrarLog('INFO', "Jogo removido dos destaques da loja. ID: $id_jogo");
        break;

    case 'mover':
        $direcao = $_POST['direcao'] ?? '';
        moverDestaque($conexao, $id_jogo, $direcao);
        break;
}

// Devolve o HTML atualizado da lista (mesma partial usada em destaques.php)
// pra quem chamar via fetch só trocar o pedaço da tela que mudou.
$destaques = listarDestaques($conexao);

header('Content-Type: text/html; charset=UTF-8');
include __DIR__ . '/../views/partials/destaques_lista.php';
exit;
