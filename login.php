<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Formiga Lúdica</title>
    <link rel="stylesheet" href="assets/css/global.css">
</head>
<body>

    <h1>Login Administrativo</h1>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alerta alerta-erro">
            E-mail ou senha inválidos.
        </div>
    <?php endif; ?>

    <form action="controllers/loginController.php" method="POST">

        <label>E-mail:</label>
        <input type="email" name="email" required>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <button type="submit">Entrar</button>

    </form>

</body>
</html>