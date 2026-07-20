<?php
// Espera $destaques (array de listarDestaques()) já definido por quem
// inclui essa partial. Reaproveitada na primeira carga de destaques.php e
// na resposta AJAX de destaquesController.php.
?>
<?php if (empty($destaques)): ?>
    <p class="sem-fotos">Nenhum jogo em destaque ainda. Busca um jogo aí embaixo pra adicionar.</p>
<?php else: ?>
    <div class="galeria-fotos">
        <?php foreach ($destaques as $indice => $jogo): ?>
            <?php
                $srcImagem = !empty($jogo['imagem'])
                    ? (str_starts_with($jogo['imagem'], 'http') ? $jogo['imagem'] : '../../' . $jogo['imagem'])
                    : '../../assets/img/sem-imagem.png';
            ?>
            <div class="foto-galeria">
                <img src="<?= htmlspecialchars($srcImagem) ?>" alt="<?= htmlspecialchars($jogo['nome']) ?>">
                <p class="nome-destaque"><?= htmlspecialchars($jogo['nome']) ?></p>

                <div class="acoes-foto-galeria">
                    <?php if ($indice > 0): ?>
                        <button type="button" class="btn-foto" onclick="acaoDestaque('mover', <?= $jogo['id_jogo'] ?>, 'esquerda')" title="Mover pra esquerda">←</button>
                    <?php endif; ?>

                    <?php if ($indice < count($destaques) - 1): ?>
                        <button type="button" class="btn-foto" onclick="acaoDestaque('mover', <?= $jogo['id_jogo'] ?>, 'direita')" title="Mover pra direita">→</button>
                    <?php endif; ?>

                    <button type="button" class="btn-foto btn-foto-excluir" onclick="acaoDestaque('remover', <?= $jogo['id_jogo'] ?>)" title="Remover dos destaques">🗑</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
