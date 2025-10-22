# Instruções para Atualizar o Banco de Dados

## Passos para executar no phpMyAdmin:

1. **Acesse o phpMyAdmin** (http://localhost/phpmyadmin)

2. **Selecione o banco de dados "musicas"**

3. **Vá na aba "SQL"**

4. **Cole e execute o seguinte código SQL:**

```sql
-- Atualizar caminhos das imagens dos artistas
UPDATE artista SET artista_image = REPLACE(artista_image, 'audios/', 'Componentes/Armazenamento/imagens/') WHERE artista_image LIKE '%audios/%';
UPDATE artista SET artista_image = REPLACE(artista_image, 'imagens/', 'Componentes/Armazenamento/imagens/') WHERE artista_image LIKE '%imagens/%';

-- Atualizar caminhos das capas das músicas  
UPDATE musica SET musica_capa = REPLACE(musica_capa, 'audios/', 'Componentes/Armazenamento/imagens/') WHERE musica_capa LIKE '%audios/%';
UPDATE musica SET musica_capa = REPLACE(musica_capa, 'imagens/', 'Componentes/Armazenamento/imagens/') WHERE musica_capa LIKE '%imagens/%';

-- Atualizar caminhos dos áudios das músicas
UPDATE musica SET musica_link = REPLACE(musica_link, 'imagens/', 'Componentes/Armazenamento/audios/') WHERE musica_link LIKE '%imagens/%';
UPDATE musica SET musica_link = REPLACE(musica_link, 'audios/', 'Componentes/Armazenamento/audios/') WHERE musica_link LIKE '%audios/%';
```

5. **Para verificar se funcionou, execute esta consulta:**

```sql
SELECT 'ARTISTAS' as tabela, artista_nome as nome, artista_image as caminho FROM artista WHERE artista_image != ''
UNION ALL
SELECT 'MÚSICAS - CAPAS' as tabela, musica_titulo as nome, musica_capa as caminho FROM musica
UNION ALL  
SELECT 'MÚSICAS - ÁUDIOS' as tabela, musica_titulo as nome, musica_link as caminho FROM musica;
```

## ✅ Alterações Realizadas no Código:

1. **Estrutura de pastas**: A pasta `Armazenamento` já existe em `Componentes/Armazenamento/` com as subpastas:
   - `audios/` - para arquivos de áudio
   - `imagens/` - para imagens de capas e fotos de artistas

2. **processarUpload.php**: Atualizado para usar os caminhos corretos da nova estrutura

3. **Todos os caminhos**: Configurados para usar `Componentes/Armazenamento/imagens/` e `Componentes/Armazenamento/audios/`

## 🔧 Como funciona:

- **Upload de imagens**: Salvas em `Componentes/Armazenamento/imagens/`
- **Upload de áudios**: Salvos em `Componentes/Armazenamento/audios/`
- **Banco de dados**: Armazena o caminho completo `Componentes/Armazenamento/[tipo]/[arquivo]`
- **Exibição**: O site usa os caminhos do banco para mostrar imagens e tocar músicas

Após executar o SQL no phpMyAdmin, todos os arquivos existentes terão seus caminhos atualizados e o site funcionará corretamente com a nova estrutura de pastas.