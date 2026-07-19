<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendações - Formiga Lúdica</title>
    <link rel="icon" type="image/png" href="../assets/img/logo_formiga_ludica.png">
    <link rel="stylesheet" href="../assets/css/catalogo.css">
    <link rel="stylesheet" href="../assets/css/recomendacao_resultado.css">
    <script src="../assets/js/carrinho.js"></script>
</head>
<body>

<header class="topo-barra">
    <div class="info-topo">
        <a href="../index.php"><img src="../assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="logo-topo"></a>
        <div class="texto-topo">
            <span class="titulo-catalogo">RECOMENDAÇÕES</span>
            <p>A Formiguinha escolheu especialmente pra você 🐜✨</p>
        </div>
    </div>
    <div class="topo-acoes">
        <a href="../recomendacao_form.php" class="mini-recomendacao">
            <img src="../assets/img/formiguinha-rag.webp" alt="Formiguinha recomendando">
            <span>Recomendar para mim</span>
        </a>
        <button type="button" class="btn-carrinho-topo" id="abrir-modal-pedido" aria-label="Ver pedido">
            🛒
            <span class="carrinho-badge" id="carrinho-badge">0</span>
        </button>
    </div>
</header>

<?php if (!empty($recomendacoes)): ?>

    <section class="fala-formiguinha">
        <img src="../assets/img/formiguinha-falando.png" alt="Formiguinha" class="formiguinha-fala">

        <div class="balao-fala">
            <span class="selo-formiguinha">🐜 Formiguinha</span>
            <h2>Acho que encontrei algo...</h2>
            <p><?= htmlspecialchars((string) $intro) ?></p>
        </div>
    </section>

    <?php if (!empty($recomendacoes)): ?>

    <div id="container-recomendacoes">
        <div class="recomendacoes-grid" id="grid-recomendacoes">
            <?php foreach ($recomendacoes as $jogo): ?>
                <div class="card-recomendacao" data-id="<?= $jogo['id'] ?>" data-nome="<?= htmlspecialchars($jogo['nome']) ?>" data-preco="<?= $jogo['preco'] ?>">
                    <?php
                        $imgSrc = !empty($jogo['imagem'])
                            ? htmlspecialchars($jogo['imagem'])
                            : '../assets/img/sem-imagem.png';
                    ?>

                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($jogo['nome']) ?>">

                    <div class="card-body">
                        <h3><?= htmlspecialchars($jogo['nome']) ?></h3>

                        <p class="card-motivo">
                            <?= htmlspecialchars((string) ($jogo['motivo'] ?? '')) ?>
                        </p>

                        <p class="card-meta">
                            👥 <?= $jogo['min_jogadores'] ?>–<?= $jogo['max_jogadores'] ?> jogadores &nbsp;|&nbsp;
                            ⏱ <?= $jogo['duracao'] ?> min &nbsp;|&nbsp;
                            🎯 <?= formatarDificuldade($jogo['dificuldade']) ?>
                        </p>

                        <?php if (!empty($jogo['link_ver_ludopedia'])): ?>
                            <a href="<?= htmlspecialchars($jogo['link_ver_ludopedia']) ?>" target="_blank" class="btn-ludopedia">
                                <img src="../assets/img/logo-ludopedia.png" alt="Ludopedia">
                                Ver na Ludopedia
                            </a>
                        <?php endif; ?>

                        <div class="rodape-card">
                            <span class="card-preco">R$ <?= number_format($jogo['preco'], 2, ',', '.') ?>/dia</span>
                            <button type="button" class="btn-escolher" data-nome="<?= htmlspecialchars($jogo['nome']) ?>">Escolher</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <div class="acoes-mais-recomendacoes">
            <button id="btn-mais-recomendacoes" class="btn-mais">
                + Recomendações
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

    <!-- Modal do pedido -->
    <div class="modal" id="modal-pedido">
        <div class="modal-conteudo">
            <button type="button" class="fechar-modal" id="fechar-modal-pedido">×</button>
            <h2>Conferir pedido</h2>
            <p>Confira os jogos selecionados antes de enviar pelo WhatsApp.</p>
            <div id="lista-pedido"></div>
            <strong id="total-pedido"></strong>
            <div class="acoes-modal">
                <button type="button" id="continuar-escolhendo">Continuar escolhendo</button>
                <button type="button" id="confirmar-whatsapp">Enviar para WhatsApp</button>
            </div>
        </div>
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
        btn.textContent = 'Procurando mais opções...';

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
            btn.textContent = '+ Recomendações';

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
                card.dataset.nome = jogo.nome;
                card.dataset.preco = jogo.preco;
                card.innerHTML = `
                    <img src="${imgSrc}" alt="${jogo.nome}">
                    <div class="card-body">
                        <h3>${jogo.nome}</h3>
                        <p class="card-motivo">${jogo.motivo}</p>
                        <p class="card-meta">
                            👥 ${jogo.min_jogadores}–${jogo.max_jogadores} jogadores &nbsp;|&nbsp;
                            ⏱ ${jogo.duracao} min &nbsp;|&nbsp;
                            🎯 ${jogo.dificuldade === 'nao_informada' ? '-' : jogo.dificuldade.charAt(0).toUpperCase() + jogo.dificuldade.slice(1)}
                        </p>
                        ${jogo.link_ver_ludopedia ? `
                        <a href="${jogo.link_ver_ludopedia}" target="_blank" class="btn-ludopedia">
                            <img src="../assets/img/logo-ludopedia.png" alt="Ludopedia">
                            Ver na Ludopedia
                        </a>` : ''}
                        <div class="rodape-card">
                            <span class="card-preco">R$ ${parseFloat(jogo.preco).toFixed(2).replace('.', ',')}/dia</span>
                            <button type="button" class="btn-escolher" data-nome="${jogo.nome}">Escolher</button>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
            carrinhoUI.atualizarBotoesSelecionados();
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = '+ Recomendações';
            alert('Erro ao buscar mais recomendações. Tenta novamente!');
        });
    });

    // ============================================================
    // CARRINHO / SELEÇÃO DE JOGOS PARA O WHATSAPP
    // ============================================================
    const gridRecomendacoes = document.getElementById('grid-recomendacoes');

    const carrinhoUI = Carrinho.iniciarUI({
        botaoEscolherSeletor: '#grid-recomendacoes .btn-escolher'
    });

    // Delegação de evento: cobre os cards já renderizados e os que forem adicionados depois
    gridRecomendacoes.addEventListener('click', function(e) {
        const botao = e.target.closest('.btn-escolher');
        if (!botao) return;
        const card = botao.closest('.card-recomendacao');
        carrinhoUI.alternarJogoSelecionado({ nome: card.dataset.nome, preco: card.dataset.preco });
    });
    </script>

    <?php endif; ?>

<?php else: ?>
    <div class="sem-resultado">
        <p>😕 Não encontrei recomendações certinhas pra esse perfil.</p>
        <p>Tenta ajustar o número de jogadores ou o tempo disponível!</p>
    </div>
<?php endif; ?>

<div class="acoes-finais">
    <a class="btn-voltar" href="../recomendacao_form.php">← Tentar outra busca</a>
    <a class="btn-voltar" href="../index.php">← Voltar ao catálogo</a>
</div>

<?php $prefixoAssets = '../'; include __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>