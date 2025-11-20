# Sistema de Visualizações

## Funcionalidade Implementada

O sistema de visualizações foi implementado para registrar automaticamente quando uma música é tocada por qualquer usuário.

## Como Funciona

### 1. Banco de Dados
- **Tabela**: `visualizacoes`
- **Campos**:
  - `visualizacao_id`: ID único da visualização
  - `musica_id`: ID da música visualizada (chave estrangeira)
  - `ip_usuario`: IP do usuário que visualizou
  - `data_visualizacao`: Data e hora da visualização

### 2. Registro Automático
- Quando um usuário clica em uma música, a função `playMusic()` é chamada
- Automaticamente é enviada uma requisição AJAX para registrar a visualização
- O sistema evita spam registrando apenas 1 visualização por IP a cada 30 minutos

### 3. Arquivos Modificados/Criados

#### Novos Arquivos:
- `Componentes/páginas/php/processar_visualizacao.php`: Processa e registra visualizações
- `testar_visualizacoes.php`: Arquivo de teste do sistema

#### Arquivos Modificados:
- `Componentes/páginas/php/banco.php`: Adicionada criação da tabela visualizações
- `Componentes/páginas/php/funcoesMusicas.php`: Adicionadas funções para buscar músicas mais visualizadas
- `Componentes/configuracoes/JS/musicPlayer.js`: Adicionada função para registrar visualização
- `Componentes/páginas/principal.php`: Adicionada seção "Músicas Mais Visualizadas"

### 4. Funcionalidades Adicionadas

#### Buscar Músicas Mais Visualizadas
```php
buscarMusicasMaisVisualizadas($conexao, $limite = null)
```

#### Contar Visualizações
```php
contarVisualizacoes($conexao, $musica_id)
```

#### Registro Automático via JavaScript
```javascript
registrarVisualizacao(musicaId)
```

### 5. Interface do Usuário
- Nova seção "Músicas Mais Visualizadas" na página principal
- Contador de visualizações exibido nos cards das músicas
- Ícone 👁 para indicar visualizações

### 6. Prevenção de Spam
- Apenas 1 visualização por IP a cada 30 minutos para a mesma música
- Validação de dados no backend
- Verificação de existência da música

## Como Testar

1. Acesse `testar_visualizacoes.php` para verificar se a tabela foi criada
2. Clique em qualquer música na página principal
3. Verifique se a visualização foi registrada
4. Observe a seção "Músicas Mais Visualizadas" sendo atualizada

## Benefícios

- **Para Usuários**: Descobrir músicas populares
- **Para Artistas**: Ver quais músicas são mais ouvidas
- **Para o Sistema**: Dados para recomendações e estatísticas
- **Acessível**: Funciona para todos os usuários, mesmo sem login

## Segurança

- Validação de entrada de dados
- Prevenção de SQL injection com prepared statements
- Limitação de frequência para evitar spam
- Verificação de existência da música antes de registrar