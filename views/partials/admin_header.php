<?php $mostrarLogout = $mostrarLogout ?? false; ?>

<header class="admin-header">
    <div class="admin-header-conteudo">
        <?php if ($mostrarLogout): ?>

            <div class="admin-logo-area">
                <img src="../../assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="admin-logo">

                <div>
                    <span class="admin-label">Painel administrativo</span>
                    <h1><?= htmlspecialchars($tituloPagina) ?></h1>
                    <p><?= htmlspecialchars($subtituloPagina) ?></p>
                </div>
            </div>

            <a href="../../logout.php" class="btn-logout-topo">
                Sair
            </a>

        <?php else: ?>

            <img src="../../assets/img/logo_formiga_ludica.png" alt="Formiga Lúdica" class="admin-logo">

            <div>
                <span class="admin-label">Painel administrativo</span>
                <h1><?= htmlspecialchars($tituloPagina) ?></h1>
                <p><?= htmlspecialchars($subtituloPagina) ?></p>
            </div>

        <?php endif; ?>
    </div>
</header>
