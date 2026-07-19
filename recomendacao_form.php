<?php
require_once 'config/conexao.php';
require_once 'helpers/sessaoHelper.php';
iniciarSessaoPersistente();

$dadosAnteriores = $_SESSION['form_recomendacao'] ?? [];
unset($_SESSION['form_recomendacao']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendação - Formiga Lúdica</title>
    <link rel="icon" type="image/png" href="assets/img/logo_formiga_ludica.png">

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/recomendar_form.css">
</head>
<body class="recomendar-body">

<header class="recomendar-header">
    <div class="recomendar-header-conteudo">

        <a href="index.php"><img src="assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="recomendar-logo"></a>

        <div>
            <span class="recomendar-label">Formiguinha IA</span>
            <h1>Recomendação</h1>
            <p>Conte como será sua jogatina e a Formiguinha encontra jogos que combinam com você.</p>
        </div>

    </div>
</header>

<main class="recomendar-container">

    <section class="card-recomendacao-form">

        <div class="intro-card">
            <h2>🐜 O que você quer jogar hoje?</h2>
            <p>
                Pode escrever do seu jeito: churrasco com amigos, noite em família,
                jogos rápidos, mímica, estratégia, risada, crianças ou casal.
            </p>
        </div>

        <?php if (isset($_GET['erro'])): ?>
            <?php
            $mensagens = [
                1 => '🐜 Ops, esqueceu de contar pra Formiguinha o que você procura! Descreve sua jogatina.',
                2 => '🐜 A Formiguinha não conseguiu entender seu pedido agora. Tenta de novo em instantes!',
                3 => '🐜 Não encontrei jogos com esse perfil no catálogo. Tenta ajustar os filtros!',
                4 => '🐜 Estou bombando de pedidos agora! Aguarda uns 30 segundinhos e tenta de novo 💛',
                5 => '🐜 Algo deu errado por aqui. Tenta novamente em instantes!',
            ];
            $codigo = (int)$_GET['erro'];
            ?>
            <div class="alerta-recomendacao">
                <?= $mensagens[$codigo] ?? 'Algo deu errado. Tenta novamente!' ?>
            </div>
        <?php endif; ?>

        <form action="controllers/recomendacaoController.php" method="POST" class="form-recomendacao" id="form-recomendacao">

            <div class="campo campo-grande">
                <label>Descreva sua jogatina:</label>
                <textarea
                    name="descricao_sessao"
                    required
                    placeholder="Ex: noite divertida com 6 amigos, queremos jogos interativos, de mímica, risada e pouca regra."
                ><?= htmlspecialchars($dadosAnteriores['descricao_sessao'] ?? '') ?></textarea>
            </div>

            <div class="grid-form">

                <div class="campo">
                    <label>Quantas pessoas vão jogar?</label>
                    <input
                        type="number"
                        name="jogadores"
                        min="1"
                        required
                        placeholder="Ex: 6"
                        value="<?= htmlspecialchars($dadosAnteriores['jogadores'] ?? '') ?>"
                    >
                </div>

                <div class="campo">
                    <label>Idade mínima do grupo:</label>
                    <input
                        type="number"
                        name="idade"
                        min="1"
                        required
                        placeholder="Ex: 10"
                        value="<?= htmlspecialchars($dadosAnteriores['idade'] ?? '') ?>"
                    >
                </div>

                <div class="campo">
                    <label>Tempo disponível:</label>
                    <select name="tempo" required>
                        <option value="">Selecione</option>
                        <?php
                        $opcoesTempo = [
                            30 => 'Até 30 minutos',
                            60 => 'Até 1 hora',
                            90 => 'Até 1h30',
                            999 => 'Tanto faz'
                        ];

                        $tempoSelecionado = $dadosAnteriores['tempo'] ?? '';

                        foreach ($opcoesTempo as $valor => $label):
                        ?>
                            <option value="<?= $valor ?>" <?= ($tempoSelecionado == $valor) ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="acoes-recomendacao">
                <button type="submit" class="btn-recomendar-form">
                    ✨ Ver recomendações
                </button>

                <a href="index.php" class="btn-voltar-catalogo">
                    ← Voltar ao catálogo
                </a>
            </div>

        </form>

    </section>

</main>

<div id="overlay-carregando">
    <div class="modal-carregando">
        <video id="video-formiguinha-carregando" src="assets/img/formiguinha-pesquisando.mp4" class="gif-formiguinha" autoplay loop muted playsinline></video>
        <p>🐜 A Formiguinha está fuçando o catálogo em busca dos jogos perfeitos para você...</p>
    </div>
</div>

<script>
    document.getElementById('form-recomendacao').addEventListener('submit', function () {
        document.getElementById('overlay-carregando').style.display = 'flex';

        // No celular, video que fica escondido (display:none) desde o
        // carregamento da página às vezes não respeita o autoplay sozinho
        // na primeira vez que fica visível — chama o play() na mão pra
        // garantir, já que isso acontece dentro do gesto do usuário (o
        // clique em "Ver recomendações").
        const videoCarregando = document.getElementById('video-formiguinha-carregando');
        videoCarregando.currentTime = 0;
        videoCarregando.play().catch(() => {});
    });
</script>

<?php include 'views/partials/footer.php'; ?>

</body>
</html>