<?php

require_once __DIR__ . '/sessaoHelper.php';
iniciarSessaoPersistente();

function protegerAdmin(string $caminhoLogin = '../../login.php')
{
    if (
        !isset($_SESSION['id_usuario']) ||
        $_SESSION['tipo'] !== 'admin'
    ) {
        header("Location: $caminhoLogin");
        exit;
    }
}