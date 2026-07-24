<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';
require_once '../../helpers/authHelper.php';
require_once '../../helpers/assetHelper.php';

protegerAdmin('../../login.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Novo usuário</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo_formiga_ludica.png">

    <link rel="stylesheet" href="../../assets/css/global.css<?= assetVersao('assets/css/global.css') ?>">
    <link rel="stylesheet" href="../../assets/css/editar.css<?= assetVersao('assets/css/editar.css') ?>">
</head>
<body class="admin-body">

<?php
    $tituloPagina = 'Novo usuário';
    $subtituloPagina = 'Crie um acesso administrativo pra outra pessoa.';
    $mostrarLogout = true;
    include '../partials/admin_header.php';
?>

<main class="admin-container">

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'campos_obrigatorios'): ?>
        <div class="alerta alerta-erro">Preencha todos os campos.</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'senha_curta'): ?>
        <div class="alerta alerta-erro">A senha precisa ter pelo menos 8 caracteres.</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'senha_diferente'): ?>
        <div class="alerta alerta-erro">As senhas não são iguais.</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'email_duplicado'): ?>
        <div class="alerta alerta-erro">Já existe um usuário com esse e-mail.</div>
    <?php endif; ?>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alerta alerta-sucesso">Usuário criado com sucesso!</div>
    <?php endif; ?>

    <section class="card-admin">

        <form action="../../controllers/criarUsuarioController.php" method="POST" class="form-editar">

            <div class="grid-form">

                <div class="campo campo-grande">
                    <label>Nome:</label>
                    <input type="text" name="nome" required value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
                </div>

                <div class="campo campo-grande">
                    <label>E-mail:</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                </div>

                <div class="campo">
                    <label>Senha:</label>
                    <div class="campo-senha">
                        <input type="password" name="senha" id="campo-senha" required minlength="8">
                        <button type="button" class="btn-mostrar-senha" onclick="alternarSenha('campo-senha', this)" aria-label="Mostrar senha">👁</button>
                    </div>
                </div>

                <div class="campo">
                    <label>Confirmar senha:</label>
                    <div class="campo-senha">
                        <input type="password" name="confirmar_senha" id="campo-confirmar-senha" required minlength="8">
                        <button type="button" class="btn-mostrar-senha" onclick="alternarSenha('campo-confirmar-senha', this)" aria-label="Mostrar senha">👁</button>
                    </div>
                </div>

            </div>

            <div class="acoes-form">
                <button type="submit" class="btn-salvar">Criar usuário</button>
                <a href="../jogos/listar.php" class="btn-voltar">← Voltar para listagem</a>
            </div>

        </form>

    </section>

</main>

<script src="../../assets/js/mostrarSenha.js<?= assetVersao('assets/js/mostrarSenha.js') ?>"></script>

</body>
</html>
