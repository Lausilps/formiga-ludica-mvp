<?php

$host = "localhost";
$usuario = "root";
$senha = "ROOT";
$banco = "formiga_ludica";
$porta = 3306;

$conexao = mysqli_connect(
    $host,
    $usuario,
    $senha,
    $banco,
    $porta
);

if (!$conexao) {
    die("Erro na conexão: " . mysqli_connect_error());
}