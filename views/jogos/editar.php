<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';
require_once '../../helpers/authHelper.php';
require_once '../../helpers/jogoImagensHelper.php';

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
$imagensGaleria = listarImagensJogo($conexao, $id_jogo);

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

    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'falha_exclusao'): ?>
        <div class="alerta alerta-erro">
            Não foi possível excluir o jogo. Tente novamente.
        </div>
    <?php endif; ?>

    <section class="card-admin">

        <form id="form-editar-jogo" action="../../controllers/editarJogoController.php" method="POST" enctype="multipart/form-data" class="form-editar">

            <input type="hidden" name="id_jogo" value="<?= $jogo['id_jogo'] ?>">

            <div class="grid-form">
                <?php $modoEdicao = true; include '../partials/jogo_form_campos.php'; ?>
            </div>

        </form>

        <form id="form-excluir-jogo" action="../../controllers/excluirJogoController.php" method="POST" style="display:none;">
            <input type="hidden" name="id_jogo" value="<?= $jogo['id_jogo'] ?>">
        </form>

        <div class="area-galeria">

            <h2>Galeria de fotos</h2>
            <p class="descricao-galeria">A primeira foto é a capa usada no catálogo e na listagem. Use as setas pra reordenar ou a estrela pra trocar a capa.</p>

            <?php if (empty($imagensGaleria)): ?>
                <p class="sem-fotos">Nenhuma foto cadastrada ainda.</p>
            <?php else: ?>
                <div class="galeria-fotos">
                    <?php foreach ($imagensGaleria as $indice => $imagem): ?>
                        <?php
                            $srcGaleria = str_starts_with($imagem['caminho'], 'http')
                                ? $imagem['caminho']
                                : '../../' . $imagem['caminho'];
                        ?>
                        <div class="foto-galeria">
                            <img src="<?= htmlspecialchars($srcGaleria) ?>" alt="Foto <?= $indice + 1 ?> de <?= htmlspecialchars($jogo['nome']) ?>">

                            <?php if ($indice === 0): ?>
                                <span class="etiqueta-capa">Capa</span>
                            <?php endif; ?>

                            <div class="acoes-foto-galeria">
                                <?php if ($indice !== 0): ?>
                                    <button type="button" class="btn-foto" onclick="acaoGaleria('capa', <?= $imagem['id_imagem'] ?>)" title="Tornar capa">★</button>
                                <?php endif; ?>

                                <?php if ($indice > 0): ?>
                                    <button type="button" class="btn-foto" onclick="acaoGaleria('mover', <?= $imagem['id_imagem'] ?>, 'esquerda')" title="Mover pra esquerda">←</button>
                                <?php endif; ?>

                                <?php if ($indice < count($imagensGaleria) - 1): ?>
                                    <button type="button" class="btn-foto" onclick="acaoGaleria('mover', <?= $imagem['id_imagem'] ?>, 'direita')" title="Mover pra direita">→</button>
                                <?php endif; ?>

                                <button type="button" class="btn-foto btn-foto-excluir" onclick="excluirFotoGaleria(<?= $imagem['id_imagem'] ?>)" title="Excluir foto">🗑</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="../../controllers/jogoImagensController.php" method="POST" enctype="multipart/form-data" class="form-upload-galeria">
                <input type="hidden" name="id_jogo" value="<?= $jogo['id_jogo'] ?>">
                <input type="hidden" name="acao" value="upload">

                <label>Adicionar fotos:</label>
                <input type="file" name="imagens[]" accept="image/*" multiple required>
                <button type="submit" class="btn-add-foto">Enviar fotos</button>
            </form>

            <form id="form-acao-galeria" action="../../controllers/jogoImagensController.php" method="POST" style="display:none;">
                <input type="hidden" name="id_jogo" value="<?= $jogo['id_jogo'] ?>">
                <input type="hidden" name="acao" id="galeria-acao">
                <input type="hidden" name="id_imagem" id="galeria-id-imagem">
                <input type="hidden" name="direcao" id="galeria-direcao">
            </form>

        </div>

        <div class="acoes-form">
            <button type="submit" form="form-editar-jogo" class="btn-salvar">Salvar alterações</button>
            <a href="listar.php" class="btn-voltar">← Voltar para listagem</a>
            <button type="button" class="btn-excluir" onclick="confirmarExclusao()">🗑️ Excluir jogo</button>
        </div>

    </section>

</main>

<script>
    function confirmarExclusao() {
        const nomeJogo = <?= json_encode($jogo['nome'], JSON_UNESCAPED_UNICODE) ?>;

        if (confirm(`Tem certeza que deseja excluir "${nomeJogo}"? Essa ação não pode ser desfeita.`)) {
            document.getElementById('form-excluir-jogo').submit();
        }
    }

    function acaoGaleria(acao, idImagem, direcao) {
        document.getElementById('galeria-acao').value = acao;
        document.getElementById('galeria-id-imagem').value = idImagem;
        document.getElementById('galeria-direcao').value = direcao || '';
        document.getElementById('form-acao-galeria').submit();
    }

    function excluirFotoGaleria(idImagem) {
        if (confirm('Tem certeza que deseja excluir essa foto?')) {
            acaoGaleria('excluir', idImagem);
        }
    }
</script>

</body>
</html>