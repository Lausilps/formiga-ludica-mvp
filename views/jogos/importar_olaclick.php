<?php
require_once '../../helpers/authHelper.php';
protegerAdmin();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar OlaClick</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
</head>
<body>

<h1>Importar jogos do OlaClick</h1>

<form action="../../controllers/importarOlaClickController.php" method="POST">

    <label for="json_olaclick">Cole aqui o JSON do OlaClick:</label><br>

    <textarea
        name="json_olaclick"
        id="json_olaclick"
        required
        style="width:100%; max-width:900px; min-height:350px;"
    ></textarea>

    <br><br>

    <button type="submit">Importar jogos</button>

</form>

<p>
    <a href="listar.php">Voltar</a>
</p>

</body>
</html>