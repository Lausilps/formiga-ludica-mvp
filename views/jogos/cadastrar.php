<?php
require_once '../../config/conexao.php';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <title>Cadastrar Jogo</title>
</head>
<body>      

    <h1>Cadastrar Jogo</h1>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alerta alerta-sucesso">
            Jogo cadastrado com sucesso! 🎲
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'duplicado'): ?>
        <div class="alerta alerta-erro">
            Já existe um jogo cadastrado com esse nome.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'jogadores_invalidos'): ?>
        <div class="alerta alerta-erro">
            A quantidade mínima de jogadores não pode ser maior que a máxima.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'campos_obrigatorios'): ?>
        <div class="alerta alerta-erro">
            Preencha todos os campos obrigatórios.
        </div>
    <?php endif; ?>

    <form action="../../controllers/jogosController.php" method="POST">

        <label>Nome do jogo:</label><br>
        <input type="text" name="nome" required value="<?= $_GET['nome'] ?? '' ?>"><br><br>
        

        <label>Descrição:</label><br>
        <textarea name="descricao" required><?= $_GET['descricao'] ?? '' ?></textarea><br><br>

        <label>Preço:</label><br>
        <input type="number" name="preco" step="0.01" required value="<?= $_GET['preco'] ?? '' ?>"><br><br>

        <label>Mínimo de jogadores:</label><br>
        <input type="number" name="min_jogadores" required value="<?= $_GET['min_jogadores'] ?? '' ?>"><br><br>

        <label>Máximo de jogadores:</label><br>
        <input type="number" name="max_jogadores" required value="<?= $_GET['max_jogadores'] ?? '' ?>"><br><br>

        <label>Idade mínima:</label><br>
        <input type="number" name="idade_minima" required value="<?= $_GET['idade_minima'] ?? '' ?>"><br><br>

        <label>Duração média em minutos:</label><br>
        <input type="number" name="duracao_minutos" required value="<?= $_GET['duracao_minutos'] ?? '' ?>"><br><br>

        <label>Dificuldade:</label><br>
        <select name="dificuldade" required>
            <option value="facil" <?= (($_GET['dificuldade'] ?? '') == 'facil') ? 'selected' : '' ?>>Fácil</option>
            <option value="media" <?= (($_GET['dificuldade'] ?? '') == 'media') ? 'selected' : '' ?>>Média</option>
            <option value="dificil" <?= (($_GET['dificuldade'] ?? '') == 'dificil') ? 'selected' : '' ?>>Difícil</option>
        </select><br><br>

        <label>Resumo das regras:</label><br>
        <textarea name="resumo_regras"><?= $_GET['resumo_regras'] ?? '' ?></textarea><br><br>

        <label>Link do tutorial:</label><br>
        <input
            type="url"
            name="link_tutorial"
            placeholder="Cole aqui o link do tutorial"
            value="<?= $_GET['link_tutorial'] ?? '' ?>"
        ><br><br>

        <button type="submit">Cadastrar jogo</button>

    </form>

</body>
</html>