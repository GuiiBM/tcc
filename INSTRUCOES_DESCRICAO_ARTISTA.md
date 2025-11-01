# Instruções para Adicionar Descrição dos Artistas no Popup

## Passo 1: Executar Script SQL
1. Acesse o phpMyAdmin (http://localhost/phpmyadmin)
2. Selecione seu banco de dados
3. Vá na aba "SQL"
4. Execute o conteúdo do arquivo `adicionar_descricao_artista.sql`

OU

Execute o arquivo PHP:
1. Acesse: http://localhost/tcc/adicionarDescricaoArtista.php
2. Aguarde a execução completar

## Passo 2: Verificar Implementação
As seguintes alterações já foram feitas:

### JavaScript (artistPopup.js):
- Adicionada seção de descrição no HTML do popup
- Implementada população da descrição do artista

### CSS (styleArtistPopup.css):
- Adicionados estilos para a seção de descrição
- Design harmonioso com o resto do popup

### PHP (getArtistData.php):
- Query atualizada para buscar descrição do artista
- Retorno JSON inclui campo de descrição

## Resultado:
- A descrição aparecerá entre as informações do artista e a lista de músicas
- Design estilizado com fundo semi-transparente e bordas neon
- Texto justificado e em itálico para melhor apresentação

## Personalização:
Para editar descrições dos artistas:
1. Acesse o banco de dados
2. Edite a tabela `artista`
3. Modifique o campo `artista_descricao`