<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/sessaoHelper.php';
iniciarSessaoPersistente();

$email = $_POST['email'];
$senha = $_POST['senha'];

$stmt = mysqli_prepare($conexao, "SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

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