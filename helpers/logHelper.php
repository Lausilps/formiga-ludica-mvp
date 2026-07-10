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

    // Também manda pro log padrão do PHP (stdout/stderr), que é o que
    // aparece na aba de Logs do Railway — o arquivo acima some a cada
    // deploy porque o disco lá é temporário.
    error_log("[$tipo] $mensagem");
}