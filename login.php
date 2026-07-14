<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Formiga Lúdica</title>
    <link rel="icon" type="image/png" href="assets/img/logo_formiga_ludica.png">
    <link rel="stylesheet" href="assets/css/global.css">
</head>
<body>

    <h1>Login Administrativo</h1>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alerta alerta-erro">
            E-mail ou senha inválidos.
        </div>
    <?php endif; ?>

    <form action="controllers/loginController.php" method="POST" id="form-login">

        <label>E-mail:</label>
        <input type="email" name="email" id="campo-email" autocomplete="email" required>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <button type="submit">Entrar</button>

    </form>

    <script>
        const CHAVE_ULTIMO_EMAIL = 'formigaludica_ultimo_email';
        const campoEmail = document.getElementById('campo-email');

        const ultimoEmail = localStorage.getItem(CHAVE_ULTIMO_EMAIL);
        if (ultimoEmail) campoEmail.value = ultimoEmail;

        document.getElementById('form-login').addEventListener('submit', () => {
            localStorage.setItem(CHAVE_ULTIMO_EMAIL, campoEmail.value);
        });
    </script>

</body>
</html>