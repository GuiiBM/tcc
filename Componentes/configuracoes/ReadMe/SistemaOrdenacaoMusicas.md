# Sistema de Ordenação de Músicas

## Visão Geral
O sistema ordena as músicas baseado em dois critérios principais:
1. **Total de Votos** (likes + dislikes) - Crescente (menos engajamento primeiro)
2. **Qualidade dos Votos** (likes - dislikes) - Decrescente (melhor qualidade primeiro)

## Lógica de Ordenação

### Fórmulas:
- **Total de Votos** = Likes + Dislikes
- **Qualidade dos Votos** = Likes - Dislikes

### Critérios de Ordenação:
1. **Prioridade 1**: Total de Votos (menor para maior) - Promove descoberta de músicas novas
2. **Prioridade 2**: Qualidade dos Votos (melhor para pior) - Garante que músicas boas sejam vistas

## Como Funciona na Prática

### Princípio:
- **Músicas com MENOS engajamento aparecem PRIMEIRO**
- **Dentro do mesmo nível de engajamento, as MELHORES aparecem PRIMEIRO**
- **Sistema justo**: 1 like cancela 1 dislike
- **Objetivo**: Dar chance para músicas novas, mas priorizar qualidade

## Exemplos Práticos

### Cenário 1: Diferentes Totais de Votos
| Música | Likes | Dislikes | Total | Qualidade | Posição |
|--------|-------|----------|-------|-----------|---------|
| A      | 0     | 0        | 0     | 0         | 1º      |
| B      | 1     | 0        | 1     | 1         | 2º      |
| C      | 2     | 1        | 3     | 1         | 3º      |
| D      | 5     | 5        | 10    | 0         | 4º      |

### Cenário 2: Mesmo Total, Diferentes Qualidades
| Música | Likes | Dislikes | Total | Qualidade | Posição |
|--------|-------|----------|-------|-----------|---------|
| A      | 3     | 0        | 3     | 3         | 1º      |
| B      | 2     | 1        | 3     | 1         | 2º      |
| C      | 1     | 2        | 3     | -1        | 3º      |
| D      | 0     | 3        | 3     | -3        | 4º      |

### Cenário 3: Exemplo Completo
| Música | Likes | Dislikes | Total | Qualidade | Posição |
|--------|-------|----------|-------|-----------|---------|
| A      | 0     | 0        | 0     | 0         | 1º      |
| B      | 1     | 0        | 1     | 1         | 2º      |
| C      | 0     | 1        | 1     | -1        | 3º      |
| D      | 2     | 0        | 2     | 2         | 4º      |
| E      | 1     | 1        | 2     | 0         | 5º      |
| F      | 0     | 2        | 2     | -2        | 6º      |
| G      | 100   | 100      | 200   | 0         | 7º      |

## Fluxo de Ordenação

### Passo 1: Agrupa por Total de Votos
- Grupo 0: Músicas com 0 votos totais
- Grupo 1: Músicas com 1 voto total
- Grupo 2: Músicas com 2 votos totais
- E assim por diante...

### Passo 2: Dentro de cada grupo, ordena por Qualidade
- Melhor qualidade (mais likes relativos) primeiro
- Pior qualidade (mais dislikes relativos) por último

## Vantagens do Sistema

1. **Descoberta justa**: Músicas novas têm chance de serem vistas
2. **Qualidade garantida**: Músicas boas não ficam perdidas
3. **Engajamento equilibrado**: Não favorece apenas músicas populares
4. **Sistema transparente**: Critérios claros e matemáticos

## Implementação Técnica

```sql
ORDER BY 
    (COALESCE(likes.total_likes, 0) + COALESCE(dislikes.total_dislikes, 0)) ASC,
    (COALESCE(likes.total_likes, 0) - COALESCE(dislikes.total_dislikes, 0)) DESC
```

## Resumo da Lógica

**"Menos engajamento primeiro, melhor qualidade primeiro"**

1. Uma música com 0 likes e 0 dislikes sempre aparece antes de uma com 100 likes e 100 dislikes
2. Entre duas músicas com 2 votos totais, a com 2 likes e 0 dislikes aparece antes da com 1 like e 1 dislike
3. O sistema promove descoberta sem sacrificar qualidade

### Funções Implementadas:
- `buscarMusicas($conexao)` - Lista todas as músicas com ordenação personalizada
- `buscarMusicasOrdemPersonalizada($conexao, $limite)` - Versão com limite de resultados