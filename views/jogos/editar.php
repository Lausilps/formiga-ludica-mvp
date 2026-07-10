<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';
require_once '../../helpers/authHelper.php';

protegerAdmin();

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

$srcImagemAtual = '';

if (!empty($jogo['imagem'])) {
    if (str_starts_with($jogo['imagem'], 'http')) {
        $srcImagemAtual = $jogo['imagem'];
    } else {
        $srcImagemAtual = "../../" . $jogo['imagem'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Editar Jogo</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo_formiga_ludica.png">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/editar.css">
</head>
<body class="admin-body">

<?php
    $tituloPagina = 'Editar jogo';
    $subtituloPagina = 'Atualize as informações do catálogo da Formiga Lúdica.';
    include '../partials/admin_header.php';
?>

<main class="admin-container">

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

    <section class="card-admin">

        <form action="../../controllers/editarJogoController.php" method="POST" enctype="multipart/form-data" class="form-editar">

            <input type="hidden" name="id_jogo" value="<?= $jogo['id_jogo'] ?>">

            <div class="grid-form">
                <?php $modoEdicao = true; include '../partials/jogo_form_campos.php'; ?>
            </div>

            <div class="area-imagem">

                <?php if (!empty($srcImagemAtual)): ?>
                    <div>
                        <label>Imagem atual:</label>

                        <div class="preview-atual">
                            <img src="<?= htmlspecialchars($srcImagemAtual) ?>" alt="<?= htmlspecialchars($jogo['nome']) ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <label>Nova imagem:</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*">

                    <img
                        id="preview-imagem"
                        src=""
                        alt="Prévia da nova imagem"
                        class="preview-nova"
                    >
                </div>

            </div>

            <div class="acoes-form">
                <button type="submit" class="btn-salvar">Salvar alterações</button>
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