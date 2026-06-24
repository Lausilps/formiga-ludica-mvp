<?php

function registrarLog($tipo, $mensagem)
{
    $arquivo = __DIR__ . '/../logs/sistema.log';

    $dataHora = date('Y-m-d H:i:s');

    $texto = "[$dataHora] [$tipo] $mensagem" . PHP_EOL;

    file_put_contents(
        $arquivo,
        $texto,
        FILE_APPEND
    );
}