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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Jogo</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cadastrar.css">
</head>
<body class="admin-body">

<?php
    $tituloPagina = 'Cadastrar jogo';
    $subtituloPagina = 'Adicione um novo jogo ao catálogo da Formiga Lúdica.';
    include '../partials/admin_header.php';
?>

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
                <?php include '../partials/jogo_form_campos.php'; ?>
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

<script src="../../assets/js/preview-imagem.js"></script>
<script>
    inicializarPreviewImagem('imagem', 'preview-imagem');
</script>

</body>
</html>