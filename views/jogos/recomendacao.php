<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recomendações - Formiga Lúdica</title>
    <link rel="stylesheet" href="../assets/css/catalogo.css">
    <link rel="stylesheet" href="../assets/css/recomendacao_resultado.css">
</head>
<body>

<header class="catalogo-topo">
    <div class="info-topo">
        <img src="../assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="logo-topo">
        <div class="texto-topo">
            <span class="titulo-catalogo">RECOMENDAÇÕES</span>
            <p>A Formiguinha escolheu especialmente pra você 🐜✨</p>
        </div>
    </div>
</header>

<?php if (!empty($recomendacoes)): ?>

    <section class="fala-formiguinha">

        <div class="balao-fala">
            <h2>🐜 E aí, formigão!</h2>
            <p><?= htmlspecialchars($intro) ?></p>
        </div>
    </section>

    <?php if (!empty($recomendacoes)): ?>

    <div id="container-recomendacoes">
        <div class="recomendacoes-grid" id="grid-recomendacoes">
            <?php foreach ($recomendacoes as $jogo): ?>
                <div class="card-recomendacao" data-id="<?= $jogo['id'] ?>">
                    <?php
                        $imgSrc = !empty($jogo['imagem'])
                            ? htmlspecialchars($jogo['imagem'])
                            : '../assets/img/sem-imagem.png';
                    ?>

                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($jogo['nome']) ?>">

                    <div class="card-body">
                        <h3><?= htmlspecialchars($jogo['nome']) ?></h3>

                        <p class="card-motivo">
                            <?= htmlspecialchars($jogo['motivo']) ?>
                        </p>

                        <p class="card-meta">
                            👥 <?= $jogo['min_jogadores'] ?>–<?= $jogo['max_jogadores'] ?> jogadores &nbsp;|&nbsp;
                            ⏱ <?= $jogo['duracao'] ?> min &nbsp;|&nbsp;
                            🎯 <?= ucfirst($jogo['dificuldade']) ?>
                        </p>

                        <p class="card-preco">
                            R$ <?= number_format($jogo['preco'], 2, ',', '.') ?>/dia
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <div class="acoes-mais-recomendacoes">
            <button id="btn-mais-recomendacoes" class="btn-mais">
                🐜 + Recomendações
            </button>
            <p id="msg-fim-catalogo" style="display:none; color:#777; margin-top:12px;">
                🐜 A Formiguinha já trouxe tudo que tinha no catálogo pra esse perfil!
                Se não gostou de nenhuma, fala com o <strong>Jander</strong> que ele te ajuda pessoalmente. 💛
            </p>
        </div>

        <!-- dados ocultos para a busca incremental -->
        <input type="hidden" id="dados-descricao" value="<?= htmlspecialchars($queryDescricao ?? '') ?>">
        <input type="hidden" id="dados-jogadores" value="<?= htmlspecialchars($jogadores ?? '') ?>">
        <input type="hidden" id="dados-idade" value="<?= htmlspecialchars($idade ?? '') ?>">
        <input type="hidden" id="dados-tempo" value="<?= htmlspecialchars($tempo ?? '') ?>">
    </div>

    <script>
    document.getElementById('btn-mais-recomendacoes').addEventListener('click', function() {
        const btn = this;
        const msgFim = document.getElementById('msg-fim-catalogo');
        const grid = document.getElementById('grid-recomendacoes');

        // Pega os IDs já exibidos na tela
        const idsExibidos = Array.from(document.querySelectorAll('.card-recomendacao'))
            .map(card => card.dataset.id);

        btn.disabled = true;
        btn.textContent = '🐜 Procurando mais opções...';

        const formData = new FormData();
        formData.append('descricao_sessao', document.getElementById('dados-descricao').value);
        formData.append('jogadores', document.getElementById('dados-jogadores').value);
        formData.append('idade', document.getElementById('dados-idade').value);
        formData.append('tempo', document.getElementById('dados-tempo').value);
        idsExibidos.forEach(id => formData.append('ids_exibidos[]', id));

        fetch('maisRecomendacoesController.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = '🐜 + Recomendações';

            if (data.fim) {
                btn.style.display = 'none';
                msgFim.style.display = 'block';
                return;
            }

            if (data.erro === 'limite_excedido') {
                alert('A Formiguinha está bombando de pedidos agora! Tenta de novo em alguns segundos.');
                return;
            }

            if (data.erro) {
                alert('Ops, algo deu errado. Tenta novamente!');
                return;
            }

            // Adiciona os novos cards no grid
            data.recomendacoes.forEach(jogo => {
                const imgSrc = jogo.imagem ? jogo.imagem : '../assets/img/sem-imagem.png';
                const card = document.createElement('div');
                card.className = 'card-recomendacao';
                card.dataset.id = jogo.id;
                card.innerHTML = `
                    <img src="${imgSrc}" alt="${jogo.nome}">
                    <div class="card-body">
                        <h3>${jogo.nome}</h3>
                        <p class="card-motivo">${jogo.motivo}</p>
                        <p class="card-meta">
                            👥 ${jogo.min_jogadores}–${jogo.max_jogadores} jogadores &nbsp;|&nbsp;
                            ⏱ ${jogo.duracao} min &nbsp;|&nbsp;
                            🎯 ${jogo.dificuldade.charAt(0).toUpperCase() + jogo.dificuldade.slice(1)}
                        </p>
                        <p class="card-preco">R$ ${parseFloat(jogo.preco).toFixed(2).replace('.', ',')}/dia</p>
                    </div>
                `;
                grid.appendChild(card);
            });
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = '🐜 + Recomendações';
            alert('Erro ao buscar mais recomendações. Tenta novamente!');
        });
    });
    </script>

    <?php endif; ?>

<?php else: ?>
    <div class="sem-resultado">
        <p>😕 Não encontrei recomendações certinhas pra esse perfil.</p>
        <p>Tenta ajustar o número de jogadores ou o tempo disponível!</p>
    </div>
<?php endif; ?>

<a class="btn-voltar" href="../recomendacao_form.php">← Tentar outra busca</a>
<a class="btn-voltar" href="../index.php">← Voltar ao catálogo</a>

</body>
</html>