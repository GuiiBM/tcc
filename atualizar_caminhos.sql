-- Script SQL para atualizar os caminhos no banco de dados
-- Execute este script no phpMyAdmin

-- Atualizar caminhos das imagens dos artistas
UPDATE artista SET artista_image = REPLACE(artista_image, 'audios/', 'Componentes/Armazenamento/imagens/') WHERE artista_image LIKE '%audios/%';
UPDATE artista SET artista_image = REPLACE(artista_image, 'imagens/', 'Componentes/Armazenamento/imagens/') WHERE artista_image LIKE '%imagens/%';

-- Atualizar caminhos das capas das músicas  
UPDATE musica SET musica_capa = REPLACE(musica_capa, 'audios/', 'Componentes/Armazenamento/imagens/') WHERE musica_capa LIKE '%audios/%';
UPDATE musica SET musica_capa = REPLACE(musica_capa, 'imagens/', 'Componentes/Armazenamento/imagens/') WHERE musica_capa LIKE '%imagens/%';

-- Atualizar caminhos dos áudios das músicas
UPDATE musica SET musica_link = REPLACE(musica_link, 'imagens/', 'Componentes/Armazenamento/audios/') WHERE musica_link LIKE '%imagens/%';
UPDATE musica SET musica_link = REPLACE(musica_link, 'audios/', 'Componentes/Armazenamento/audios/') WHERE musica_link LIKE '%audios/%';

-- Verificar os dados atualizados
SELECT 'ARTISTAS' as tabela, artista_nome as nome, artista_image as caminho FROM artista WHERE artista_image != ''
UNION ALL
SELECT 'MÚSICAS - CAPAS' as tabela, musica_titulo as nome, musica_capa as caminho FROM musica
UNION ALL  
SELECT 'MÚSICAS - ÁUDIOS' as tabela, musica_titulo as nome, musica_link as caminho FROM musica;