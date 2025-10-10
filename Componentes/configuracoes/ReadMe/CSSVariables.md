# CSS Variables - Sistema de Cores

## Visão Geral
Sistema centralizado de variáveis CSS para manter consistência visual em todo o projeto. Todas as cores estão organizadas em categorias no arquivo `variables.css`.

## Estrutura das Variáveis

### 🌈 Neon Colors
```css
--neon-purple: #bd00ff    /* Roxo neon principal */
--neon-blue: #00d9ff      /* Azul neon */
--neon-pink: #ff00c8      /* Rosa neon */
--neon-white: #fff        /* Branco neon */
```

### 🎨 Background Colors
```css
--bg-primary: #14001a     /* Fundo principal */
--bg-secondary: #1a0024   /* Fundo secundário */
--bg-dark: #030014        /* Fundo escuro */
--bg-gradient-1: #210405  /* Gradiente animado 1 */
--bg-gradient-2: #000     /* Gradiente animado 2 */
--bg-gradient-3: #030021  /* Gradiente animado 3 */
--bg-gradient-4: #032020  /* Gradiente animado 4 */
--bg-gradient-5: #000000  /* Gradiente animado 5 */
```

### 🎭 Theme Colors
```css
--theme-purple-dark: #1e0069    /* Roxo tema escuro */
--theme-purple-medium: #18009b  /* Roxo tema médio */
--theme-red-hover: #ff0040      /* Vermelho hover */
```

### 📝 Text Colors
```css
--text-primary: #eaeaea   /* Texto principal */
--text-secondary: #ccc    /* Texto secundário */
--text-white: #fff        /* Texto branco */
```

### 🔲 Border Colors
```css
--border-light: rgba(255, 255, 255, 0.05)  /* Borda clara */
--border-purple: rgba(30, 0, 105, 0.5)     /* Borda roxa */
--border-purple-light: rgba(189, 0, 255, 0.3)  /* Borda roxa clara */
```

### 🌑 Shadow Colors
```css
--shadow-dark: rgba(0, 0, 0, 0.4)           /* Sombra escura */
--shadow-dark-strong: rgba(0, 0, 0, 0.7)    /* Sombra escura forte */
--shadow-purple: rgba(189, 0, 255, 0.3)     /* Sombra roxa */
--shadow-purple-medium: rgba(189, 0, 255, 0.5)   /* Sombra roxa média */
--shadow-purple-strong: rgba(189, 0, 255, 0.8)   /* Sombra roxa forte */
--shadow-theme-purple: rgba(30, 0, 105, 0.3)     /* Sombra tema roxa */
--shadow-theme-purple-medium: rgba(30, 0, 105, 0.4)  /* Sombra tema roxa média */
```

### 🎨 Background with Opacity
```css
--bg-purple-light: rgba(189, 0, 255, 0.1)  /* Fundo roxo claro */
--bg-white-light: rgba(255, 255, 255, 0.2) /* Fundo branco claro */
```

## Como Usar

### Exemplo de uso:
```css
.elemento {
  background-color: var(--bg-primary);
  color: var(--text-primary);
  border: 1px solid var(--border-purple);
  box-shadow: 0 4px 16px var(--shadow-purple);
}
```

## Arquivos Atualizados
- `styleGeral.css` - Gradiente animado do body
- `styleFooter.css` - Footer e player de música
- `styleHeader.css` - Navbar e elementos do cabeçalho
- `styleMain.css` - Scrollbars da área principal
- `styleAside.css` - Sidebar e seus elementos
- `stylePrincipal.css` - Cards e botões de scroll

## Vantagens
- ✅ Consistência visual
- ✅ Fácil manutenção
- ✅ Mudanças centralizadas
- ✅ Melhor organização do código