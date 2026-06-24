<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';

if (!isset($_GET['id'])) {
    registrarLog('ERRO', 'Tentativa de acessar edição sem informar ID do jogo.');
    die('Jogo não informado.');
}

$id_jogo = (int) $_GET['id'];

$sql = "SELECT * FROM jogos WHERE id_jogo = $id_jogo";
$resultado = mysqli_query($conexao, $sql);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    registrarLog('ERRO', "Tentativa de editar jogo inexistente. ID: $id_jogo");
    die('Jogo não encontrado.');
}

$jogo = mysqli_fetch_assoc($resultado);

$nome = $_GET['nome'] ?? $jogo['nome'];
$descricao = $_GET['descricao'] ?? $jogo['descricao'];
$preco = $_GET['preco'] ?? $jogo['preco'];
$min_jogadores = $_GET['min_jogadores'] ?? $jogo['min_jogadores'];
$max_jogadores = $_GET['max_jogadores'] ?? $jogo['max_jogadores'];
$idade_minima = $_GET['idade_minima'] ?? $jogo['idade_minima'];
$duracao_minutos = $_GET['duracao_minutos'] ?? $jogo['duracao_minutos'];
$dificuldade = $_GET['dificuldade'] ?? $jogo['dificuldade'];
$resumo_regras = $_GET['resumo_regras'] ?? $jogo['resumo_regras'];
$link_tutorial = $_GET['link_tutorial'] ?? $jogo['link_tutorial'];
$ativo = $_GET['ativo'] ?? $jogo['ativo'];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Jogo</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
</head>
<body>

    <h1>Editar jogo</h1>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'duplicado'): ?>
        <div class="alerta alerta-erro">
            Já existe outro jogo cadastrado com esse nome.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'campos_obrigatorios'): ?>
        <div class="alerta alerta-erro">
            Preencha todos os campos obrigatórios.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'jogadores_invalidos'): ?>
        <div class="alerta alerta-erro">
            A quantidade mínima de jogadores não pode ser maior que a máxima.
        </div>
    <?php endif; ?>

    <form action="../../controllers/editarJogoController.php" method="POST" enctype="multipart/form-data">

        <input type="hidden" name="id_jogo" value="<?= $jogo['id_jogo'] ?>">

        <label>Nome:</label><br>
        <input type="text" name="nome" required value="<?= $nome ?>"><br><br>

        <div class="campo-checkbox">
            <input
                type="checkbox"
                id="inativar"
                name="inativar"
                value="1"
                <?= $ativo == 0 ? 'checked' : '' ?>
            >

            <label for="inativar">Inativar produto</label>
        </div>

        <br><br>

        <br><br>

        <label>Descrição:</label><br>
        <textarea name="descricao" required><?= $descricao ?></textarea><br><br>

        <label>Preço:</label><br>
        <input type="number" step="0.01" name="preco" required value="<?= $preco ?>"><br><br>

        <label>Mínimo de jogadores:</label><br>
        <input type="number" name="min_jogadores" required value="<?= $min_jogadores ?>"><br><br>

        <label>Máximo de jogadores:</label><br>
        <input type="number" name="max_jogadores" required value="<?= $max_jogadores ?>"><br><br>

        <label>Idade mínima:</label><br>
        <input type="number" name="idade_minima" required value="<?= $idade_minima ?>"><br><br>

        <label>Duração média (minutos):</label><br>
        <input type="number" name="duracao_minutos" required value="<?= $duracao_minutos ?>"><br><br>

        <label>Dificuldade:</label><br>
        <select name="dificuldade" required>
            <option value="facil" <?= $dificuldade == 'facil' ? 'selected' : '' ?>>Fácil</option>
            <option value="media" <?= $dificuldade == 'media' ? 'selected' : '' ?>>Média</option>
            <option value="dificil" <?= $dificuldade == 'dificil' ? 'selected' : '' ?>>Difícil</option>
        </select><br><br>

        <label>Status:</label><br>
        <select name="ativo">
            <option value="1" <?= $ativo == 1 ? 'selected' : '' ?>>Ativo</option>
            <option value="0" <?= $ativo == 0 ? 'selected' : '' ?>>Inativo</option>
        </select><br><br>

        <label>Resumo das regras:</label><br>
        <textarea name="resumo_regras"><?= $resumo_regras ?></textarea><br><br>

        <label>Link do tutorial:</label><br>
        <input
            type="url"
            name="link_tutorial"
            placeholder="Cole aqui o link do tutorial"
            value="<?= $link_tutorial ?>"
        ><br><br>

        <?php if (!empty($jogo['imagem'])): ?>
            <label>Imagem atual:</label><br>
            <img
                src="../../<?= $jogo['imagem'] ?>"
                alt="<?= $jogo['nome'] ?>"
                style="max-width:200px; border-radius:10px; margin-bottom:16px;"
            ><br><br>
        <?php endif; ?>

        <label>Nova imagem:</label><br>
        <input type="file" name="imagem" id="imagem" accept="image/*"><br><br>

        <img
            id="preview-imagem"
            src=""
            alt="Prévia da nova imagem"
            style="display:none; max-width:200px; border-radius:10px; margin-bottom:16px;"
        >

        <button type="submit">Salvar alterações</button>

    </form>

    <p>
        <a href="listar.php">Voltar para listagem</a>
    </p>

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