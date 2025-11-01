-- Script SQL para adicionar campo de descrição na tabela artista
-- Execute este script no phpMyAdmin ou MySQL

-- Adicionar coluna de descrição se não existir
ALTER TABLE artista ADD COLUMN IF NOT EXISTS artista_descricao TEXT;

-- Copiar descrições dos usuários para artistas
UPDATE artista a 
JOIN usuarios u ON a.artista_id = u.artista_id 
SET a.artista_descricao = u.usuario_descricao 
WHERE u.usuario_descricao IS NOT NULL AND u.usuario_descricao != '';

-- Adicionar descrições padrão para artistas sem descrição
UPDATE artista 
SET artista_descricao = CONCAT('Artista talentoso de ', COALESCE(artista_cidade, 'localização não informada'), '. Explore suas músicas e descubra seu estilo único.') 
WHERE artista_descricao IS NULL OR artista_descricao = '';