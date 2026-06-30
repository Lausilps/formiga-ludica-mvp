<?php

$host = "localhost";
$usuario = "edilsystem";
$senha = "3Dsys_04244425";
$banco = "formiga_ludica";
$porta = 33418;

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