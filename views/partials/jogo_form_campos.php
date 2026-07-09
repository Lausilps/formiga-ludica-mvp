<?php $modoEdicao = $modoEdicao ?? false; ?>

<div class="campo campo-grande">
    <label>Nome<?= $modoEdicao ? '' : ' do jogo' ?>:</label>
    <input type="text" name="nome" required value="<?= htmlspecialchars($nome) ?>">
</div>

<?php if ($modoEdicao): ?>
    <div class="campo">
        <label>Status:</label>
        <select name="ativo">
            <option value="1" <?= $ativo == 1 ? 'selected' : '' ?>>Ativo</option>
            <option value="0" <?= $ativo == 0 ? 'selected' : '' ?>>Inativo</option>
        </select>
    </div>
<?php endif; ?>

<div class="campo campo-grande">
    <label>Descrição:</label>
    <textarea name="descricao" required><?= htmlspecialchars($descricao) ?></textarea>
</div>

<div class="campo">
    <label>Preço:</label>
    <input type="number" name="preco" step="0.01" required value="<?= htmlspecialchars($preco) ?>">
</div>

<div class="campo">
    <label>Mínimo de jogadores:</label>
    <input type="number" name="min_jogadores" required value="<?= htmlspecialchars($min_jogadores) ?>">
</div>

<div class="campo">
    <label>Máximo de jogadores:</label>
    <input type="number" name="max_jogadores" required value="<?= htmlspecialchars($max_jogadores) ?>">
</div>

<div class="campo">
    <label>Idade mínima:</label>
    <input type="number" name="idade_minima" required value="<?= htmlspecialchars($idade_minima) ?>">
</div>

<div class="campo">
    <label>Duração média:</label>
    <input type="number" name="duracao_minutos" required value="<?= htmlspecialchars($duracao_minutos) ?>">
</div>

<div class="campo">
    <label>Dificuldade:</label>
    <select name="dificuldade" required>
        <?php if (!$modoEdicao): ?><option value="">Selecione</option><?php endif; ?>
        <option value="facil" <?= $dificuldade == 'facil' ? 'selected' : '' ?>>Fácil</option>
        <option value="media" <?= $dificuldade == 'media' ? 'selected' : '' ?>>Média</option>
        <option value="dificil" <?= $dificuldade == 'dificil' ? 'selected' : '' ?>>Difícil</option>
    </select>
</div>

<div class="campo campo-grande">
    <label>Resumo das regras:</label>
    <textarea name="resumo_regras"><?= htmlspecialchars($resumo_regras) ?></textarea>
</div>

<div class="campo campo-grande">
    <label>Link do tutorial:</label>
    <input
        type="url"
        name="link_tutorial"
        placeholder="Cole aqui o link do tutorial"
        value="<?= htmlspecialchars($link_tutorial) ?>"
    >
</div>
