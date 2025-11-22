# 👥 Sistema de Gerenciamento de Usuários Duplicados

## 📋 Visão Geral

O sistema de gerenciamento de usuários duplicados permite identificar e combinar usuários que podem ser duplicatas, mantendo a integridade dos dados e melhorando a qualidade da base de usuários.

## 🚀 Funcionalidades

### 1. Detecção Automática de Duplicados
- **Algoritmo de Similaridade**: Compara nomes de usuários usando algoritmo de similaridade de texto
- **Verificação de Email**: Identifica emails idênticos entre usuários
- **Threshold Configurável**: Permite ajustar a sensibilidade da detecção (50% a 100%)

### 2. Combinação Manual
- **Seleção Livre**: Permite combinar qualquer par de usuários manualmente
- **Preview da Combinação**: Visualiza o resultado antes de executar a ação
- **Confirmação de Segurança**: Múltiplas confirmações para evitar erros

### 3. Preview Inteligente
- **Visualização Completa**: Mostra todos os dados dos usuários antes da combinação
- **Estatísticas**: Exibe contagem de curtidas e outros dados relevantes
- **Resultado Projetado**: Mostra como ficará após a combinação

## 🔧 Como Usar

### Acesso
1. Faça login como administrador
2. Acesse o **Painel Administrativo**
3. Clique em **"Gerenciar Usuários"**

### Detecção Automática
1. Na aba **"Usuários Duplicados"**:
   - Visualize os pares de usuários similares encontrados
   - Veja o motivo da detecção (nome similar ou email idêntico)
   - Compare as informações dos usuários lado a lado
   - Clique em "🔍 Manter Usuário X" para fazer o preview

### Combinação Manual
1. Na aba **"Combinação Manual"**:
   - Selecione o **usuário principal** (que será mantido)
   - Selecione o **usuário secundário** (que será removido)
   - Clique em "🔍 Preview da Combinação" para visualizar
   - Ou clique em "🔄 Combinar Diretamente" para ação imediata

### Configurações
1. Na aba **"Configurações"**:
   - Ajuste o **threshold de similaridade** (50% a 100%)
   - Visualize **estatísticas do sistema**
   - Monitore o número de duplicados encontrados

## ⚙️ Configuração do Threshold

### Valores Recomendados:
- **90-100%**: Detecção muito rígida (apenas duplicatas óbvias)
- **80-89%**: Detecção equilibrada (recomendado)
- **70-79%**: Detecção flexível (mais candidatos)
- **50-69%**: Detecção muito flexível (muitos falsos positivos)

### Exemplos de Similaridade:
- "João Silva" vs "Joao Silva" = 95% similar
- "Maria Santos" vs "Maria dos Santos" = 85% similar
- "Pedro" vs "Pedro123" = 75% similar

## 🔄 Processo de Combinação

### O que acontece durante a combinação:
1. **Transferência de Curtidas**: Todas as curtidas do usuário secundário são transferidas para o principal
2. **Preservação de Artista**: O perfil de artista do usuário principal é mantido (se não existir, usa o do secundário)
3. **Remoção Segura**: O usuário secundário é removido permanentemente
4. **Transação Atômica**: Todo o processo é executado em uma transação para garantir consistência

### Dados Preservados (Usuário Principal):
- ✅ Nome, email, cidade
- ✅ Tipo de usuário (admin/usuario)
- ✅ Data de criação
- ✅ Perfil de artista
- ✅ Todas as curtidas (próprias + transferidas)

### Dados Perdidos (Usuário Secundário):
- ❌ Conta de usuário
- ❌ Dados pessoais
- ❌ Perfil de artista (se o principal já tiver um)

## 🛡️ Segurança

### Medidas de Proteção:
- **Confirmação Dupla**: Preview + confirmação final
- **Transações Atômicas**: Rollback automático em caso de erro
- **Logs de Auditoria**: Registro de todas as operações
- **Acesso Restrito**: Apenas administradores podem usar

### Validações:
- Usuários diferentes devem ser selecionados
- Ambos os usuários devem existir no banco
- Verificação de integridade antes da combinação

## 📊 Estatísticas

O sistema fornece estatísticas em tempo real:
- **Total de Usuários**: Número atual de usuários cadastrados
- **Duplicados Encontrados**: Baseado no threshold atual
- **Total de Curtidas**: Soma de todas as curtidas no sistema

## 🚨 Importante

⚠️ **ATENÇÃO**: A combinação de usuários é uma operação **IRREVERSÍVEL**. Uma vez executada, não é possível desfazer a ação.

### Recomendações:
1. **Sempre use o Preview** antes de combinar usuários
2. **Verifique cuidadosamente** os dados de ambos os usuários
3. **Considere fazer backup** do banco de dados antes de operações em massa
4. **Teste primeiro** com usuários de teste em ambiente de desenvolvimento

## 🔧 Manutenção

### Limpeza Periódica:
- Execute a detecção regularmente para manter a base limpa
- Ajuste o threshold conforme necessário
- Monitore as estatísticas para identificar padrões

### Troubleshooting:
- Se a combinação falhar, verifique os logs do servidor
- Certifique-se de que não há restrições de chave estrangeira
- Verifique a integridade do banco de dados

---

**Desenvolvido para o Sistema de Música TCC**  
*Versão 1.0 - Sistema de Gerenciamento de Usuários Duplicados*