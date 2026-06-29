<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recomendações - Formiga Lúdica</title>
    <link rel="stylesheet" href="assets/css/catalogo.css">
    <style>
        .recomendacoes-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
            padding: 32px 16px;
        }
        .card-recomendacao {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.10);
            width: 280px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .card-recomendacao img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .card-body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .card-body h3 { margin: 0; font-size: 1.1rem; }
        .card-motivo { font-size: 0.92rem; color: #444; flex: 1; }
        .card-meta { font-size: 0.85rem; color: #777; }
        .card-preco { font-weight: bold; color: #e07b00; font-size: 1rem; }
        .intro-texto {
            text-align: center;
            font-size: 1.1rem;
            padding: 24px 16px 0;
            color: #333;
        }
        .btn-voltar {
            display: block;
            text-align: center;
            margin: 24px auto;
            color: #555;
        }
        .sem-resultado {
            text-align: center;
            padding: 48px 16px;
            color: #666;
        }
    </style>
</head>
<body>

<header class="catalogo-topo">
    <div class="info-topo">
        <img src="assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="logo-topo">
        <div class="texto-topo">
            <span class="titulo-catalogo">RECOMENDAÇÕES</span>
            <p>A Formiguinha escolheu especialmente pra você 🐜✨</p>
        </div>
    </div>
</header>

<?php if (!empty($recomendacoes)): ?>

    <p class="intro-texto">🐜 <?= htmlspecialchars($intro) ?></p>

    <div class="recomendacoes-grid">
        <?php foreach ($recomendacoes as $jogo): ?>
        <div class="card-recomendacao">
            <?php
                $imgSrc = !empty($jogo['imagem'])
                    ? htmlspecialchars($jogo['imagem'])
                    : 'assets/img/sem-imagem.png';
            ?>
            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($jogo['nome']) ?>">
            <div class="card-body">
                <h3><?= htmlspecialchars($jogo['nome']) ?></h3>
                <p class="card-motivo"><?= htmlspecialchars($jogo['motivo']) ?></p>
                <p class="card-meta">
                    👥 <?= $jogo['min_jogadores'] ?>–<?= $jogo['max_jogadores'] ?> jogadores &nbsp;|&nbsp;
                    ⏱ <?= $jogo['duracao'] ?> min &nbsp;|&nbsp;
                    🎯 <?= ucfirst($jogo['dificuldade']) ?>
                </p>
                <p class="card-preco">R$ <?= number_format($jogo['preco'], 2, ',', '.') ?>/dia</p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="sem-resultado">
        <p>😕 Não encontrei recomendações certinhas pra esse perfil.</p>
        <p>Tenta ajustar o número de jogadores ou o tempo disponível!</p>
    </div>
<?php endif; ?>

<a class="btn-voltar" href="recomendacao.php">← Tentar outra busca</a>
<a class="btn-voltar" href="index.php">← Voltar ao catálogo</a>

</body>
</html>