<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recomendações - Formiga Lúdica</title>
    <link rel="stylesheet" href="../assets/css/catalogo.css">
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
        .btn-mais {
            background: #e07b00;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-mais:hover { opacity: 0.85; }
        .btn-mais:disabled { opacity: 0.6; cursor: wait; }
    </style>
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
            <h2>Tenho algumas ideias...</h2>
            <p><?= htmlspecialchars($intro) ?></p>
        </div>
    </section>

    <section class="resultado-recomendacoes">

    <div class="titulo-resultado">
        <h2>✨ Jogos escolhidos especialmente para vocês</h2>
        <p>A Formiguinha analisou as respostas e encontrou estes jogos.</p>
    </div>

    <div id="container-recomendacoes">
        <div class="recomendacoes-grid" id="grid-recomendacoes">
            <?php foreach ($recomendacoes as $jogo): ?>
                <div class="card-recomendacao" data-id="<?= $jogo['id'] ?>">
                    <?php
                        $imgSrc = !empty($jogo['imagem'])
                            ? htmlspecialchars($jogo['imagem'])
                            : '../../assets/img/sem-imagem.png';
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

    <div style="text-align:center; margin: 24px 0;">
            <button id="btn-mais-recomendacoes" class="btn-mais">
                🐜 + Recomendações
            </button>
            <p id="msg-fim-catalogo" style="display:none; color:#777; margin-top:12px;">
                🐜 A Formiguinha já trouxe tudo que tinha no catálogo pra esse perfil!
                Se não gostou de nenhuma, fala com o <strong>Jander</strong> que ele pode te ajudar a encontrar o jogo ideal. 💛
            </p>
        </div>

        <!-- dados ocultos para a busca incremental -->
        <input type="hidden" id="dados-descricao" value="<?= htmlspecialchars($queryDescricao ?? '') ?>">
        <input type="hidden" id="dados-jogadores" value="<?= htmlspecialchars($jogadores ?? '') ?>">
        <input type="hidden" id="dados-idade" value="<?= htmlspecialchars($idade ?? '') ?>">
        <input type="hidden" id="dados-tempo" value="<?= htmlspecialchars($tempo ?? '') ?>">
    </div>
    </section>

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

        fetch('/formiga-ludica-mvp/controllers/maisRecomendacoesController.php', {
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
                const imgSrc = jogo.imagem ? jogo.imagem : '../../assets/img/sem-imagem.png';
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