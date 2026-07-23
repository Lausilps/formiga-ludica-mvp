<?php

require_once __DIR__ . '/../helpers/logHelper.php';

$host    = getenv('DB_HOST')     ?: 'localhost';
$usuario = getenv('DB_USER')     ?: 'root';
$senha   = getenv('DB_PASSWORD') ?: '';
$banco   = getenv('DB_NAME')     ?: 'formiga_ludica';
$porta   = (int)(getenv('DB_PORT') ?: PORTA);

$conexao = mysqli_connect($host, $usuario, $senha, $banco, $porta);

if (!$conexao) {
    // Detalhe técnico (host, motivo da falha) só no log — pra quem visita
    // o site, uma mensagem genérica é suficiente e não vaza infraestrutura.
    registrarLog('ERRO', 'Falha ao conectar no banco: ' . mysqli_connect_error());
    die('Erro ao conectar. Tente novamente em instantes.');
}