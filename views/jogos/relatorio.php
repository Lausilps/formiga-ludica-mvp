<?php
require_once '../../helpers/authHelper.php';
protegerAdmin();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Jogos</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
</head>
<body>

<h1>Relatório de Jogos</h1>

<form action="../../controllers/gerarRelatorioJogosPdf.php" method="GET" target="_blank">

    <label>Nome contém:</label>
    <input type="text" name="nome">

    <label>Preço:</label>
    <input type="number" step="0.01" name="preco_de" placeholder="De R$">
    <input type="number" step="0.01" name="preco_ate" placeholder="Até R$">

    <label>Duração:</label>
    <input type="number" name="duracao_de" placeholder="De minutos">
    <input type="number" name="duracao_ate" placeholder="Até minutos">

    <label>Jogadores:</label>
    <input type="number" name="jogadores_de" placeholder="De jogadores">
    <input type="number" name="jogadores_ate" placeholder="Até jogadores">

    <label>Idade:</label>
    <input type="number" name="idade_de" placeholder="De idade">
    <input type="number" name="idade_ate" placeholder="Até idade">

    <label>Dificuldade:</label>
    <select name="dificuldade">
        <option value="">Todas</option>
        <option value="facil">Fácil</option>
        <option value="media">Média</option>
        <option value="dificil">Difícil</option>
    </select>

    <label>Tipo do relatório:</label>
    <select name="tipo">
        <option value="sintetico">Sintético</option>
        <option value="analitico">Analítico</option>
    </select>

    <div class="campo-checkbox">
        <input type="checkbox" id="mostrar_inativos" name="mostrar_inativos" value="1">
        <label for="mostrar_inativos">Mostrar inativos</label>
    </div>

    <div class="campo-checkbox">
        <input type="checkbox" id="somente_incompletos" name="somente_incompletos" value="1">
        <label for="somente_incompletos">Somente jogos com informações faltantes</label>
    </div>

    <button type="submit">Gerar PDF</button>

</form>

<br>

<p>
    <a href="listar.php">← Voltar para listagem</a>
</p>

</body>
</html>