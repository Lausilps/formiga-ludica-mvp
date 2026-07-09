<?php
require_once '../../config/conexao.php';
require_once '../../helpers/authHelper.php';

protegerAdmin();

$nome = $_GET['nome'] ?? '';
$descricao = $_GET['descricao'] ?? '';
$preco = $_GET['preco'] ?? '';
$min_jogadores = $_GET['min_jogadores'] ?? '';
$max_jogadores = $_GET['max_jogadores'] ?? '';
$idade_minima = $_GET['idade_minima'] ?? '';
$duracao_minutos = $_GET['duracao_minutos'] ?? '';
$dificuldade = $_GET['dificuldade'] ?? '';
$resumo_regras = $_GET['resumo_regras'] ?? '';
$link_tutorial = $_GET['link_tutorial'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Jogo</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cadastrar.css">
</head>
<body class="admin-body">

<header class="admin-header">
    <div class="admin-header-conteudo">
        <img src="../../assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="admin-logo">

        <div>
            <span class="admin-label">Painel administrativo</span>
            <h1>Cadastrar jogo</h1>
            <p>Adicione um novo jogo ao catálogo da Formiga Lúdica.</p>
        </div>
    </div>
</header>

<main class="admin-container">

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

    <section class="card-admin">

        <form action="../../controllers/jogosController.php" method="POST" enctype="multipart/form-data" class="form-cadastrar">

            <div class="grid-form">

                <div class="campo campo-grande">
                    <label>Nome do jogo:</label>
                    <input type="text" name="nome" required value="<?= htmlspecialchars($nome) ?>">
                </div>

                <div class="campo campo-grande">
                    <label>Descrição:</label>
                    <textarea name="descricao" required><?= htmlspecialchars($descricao) ?></textarea>
                </div>

                <div class="campo">
                    <label>Preço:</label>
                    <input type="number" name="preco" step="0.01" required value="<?= htmlspecialchars($preco) ?>">
                </div>

                <div class="campo">
                    <label>Mínimo de jogadores:</label>
                    <input type="number" name="min_jogadores" required value="<?= htmlspecialchars($min_jogadores) ?>">
                </div>

                <div class="campo">
                    <label>Máximo de jogadores:</label>
                    <input type="number" name="max_jogadores" required value="<?= htmlspecialchars($max_jogadores) ?>">
                </div>

                <div class="campo">
                    <label>Idade mínima:</label>
                    <input type="number" name="idade_minima" required value="<?= htmlspecialchars($idade_minima) ?>">
                </div>

                <div class="campo">
                    <label>Duração média:</label>
                    <input type="number" name="duracao_minutos" required value="<?= htmlspecialchars($duracao_minutos) ?>">
                </div>

                <div class="campo">
                    <label>Dificuldade:</label>
                    <select name="dificuldade" required>
                        <option value="">Selecione</option>
                        <option value="facil" <?= $dificuldade == 'facil' ? 'selected' : '' ?>>Fácil</option>
                        <option value="media" <?= $dificuldade == 'media' ? 'selected' : '' ?>>Média</option>
                        <option value="dificil" <?= $dificuldade == 'dificil' ? 'selected' : '' ?>>Difícil</option>
                    </select>
                </div>

                <div class="campo campo-grande">
                    <label>Resumo das regras:</label>
                    <textarea name="resumo_regras"><?= htmlspecialchars($resumo_regras) ?></textarea>
                </div>

                <div class="campo campo-grande">
                    <label>Link do tutorial:</label>
                    <input
                        type="url"
                        name="link_tutorial"
                        placeholder="Cole aqui o link do tutorial"
                        value="<?= htmlspecialchars($link_tutorial) ?>"
                    >
                </div>

            </div>

            <div class="area-imagem">
                <div>
                    <label>Imagem do jogo:</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*">

                    <img
                        id="preview-imagem"
                        src=""
                        alt="Prévia da imagem"
                        class="preview-nova"
                    >
                </div>
            </div>

            <div class="acoes-form">
                <button type="submit" class="btn-salvar">Cadastrar jogo</button>
                <a href="listar.php" class="btn-voltar">← Voltar para listagem</a>
            </div>

        </form>

    </section>

</main>

<script>
document.getElementById('imagem').addEventListener('change', function(event) {
    const arquivo = event.target.files[0];
    const preview = document.getElementById('preview-imagem');

    if (arquivo) {
        preview.src = URL.createObjectURL(arquivo);
        preview.style.display = 'block';
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
});
</script>

</body>
</html>