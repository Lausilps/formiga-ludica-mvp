<?php

session_start();

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';

$email = $_POST['email'];
$senha = $_POST['senha'];

$sql = "SELECT * FROM usuarios 
        WHERE email = '$email' 
        AND ativo = 1 
        LIMIT 1";

$resultado = mysqli_query($conexao, $sql);

if ($resultado && mysqli_num_rows($resultado) == 1) {

    $usuario = mysqli_fetch_assoc($resultado);

    if (password_verify($senha, $usuario['senha'])) {

        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['tipo'] = $usuario['tipo'];

        registrarLog('INFO', "Login realizado: {$usuario['email']}");

        header("Location: ../views/jogos/listar.php");
        exit;
    }
}

registrarLog('SEGURANCA', "Tentativa de login inválida: $email");

header("Location: ../login.php?erro=1");
exit;