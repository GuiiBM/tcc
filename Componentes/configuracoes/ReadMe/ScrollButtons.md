# Documentação - Sistema Universal de Botões de Scroll

## Visão Geral
Sistema reutilizável para criar botões de navegação horizontal em containers de cards/elementos.

## Arquivos Envolvidos
- `Componentes/configuracoes/JS/botton.js` - Script universal
- `Componentes/configuracoes/Styles/stylePrincipal.css` - Estilos dos botões

## Como Usar

### 1. Estrutura HTML Básica
```html
<div class="scroll-container">
    <!-- Botões de controle -->
    <div class="scroll-controls">
        <button class="scroll-btn" data-direction="left" data-container="seuContainerID">‹</button>
        <button class="scroll-btn" data-direction="right" data-container="seuContainerID">›</button>
    </div>
    
    <!-- Container com scroll -->
    <div class="grid-container" id="seuContainerID">
        <!-- Seus cards/elementos aqui -->
        <div class="grid-card">Conteúdo</div>
        <div class="grid-card">Conteúdo</div>
        <!-- ... mais cards -->
    </div>
</div>
```

### 2. Incluir o Script
```html
<script src="Componentes/configuracoes/JS/botton.js"></script>
```

## Data Attributes Obrigatórios

### `data-direction`
- **Valores**: `"left"` ou `"right"`
- **Função**: Define a direção do scroll
- **Exemplo**: `data-direction="left"`

### `data-container`
- **Valores**: ID do container (sem #)
- **Função**: Especifica qual container será scrollado
- **Exemplo**: `data-container="cardContainer"`

## Classes CSS Necessárias

### `.scroll-btn`
- Classe obrigatória para todos os botões
- Aplica estilos visuais (neon, hover, etc.)

### `.scroll-controls`
- Container dos botões
- Posiciona botões no canto superior direito

### `.grid-container`
- Container scrollável
- Deve ter `overflow-x: auto` e `display: flex`

## Configurações do Script

### Valor de Scroll
- **Padrão**: 300px por clique
- **Modificar**: Altere `scrollAmount` em `botton.js`

### Comportamento
- **Scroll suave**: `behavior: 'smooth'`
- **Event delegation**: Um listener para todos os botões

## Exemplo Completo

```html
<!-- Container de Músicas Pop -->
<div class="scroll-container">
    <h2>Músicas Pop</h2>
    <div class="scroll-controls">
        <button class="scroll-btn" data-direction="left" data-container="popMusic">‹</button>
        <button class="scroll-btn" data-direction="right" data-container="popMusic">›</button>
    </div>
    <div class="grid-container" id="popMusic">
        <div class="grid-card">Música 1</div>
        <div class="grid-card">Música 2</div>
        <!-- ... -->
    </div>
</div>

<!-- Container de Músicas Rock -->
<div class="scroll-container">
    <h2>Músicas Rock</h2>
    <div class="scroll-controls">
        <button class="scroll-btn" data-direction="left" data-container="rockMusic">‹</button>
        <button class="scroll-btn" data-direction="right" data-container="rockMusic">›</button>
    </div>
    <div class="grid-container" id="rockMusic">
        <div class="grid-card">Música 1</div>
        <div class="grid-card">Música 2</div>
        <!-- ... -->
    </div>
</div>
```

## Vantagens do Sistema

1. **Reutilizável**: Um script para múltiplos containers
2. **Flexível**: Funciona com qualquer tipo de conteúdo
3. **Performático**: Event delegation evita múltiplos listeners
4. **Manutenível**: Configuração via data attributes
5. **Responsivo**: Adapta-se a diferentes tamanhos de tela

## Troubleshooting

### Botões não funcionam
- Verifique se o script está incluído
- Confirme se o ID do container está correto
- Verifique se as classes CSS estão aplicadas

### Scroll não suave
- Confirme se o container tem `scroll-behavior: smooth`
- Verifique se há conflitos de CSS

### Estilo não aplicado
- Confirme se `stylePrincipal.css` está incluído
- Verifique se as classes `.scroll-btn` e `.scroll-controls` existem