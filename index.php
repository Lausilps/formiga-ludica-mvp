<?php

require_once 'config/conexao.php';

$sql = "SELECT
            id_jogo,
            nome,
            imagem,
            descricao,
            preco,
            min_jogadores,
            max_jogadores,
            idade_minima,
            duracao_minutos,
            dificuldade
        FROM jogos
        WHERE ativo = 1
        ORDER BY criado_em DESC, nome ASC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao carregar catálogo.");
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Formiga Lúdica - Catálogo</title>
    <link rel="stylesheet" href="assets/css/catalogo.css">
</head>
<body>

<header class="catalogo-topo">
    <div>
        <h1>FORMIGA LÚDICA - CATÁLOGO</h1>
        <p>Encontre o jogo perfeito para sua próxima jogatina.</p>
    </div>

    <div class="acoes-topo">
        <a href="recomendacao.php" class="btn-recomendar">✨ Recomendar para mim</a>
        <a href="https://wa.me/5537999999999" target="_blank" class="btn-whatsapp">WhatsApp</a>
    </div>
</header>

<section class="filtros-catalogo">
    <input type="text" id="busca-jogo" placeholder="Buscar jogos...">
</section>

<main class="grid-jogos">

    <?php while ($jogo = mysqli_fetch_assoc($resultado)): ?>

        <?php
            $imagem = $jogo['imagem'];

            if (!empty($imagem) && str_starts_with($imagem, 'http')) {
                $srcImagem = $imagem;
            } elseif (!empty($imagem)) {
                $srcImagem = $imagem;
            } else {
                $srcImagem = 'assets/img/sem-imagem.png';
            }
        ?>

        <article class="card-jogo" data-nome="<?= strtolower($jogo['nome']) ?>">

            <div class="imagem-card">
                <img src="<?= $srcImagem ?>" alt="<?= $jogo['nome'] ?>">
            </div>

            <div class="conteudo-card">
                <h2><?= $jogo['nome'] ?></h2>

                <p class="descricao-card">
                    <?= mb_strimwidth($jogo['descricao'] ?? '', 0, 110, '...') ?>
                </p>

                <div class="infos-card">
                    <span>👥 <?= $jogo['min_jogadores'] ?> - <?= $jogo['max_jogadores'] ?></span>
                    <span>⏱ <?= $jogo['duracao_minutos'] ?> min</span>
                    <span>👤 <?= $jogo['idade_minima'] ?>+</span>
                </div>

                <div class="rodape-card">
                    <strong>R$ <?= number_format($jogo['preco'], 2, ',', '.') ?></strong>
                    <button type="button" class="btn-escolher" data-nome="<?= $jogo['nome'] ?>">
                        Escolher
                    </button>
                </div>
            </div>

        </article>

    <?php endwhile; ?>

</main>

<div class="barra-whatsapp" id="barra-whatsapp" style="display:none;">
    <span id="qtd-selecionados">0 jogos selecionados</span>
    <button type="button" id="enviar-whatsapp">Enviar pelo WhatsApp</button>
</div>

<script>
const busca = document.getElementById('busca-jogo');
const cards = document.querySelectorAll('.card-jogo');
const selecionados = [];
const barra = document.getElementById('barra-whatsapp');
const qtdSelecionados = document.getElementById('qtd-selecionados');

busca.addEventListener('input', function () {
    const termo = this.value.toLowerCase();

    cards.forEach(card => {
        const nome = card.dataset.nome;

        card.style.display = nome.includes(termo) ? 'block' : 'none';
    });
});

document.querySelectorAll('.btn-escolher').forEach(botao => {
    botao.addEventListener('click', function () {
        const nome = this.dataset.nome;
        const index = selecionados.indexOf(nome);

        if (index === -1) {
            selecionados.push(nome);
            this.textContent = 'Selecionado';
            this.classList.add('selecionado');
        } else {
            selecionados.splice(index, 1);
            this.textContent = 'Escolher';
            this.classList.remove('selecionado');
        }

        if (selecionados.length > 0) {
            barra.style.display = 'flex';
            qtdSelecionados.textContent = selecionados.length + ' jogo(s) selecionado(s)';
        } else {
            barra.style.display = 'none';
        }
    });
});

document.getElementById('enviar-whatsapp').addEventListener('click', function () {
    const mensagem = `Olá! Tenho interesse em alugar os jogos:\n\n- ${selecionados.join('\n- ')}\n\nPode me passar disponibilidade e valores?`;

    const telefone = '5537999139354';
    const url = `https://wa.me/${telefone}?text=${encodeURIComponent(mensagem)}`;

    window.open(url, '_blank');
});
</script>

</body>
</html>