<?php

require_once '../../config/conexao.php';
require_once '../../helpers/logHelper.php';
require_once '../../helpers/authHelper.php';
require_once '../../helpers/destaquesHelper.php';
require_once '../../helpers/assetHelper.php';

protegerAdmin();

$destaques = listarDestaques($conexao);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Destaques da loja</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo_formiga_ludica.png">

    <link rel="stylesheet" href="../../assets/css/global.css<?= assetVersao('assets/css/global.css') ?>">
    <link rel="stylesheet" href="../../assets/css/editar.css<?= assetVersao('assets/css/editar.css') ?>">
</head>
<body class="admin-body">

<?php
    $tituloPagina = 'Destaques da loja';
    $subtituloPagina = 'Escolha os jogos que aparecem no carrossel "Recomendação da loja" no catálogo.';
    $mostrarLogout = true;
    include '../partials/admin_header.php';
?>

<main class="admin-container">

    <section class="card-admin">

        <div class="area-galeria">

            <h2>Jogos em destaque</h2>
            <p class="descricao-galeria">A primeira foto é a primeira do carrossel. Use as setas pra reordenar.</p>

            <div id="destaques-conteudo">
                <?php include '../partials/destaques_lista.php'; ?>
            </div>

        </div>

        <div class="area-galeria">

            <h2>Adicionar jogo aos destaques</h2>
            <p class="descricao-galeria">Digite o nome do jogo e clique no + na sugestão que quiser adicionar.</p>

            <div class="busca-destaque-area">
                <input type="text" id="busca-destaque" placeholder="Digite o nome do jogo..." autocomplete="off">
                <div id="sugestoes-destaque" class="sugestoes-destaque"></div>
            </div>

        </div>

        <div class="acoes-form">
            <a href="listar.php" class="btn-voltar">← Voltar para listagem</a>
        </div>

    </section>

</main>

<script>
    const destaquesConteudo = document.getElementById('destaques-conteudo');
    const campoBusca = document.getElementById('busca-destaque');
    const listaSugestoes = document.getElementById('sugestoes-destaque');
    let debounceBusca = null;

    async function atualizarDestaques(formData) {
        const resposta = await fetch('../../controllers/destaquesController.php', {
            method: 'POST',
            body: formData
        });
        destaquesConteudo.innerHTML = await resposta.text();
    }

    function acaoDestaque(acao, idJogo, direcao) {
        const formData = new FormData();
        formData.append('acao', acao);
        formData.append('id_jogo', idJogo);
        formData.append('direcao', direcao || '');
        atualizarDestaques(formData);
    }

    function adicionarAoDestaque(idJogo) {
        const formData = new FormData();
        formData.append('acao', 'adicionar');
        formData.append('id_jogo', idJogo);

        atualizarDestaques(formData).then(() => {
            campoBusca.value = '';
            listaSugestoes.innerHTML = '';
            listaSugestoes.style.display = 'none';
        });
    }

    campoBusca.addEventListener('input', function () {
        clearTimeout(debounceBusca);
        const termo = this.value.trim();

        if (termo === '') {
            listaSugestoes.innerHTML = '';
            listaSugestoes.style.display = 'none';
            return;
        }

        debounceBusca = setTimeout(() => {
            fetch(`../../controllers/buscarJogosParaDestaqueAjax.php?termo=${encodeURIComponent(termo)}`)
                .then(res => res.json())
                .then(jogos => {
                    if (!jogos.length) {
                        listaSugestoes.innerHTML = '<p class="sugestao-vazia">Nenhum jogo encontrado.</p>';
                        listaSugestoes.style.display = 'block';
                        return;
                    }

                    listaSugestoes.innerHTML = jogos.map(jogo => `
                        <div class="sugestao-item">
                            <img src="${jogo.imagem}" alt="${jogo.nome}">
                            <span>${jogo.nome}</span>
                            <button type="button" class="btn-add-destaque" onclick="adicionarAoDestaque(${jogo.id})">+</button>
                        </div>
                    `).join('');
                    listaSugestoes.style.display = 'block';
                });
        }, 300);
    });

    document.addEventListener('click', function (evento) {
        if (!listaSugestoes.contains(evento.target) && evento.target !== campoBusca) {
            listaSugestoes.style.display = 'none';
        }
    });
</script>

</body>
</html>
