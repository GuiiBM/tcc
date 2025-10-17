# Sistema de Formulários

## Formulários Disponíveis

### Formulário de Música (`formMusica.php`)
- **Localização**: `Componentes/páginas/formMusica.php`
- **Funcionalidade**: Cadastro de músicas com artistas

#### Campos:
- Nome do Artista (com autocomplete)
- País do Artista (com autocomplete)
- Título da Música
- URL da Capa
- Link do Áudio (YouTube)
- URL da Foto do Artista (apenas para novos artistas)

#### Lógica:
1. Verifica se artista já existe no banco
2. Se existe: usa ID existente
3. Se não existe: cadastra novo artista com foto
4. Cadastra música vinculada ao artista

### Formulário de Artista (`formArtista.php`)
- **Localização**: `Componentes/páginas/formArtista.php`
- **Funcionalidade**: Cadastro direto de artistas

#### Campos:
- Nome do Artista
- País de Origem
- URL da Foto

### Modais de Login/Cadastro
- **Localização**: `header.php`
- **Funcionalidade**: Autenticação de usuários

#### Modal de Login:
- E-mail
- Senha

#### Modal de Cadastro:
- Nome
- Sobrenome
- E-mail
- Senha
- Confirmar Senha

## Estilos
- **Arquivo**: `styleForms.css`
- **Características**:
  - Fundo transparente para mostrar gradiente animado
  - Bordas neon com brilho
  - Campos com efeitos hover/focus
  - Botões com gradientes e animações
  - Modais semi-transparentes