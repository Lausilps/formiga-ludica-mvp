<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function protegerAdmin()
{
    if (
        !isset($_SESSION['id_usuario']) ||
        $_SESSION['tipo'] !== 'admin'
    ) {
        header("Location: ../../login.php");
        exit;
    }
}