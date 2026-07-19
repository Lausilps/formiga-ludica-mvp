<?php
// Espera $imagensGaleria (array de listarImagensJogo) e $jogo (pra alt text)
// já definidos por quem inclui essa partial. Reaproveitada tanto na
// primeira carga de editar.php quanto na resposta AJAX de
// jogoImagensController.php, pra manter o HTML da galeria idêntico nos
// dois casos sem duplicar código.
?>
<?php if (empty($imagensGaleria)): ?>
    <p class="sem-fotos">Nenhuma foto cadastrada ainda.</p>
<?php else: ?>
    <div class="galeria-fotos">
        <?php foreach ($imagensGaleria as $indice => $imagem): ?>
            <?php
                $srcGaleria = str_starts_with($imagem['caminho'], 'http')
                    ? $imagem['caminho']
                    : '../../' . $imagem['caminho'];
            ?>
            <div class="foto-galeria">
                <img src="<?= htmlspecialchars($srcGaleria) ?>" alt="Foto <?= $indice + 1 ?> de <?= htmlspecialchars($jogo['nome']) ?>">

                <?php if ($indice === 0): ?>
                    <span class="etiqueta-capa">Capa</span>
                <?php endif; ?>

                <div class="acoes-foto-galeria">
                    <?php if ($indice !== 0): ?>
                        <button type="button" class="btn-foto" onclick="acaoGaleria('capa', <?= $imagem['id_imagem'] ?>)" title="Tornar capa">★</button>
                    <?php endif; ?>

                    <?php if ($indice > 0): ?>
                        <button type="button" class="btn-foto" onclick="acaoGaleria('mover', <?= $imagem['id_imagem'] ?>, 'esquerda')" title="Mover pra esquerda">←</button>
                    <?php endif; ?>

                    <?php if ($indice < count($imagensGaleria) - 1): ?>
                        <button type="button" class="btn-foto" onclick="acaoGaleria('mover', <?= $imagem['id_imagem'] ?>, 'direita')" title="Mover pra direita">→</button>
                    <?php endif; ?>

                    <button type="button" class="btn-foto btn-foto-excluir" onclick="excluirFotoGaleria(<?= $imagem['id_imagem'] ?>)" title="Excluir foto">🗑</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
