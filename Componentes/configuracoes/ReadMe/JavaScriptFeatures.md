# Funcionalidades JavaScript

## Scripts Principais

### Artist Autocomplete (`artistaAutocomplete.js`)
- **Funcionalidades**:
  - Busca em tempo real de artistas
  - Autocomplete de países
  - Modal de seleção de artistas
  - Campos dinâmicos para novos artistas

#### Funções Principais:
- `selectArtist()`: Seleciona artista existente
- `selectPais()`: Seleciona país e abre modal
- `showArtistsByCountry()`: Mostra artistas por país
- `addNewArtist()`: Adiciona novo artista
- `showNewArtistFields()`: Mostra/oculta campos extras

### Music Player (`musicPlayer.js`)
- **Funcionalidades**:
  - Reprodução de músicas do YouTube
  - Controles de play/pause
  - Navegação entre faixas
  - Controle de tempo

### Scroll Buttons (`botton.js`)
- **Funcionalidades**:
  - Navegação horizontal no grid
  - Scroll suave
  - Botões esquerda/direita
  - Controle por data-attributes

### Zoom Control (`zoomControl.js`)
- **Funcionalidades**:
  - Zoom da página
  - Controle por scroll + Ctrl
  - Botões de zoom
  - Limites mínimo/máximo

## Eventos e Interações

### Event Listeners
- `input`: Autocomplete em tempo real
- `click`: Seleção de sugestões
- `blur`: Validação de campos
- `scroll`: Controles de navegação

### AJAX Requests
- Busca de artistas: `buscarArtistas.php`
- Busca de países: `buscarPaises.php`
- Artistas por país: `buscarArtistasPorPais.php`

### Timeouts e Debouncing
- 300ms delay para busca automática
- Evita requisições excessivas
- Melhora performance

## Integração com CSS
- Classes dinâmicas para estados
- Animações via JavaScript
- Controle de visibilidade
- Efeitos hover programáticos