<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/authHelper.php';
require_once '../helpers/jogoHelper.php';
require_once '../helpers/jogoImagensHelper.php';

protegerAdmin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_jogo'])) {
    die('Requisição inválida.');
}

$id_jogo = (int) $_POST['id_jogo'];
$acao = $_POST['acao'] ?? '';

switch ($acao) {

    case 'upload':
        if (!empty($_FILES['imagens']['name'][0])) {
            $total = count($_FILES['imagens']['name']);

            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['imagens']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $arquivo = [
                    'name' => $_FILES['imagens']['name'][$i],
                    'type' => $_FILES['imagens']['type'][$i],
                    'tmp_name' => $_FILES['imagens']['tmp_name'][$i],
                    'error' => $_FILES['imagens']['error'][$i],
                    'size' => $_FILES['imagens']['size'][$i],
                ];

                $caminho = uploadImagemJogo($arquivo);

                if ($caminho !== null) {
                    adicionarImagemJogo($conexao, $id_jogo, $caminho);
                    registrarLog('INFO', "Foto adicionada ao jogo ID: $id_jogo | Arquivo: $caminho");
                }
            }
        }
        break;

    case 'excluir':
        $id_imagem = (int) ($_POST['id_imagem'] ?? 0);
        removerImagemJogo($conexao, $id_jogo, $id_imagem);
        registrarLog('INFO', "Foto removida do jogo ID: $id_jogo | ID imagem: $id_imagem");
        break;

    case 'capa':
        $id_imagem = (int) ($_POST['id_imagem'] ?? 0);
        definirCapaJogo($conexao, $id_jogo, $id_imagem);
        registrarLog('INFO', "Capa alterada no jogo ID: $id_jogo | Nova capa: ID imagem $id_imagem");
        break;

    case 'mover':
        $id_imagem = (int) ($_POST['id_imagem'] ?? 0);
        $direcao = $_POST['direcao'] ?? '';
        moverImagemJogo($conexao, $id_jogo, $id_imagem, $direcao);
        break;
}

header("Location: ../views/jogos/editar.php?id=$id_jogo");
exit;
