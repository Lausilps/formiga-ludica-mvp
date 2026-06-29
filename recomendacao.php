<?php
require_once 'config/conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recomendação - Formiga Lúdica</title>
    <link rel="stylesheet" href="assets/css/catalogo.css">
</head>
<body>

<header class="catalogo-topo">
    <div class="info-topo">
        <img src="assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="logo-topo">

        <div class="texto-topo">
            <span class="titulo-catalogo">RECOMENDAÇÃO</span>
            <p>Responda rapidinho e a Formiguinha te ajuda a escolher.</p>
        </div>
    </div>
</header>

<section class="filtros-catalogo">
    <form action="controllers/recomendacaoController.php" method="POST">

        <label>Descreva sua jogatina:</label>
        <textarea
            name="descricao_sessao"
            required
            placeholder="Ex: noite divertida com 6 amigos, queremos jogos interativos, de mímica, risada e pouca regra."
        ></textarea>

        <label>Quantas pessoas vão jogar?</label>
        <input type="number" name="jogadores" min="1" required>

        <label>Idade mínima do grupo:</label>
        <input type="number" name="idade" min="1" required>

        <label>Tempo disponível:</label>
        <select name="tempo" required>
            <option value="">Selecione</option>
            <option value="30">Até 30 minutos</option>
            <option value="60">Até 1 hora</option>
            <option value="90">Até 1h30</option>
            <option value="999">Tanto faz</option>
        </select>
        
        <button type="submit">✨ Ver recomendações</button>

    </form>
</section>

<p style="text-align:center;">
    <a href="index.php">← Voltar ao catálogo</a>
</p>

</body>
</html>