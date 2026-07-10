<?php
require_once '../../helpers/authHelper.php';
protegerAdmin();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Relatório de Jogos</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo_formiga_ludica.png">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/relatorio.css">
</head>
<body class="admin-body">

<?php
    $tituloPagina = 'Relatórios';
    $subtituloPagina = 'Gere relatórios personalizados do catálogo de jogos.';
    include '../partials/admin_header.php';
?>

<main class="admin-container">

    <section class="card-admin">

        <form action="../../controllers/gerarRelatorioJogosPdf.php" method="GET" target="_blank" class="form-relatorio">

            <div class="grid-form">

                <div class="campo">
                    <label>Nome contém:</label>
                    <input type="text" name="nome" placeholder="Ex: Catan, Dixit, Azul...">
                </div>

                <div class="campo">
                    <label>Dificuldade:</label>
                    <select name="dificuldade">
                        <option value="">Todas</option>
                        <option value="facil">Fácil</option>
                        <option value="media">Média</option>
                        <option value="dificil">Difícil</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Preço:</label>
                    <div class="campo-duplo">
                        <input type="number" step="0.01" name="preco_de" placeholder="De R$">
                        <input type="number" step="0.01" name="preco_ate" placeholder="Até R$">
                    </div>
                </div>

                <div class="campo">
                    <label>Duração:</label>
                    <div class="campo-duplo">
                        <input type="number" name="duracao_de" placeholder="De minutos">
                        <input type="number" name="duracao_ate" placeholder="Até minutos">
                    </div>
                </div>

                <div class="campo">
                    <label>Jogadores:</label>
                    <div class="campo-duplo">
                        <input type="number" name="jogadores_de" placeholder="De jogadores">
                        <input type="number" name="jogadores_ate" placeholder="Até jogadores">
                    </div>
                </div>

                <div class="campo">
                    <label>Idade:</label>
                    <div class="campo-duplo">
                        <input type="number" name="idade_de" placeholder="De idade">
                        <input type="number" name="idade_ate" placeholder="Até idade">
                    </div>
                </div>

                <div class="campo">
                    <label>Tipo do relatório:</label>
                    <select name="tipo">
                        <option value="sintetico">Sintético</option>
                        <option value="analitico">Analítico</option>
                    </select>
                </div>

            </div>

            <div class="opcoes-relatorio">

                <div class="campo-checkbox">
                    <input type="checkbox" id="mostrar_inativos" name="mostrar_inativos" value="1">
                    <label for="mostrar_inativos">Mostrar inativos</label>
                </div>

                <div class="campo-checkbox">
                    <input type="checkbox" id="somente_incompletos" name="somente_incompletos" value="1">
                    <label for="somente_incompletos">Somente jogos com informações faltantes</label>
                </div>

            </div>

            <div class="acoes-relatorio">
                <button type="submit" class="btn-gerar">Gerar PDF</button>
                <a href="listar.php" class="btn-voltar">← Voltar para listagem</a>
            </div>

        </form>

    </section>

</main>

</body>
</html>