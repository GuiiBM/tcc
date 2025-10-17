# Sistema de Autocomplete

## Funcionalidades

### Autocomplete de Artistas
- **Arquivo**: `artistaAutocomplete.js`
- **Endpoint**: `buscarArtistas.php`
- **Funcionalidade**: Busca artistas em tempo real conforme digitação

#### Como Funciona:
1. Digite 2+ caracteres no campo "Nome do Artista"
2. Sistema busca artistas similares no banco
3. Lista aparece com nome e país do artista
4. Clique para selecionar e preencher automaticamente

### Autocomplete de Países
- **Endpoint**: `buscarPaises.php`
- **Funcionalidade**: Mostra países com lista de artistas

#### Como Funciona:
1. Digite no campo "País do Artista"
2. Aparece lista de países cadastrados
3. Cada país mostra os artistas daquele país
4. Clique no país para abrir modal de seleção

### Modal de Seleção de Artistas
- **Endpoint**: `buscarArtistasPorPais.php`
- **Funcionalidade**: Permite escolher artista existente ou criar novo

#### Opções Disponíveis:
- **Selecionar Artista Existente**: Preenche campos automaticamente
- **Adicionar Novo Artista**: Mostra campos extras (foto)

## Arquivos Relacionados
- `artistaAutocomplete.js` - Lógica principal
- `buscarArtistas.php` - Busca por nome
- `buscarPaises.php` - Busca por país
- `buscarArtistasPorPais.php` - Artistas por país
- `styleForms.css` - Estilos das sugestões