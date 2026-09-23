# Publicar o Ressonance no InfinityFree

## 1. Criar a hospedagem
1. Crie uma conta em https://infinityfree.com e um site (domínio grátis `.rf.gd`, `.infinityfreeapp.com` etc.).
2. No painel do site, ative o **SSL gratuito** (aba "Free SSL Certificates"). O login com Google exige HTTPS.

## 2. Banco de dados
1. Painel > **MySQL Databases** > crie um banco (ex: `musicas`). Anote Host, Usuário, Senha e Nome.
2. Copie `Componentes/paginas/php/dbConfig.php.example` para `dbConfig.php` e preencha.
3. Para levar as músicas que já existem: no XAMPP abra o phpMyAdmin > banco `musicas` > **Exportar** (SQL).
   No InfinityFree abra o phpMyAdmin > seu banco > **Importar** o arquivo.
   Banco vazio também funciona: todas as tabelas são criadas sozinhas no primeiro acesso.

## 3. Google (login)
1. https://console.cloud.google.com > APIs e serviços > Credenciais > seu "ID do cliente OAuth".
2. Em **URIs de redirecionamento autorizados** adicione: `https://SEU-DOMINIO/callbackGoogle.php`
3. Em **Origens JavaScript autorizadas** adicione: `https://SEU-DOMINIO`
4. Se o app estiver em modo "Teste", só os e-mails cadastrados como testadores conseguem entrar.
   Para qualquer pessoa entrar, clique em **Publicar app** na Tela de consentimento OAuth.
5. Envie também `clientId.php` e `clientSecret.php` (não estão no git).

## 4. Administrador
Copie `adminEmails.php.example` para `adminEmails.php` e coloque seu e-mail do Google.
Quem entrar com esse e-mail vira administrador automaticamente; todos os outros são usuários comuns.

## 5. Enviar os arquivos
Pelo **File Manager** do painel ou por FTP (FileZilla: host, usuário e senha em "FTP Details"),
envie todo o conteúdo do projeto para a pasta **`htdocs`**, incluindo:
- `Componentes/Armazenamento/` (áudios e imagens)
- os arquivos de configuração: `dbConfig.php`, `clientId.php`, `clientSecret.php`, `adminEmails.php`

Não envie: `.git/`, `ferramentas/` (opcional), `*.py`, `*.sh`.

## 6. Limites do InfinityFree
- **Uploads de até 10 MB por arquivo.** O formulário avisa antes de enviar. Para podcasts longos,
  use a opção "link do áudio" (ex: MP3 hospedado no Internet Archive).
- Arquivos acima de 10 MB enviados por FTP também são recusados: confira o tamanho dos MP3 antigos.
- Sem `ffmpeg` no servidor: gere as durações e as versões compactas (Economia de dados) no XAMPP
  antes de exportar o banco: `php ferramentas/calcularDuracoes.php --compacta`
  (sem isso, a duração de cada música é preenchida sozinha na primeira vez que ela toca).

## 7. Facebook e Apple (opcional)
Copie `oauthConfig.php.example` para `oauthConfig.php` e preencha. Os botões só aparecem
na tela de login quando as credenciais existem.
- Facebook: app em https://developers.facebook.com com "Login do Facebook";
  redirecionamento: `https://SEU-DOMINIO/callbackFacebook.php`. Para o público geral, o app precisa
  estar no modo "Ao vivo" (exige URL de política de privacidade).
- Apple: exige conta Apple Developer paga (US$ 99/ano); Return URL: `https://SEU-DOMINIO/callbackApple.php`.

## 8. Segurança (automática, só conferir)
- A pasta `Componentes/privado/` guarda a chave secreta do site e as sessões. Ela é criada e
  preenchida sozinha; **não copie o conteúdo dela do XAMPP** (só o `.htaccess`).
  Se o servidor não deixar escrever nela, a chave vai para a tabela `sistema` do banco.
- Os áudios só tocam por links temporários (`stream.php`), válidos por 20 minutos e apenas na
  sessão de quem abriu a página. A pasta `Componentes/Armazenamento/audios/` fica bloqueada.
- Login: 5 senhas erradas para o mesmo e-mail (ou 20 do mesmo IP) bloqueiam por 15 minutos.
- Depois de publicar, confira em https://securityheaders.com se os cabeçalhos (CSP etc.) aparecem.

## 9. Recomendações por localização
A tabela `cidade` (5.570 municípios + 27 estados, dados do IBGE) é importada sozinha na primeira
visita. A cidade dos artistas é convertida em coordenadas automaticamente; peça para os artistas
escreverem a cidade corretamente (ex: `Ubatuba` ou `Ubatuba - SP`).
