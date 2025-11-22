# Sistema de Ordenação de Propagandas

## Funcionalidades Implementadas

✅ **Controle de Ordem**: Sistema completo para subir e descer propagandas
✅ **Interface Visual**: Botões intuitivos com setas para cima (↑) e para baixo (↓)
✅ **Numeração**: Cada propaganda mostra sua posição atual (#1, #2, etc.)
✅ **Banco de Dados**: Tabela `propagandas` para controlar ordem e status
✅ **Exibição Ordenada**: Propagandas aparecem na ordem definida no site

## Como Usar

### 1. Configurar o Banco de Dados
Execute uma única vez para criar a tabela e migrar propagandas existentes:
```
http://localhost/tcc/criarTabelaPropagandas.php
```

### 2. Gerenciar Propagandas
Acesse o painel de gerenciamento:
```
http://localhost/tcc/gerenciarPropagandas.php
```

### 3. Reordenar Propagandas
- **Botão ↑ (Verde)**: Move a propaganda para cima na ordem
- **Botão ↓ (Amarelo)**: Move a propaganda para baixo na ordem
- **Número #X**: Mostra a posição atual da propaganda

## Estrutura do Sistema

### Tabela `propagandas`
```sql
CREATE TABLE propagandas(
    propaganda_id INT PRIMARY KEY AUTO_INCREMENT,
    propaganda_nome VARCHAR(255) NOT NULL,
    propaganda_ordem INT NOT NULL DEFAULT 0,
    propaganda_ativa BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Funções Principais
- `listarPropagandasOrdenadas()`: Lista propagandas por ordem
- `moverPropaganda($id, $direcao)`: Move propaganda para cima/baixo
- `uploadPropaganda()`: Adiciona nova propaganda com ordem automática
- `deletePropaganda()`: Remove propaganda do banco e arquivo

### Arquivos Modificados
- `funcoesPropaganda.php`: Funções de banco de dados
- `gerenciarPropagandas.php`: Interface de gerenciamento
- `aside.php`: Exibição ordenada das propagandas
- `stylePropaganda.css`: Estilos dos botões de ordenação

## Características

- **Ordem Automática**: Novas propagandas são adicionadas no final
- **Troca Inteligente**: Ao mover, as posições são trocadas automaticamente
- **Interface Responsiva**: Botões adaptativos que aparecem apenas quando necessário
- **Feedback Visual**: Efeitos hover nos botões de ordenação
- **Segurança**: Validação de dados e proteção contra SQL injection

## Observações

- Execute `criarTabelaPropagandas.php` apenas uma vez
- As propagandas existentes serão migradas automaticamente
- A ordem é mantida mesmo após reiniciar o servidor
- O sistema é compatível com todas as funcionalidades existentes