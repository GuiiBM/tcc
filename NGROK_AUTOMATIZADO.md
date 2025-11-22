# Ngrok Automatizado - TCC

## Configuração Automática

O sistema agora está configurado para iniciar automaticamente o Ngrok com o domínio fixo:
**https://charlyn-unpaying-zara.ngrok-free.dev/tcc/**

## Como Funciona

### 1. Inicialização Automática
- O Ngrok é iniciado automaticamente quando você acessa qualquer página do site
- Configuração automática do domínio fixo
- Não precisa de intervenção manual

### 2. Controle Manual (Admin)
- Acesse a página de administração
- Use o botão "🌐 Ngrok" para controlar manualmente
- Inicie/pare o Ngrok conforme necessário

### 3. Arquivos Principais

- `auto_ngrok.php` - Auto-inicialização no carregamento das páginas
- `ngrok_auto_start.php` - Script de inicialização robusto
- `ngrok_control.php` - Controle via botão no header (admin)
- `ngrok_final.php` - API de controle completo
- `setup_ngrok.php` - Configuração inicial completa

### 4. URL Configurada

**Domínio Fixo:** `charlyn-unpaying-zara.ngrok-free.dev`
**URL Completa:** `https://charlyn-unpaying-zara.ngrok-free.dev/tcc/`

## Recursos

✅ **Inicialização Automática** - Ngrok inicia sozinho
✅ **Domínio Fixo** - Sempre a mesma URL
✅ **Controle via Botão** - Interface simples no admin
✅ **Status em Tempo Real** - Verificação automática do status
✅ **Configuração Persistente** - Mantém configurações

## Como Usar

1. **Automático**: Apenas acesse o site - o Ngrok iniciará sozinho
2. **Manual**: Use o botão no painel admin para controlar
3. **Verificação**: O status é mostrado automaticamente

## Troubleshooting

Se o Ngrok não iniciar automaticamente:
1. Acesse `setup_ngrok.php` para configuração manual
2. Verifique se o authtoken está correto
3. Use o botão no painel admin para reiniciar

## Configuração do Authtoken

O authtoken já está configurado no sistema. Se precisar alterar:
- Edite o arquivo `ngrok_auto_start.php`
- Substitua o valor do authtoken na configuração