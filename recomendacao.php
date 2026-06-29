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
        <img src="assets/img/logo-formiga-ludica.png" alt="Formiga Lúdica" class="logo-topo">

        <div class="texto-topo">
            <span class="titulo-catalogo">RECOMENDAÇÃO</span>
            <p>Responda rapidinho e a Formiguinha te ajuda a escolher.</p>
        </div>
    </div>
</header>

<section class="filtros-catalogo">
    <form action="controllers/recomendacaoController.php" method="POST">

        <label>Quantas pessoas vão jogar?</label>
        <input type="number" name="jogadores" min="1" required>

        <label>Qual a idade mínima do grupo?</label>
        <input type="number" name="idade" min="1" required>

        <label>Quanto tempo vocês têm?</label>
        <select name="tempo" required>
            <option value="">Selecione</option>
            <option value="30">Até 30 minutos</option>
            <option value="60">Até 1 hora</option>
            <option value="90">Até 1h30</option>
            <option value="999">Tanto faz</option>
        </select>

        <label>Experiência do grupo</label>
        <select name="experiencia" required>
            <option value="">Selecione</option>
            <option value="iniciante">Iniciante</option>
            <option value="intermediario">Intermediário</option>
            <option value="experiente">Experiente</option>
        </select>

        <label>Ocasião</label>
        <select name="ocasiao" required>
            <option value="">Selecione</option>
            <option value="familia">Família</option>
            <option value="casal">Casal</option>
            <option value="amigos">Amigos</option>
            <option value="festa">Festa</option>
            <option value="criancas">Crianças</option>
        </select>

        <button type="submit">✨ Ver recomendações</button>

    </form>
</section>

<p style="text-align:center;">
    <a href="index.php">← Voltar ao catálogo</a>
</p>

</body>
</html>