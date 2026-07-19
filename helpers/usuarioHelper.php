<?php

function emailUsuarioDuplicado(mysqli $conexao, string $email): bool
{
    $stmt = mysqli_prepare($conexao, "SELECT id_usuario FROM usuarios WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    return mysqli_num_rows($resultado) > 0;
}
