-- Rode no console do Railway. Coluna pro carrossel "Recomendação da loja".
-- NULL = jogo nao esta em destaque. Um numero = esta, nessa posicao.

ALTER TABLE jogos ADD COLUMN ordem_destaque INT DEFAULT NULL;
