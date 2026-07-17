<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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