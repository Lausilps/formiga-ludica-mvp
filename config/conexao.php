<?php

$host    = getenv('DB_HOST')     ?: 'localhost';
$usuario = getenv('DB_USER')     ?: 'root';
$senha   = getenv('DB_PASSWORD') ?: '';
$banco   = getenv('DB_NAME')     ?: 'formiga_ludica';
$porta   = (int)(getenv('DB_PORT') ?: PORTA);

$conexao = mysqli_connect($host, $usuario, $senha, $banco, $porta);

if (!$conexao) {
    die("Erro na conexão: " . mysqli_connect_error());
}