<?php

require_once '../config/conexao.php';
require_once '../helpers/logHelper.php';
require_once '../helpers/authHelper.php';
require_once '../helpers/usuarioHelper.php';

protegerAdmin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Requisição inválida.');
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

$dadosFormulario = ['nome' => $nome, 'email' => $email];

if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
    header("Location: ../views/usuarios/criar.php?erro=campos_obrigatorios&" . http_build_query($dadosFormulario));
    exit;
}

if (strlen($senha) < 8) {
    header("Location: ../views/usuarios/criar.php?erro=senha_curta&" . http_build_query($dadosFormulario));
    exit;
}

if ($senha !== $confirmarSenha) {
    header("Location: ../views/usuarios/criar.php?erro=senha_diferente&" . http_build_query($dadosFormulario));
    exit;
}

if (emailUsuarioDuplicado($conexao, $email)) {
    registrarLog('ALERTA', "Tentativa de criar usuário com e-mail já existente: $email");
    header("Location: ../views/usuarios/criar.php?erro=email_duplicado&" . http_build_query($dadosFormulario));
    exit;
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (nome, email, senha, tipo, ativo) VALUES (?, ?, ?, 'admin', 1)");
mysqli_stmt_bind_param($stmt, 'sss', $nome, $email, $senhaHash);

if (mysqli_stmt_execute($stmt)) {

    registrarLog('INFO', "Novo usuário admin criado: $email (por {$_SESSION['email']})");

    header("Location: ../views/usuarios/criar.php?sucesso=1");
    exit;

} else {

    $erro = mysqli_error($conexao);
    registrarLog('ERRO', "Falha ao criar usuário '$email': $erro");

    echo "Ocorreu um erro ao criar o usuário.";
}
