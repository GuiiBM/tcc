<?php
// Política de privacidade (LGPD - Lei 13.709/2018).
$paginaId = 'privacidade';
$paginaSpa = true;
$tituloPagina = 'Privacidade';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";
?>
<article class="legal-page">
    <header class="page-head"><h1>Política de privacidade</h1><p class="muted">Como o Ressonance trata seus dados, conforme a LGPD.</p></header>

    <section class="panel">
        <h2>Quais dados coletamos</h2>
        <ul>
            <li><strong>Conta:</strong> nome, e-mail, foto, idade, cidade e descrição que você informa (ou que vêm do Facebook/Apple ao entrar com eles).</li>
            <li><strong>Senha:</strong> guardamos apenas um <em>hash</em> (Argon2/Bcrypt); nem a equipe consegue ver sua senha.</li>
            <li><strong>Uso:</strong> músicas curtidas, playlists, artistas seguidos e histórico das faixas ouvidas até o fim.</li>
            <li><strong>Estatísticas:</strong> contagem de reproduções. O endereço IP é guardado apenas como um código irreversível (hash), nunca o IP em si.</li>
            <li><strong>Localização (opcional):</strong> se você tocar em “Usar minha localização”, a posição aproximada (~1 km) fica só num cookie do seu navegador, para recomendar artistas próximos. Ela não é gravada no nosso banco.</li>
        </ul>
    </section>

    <section class="panel">
        <h2>Para que usamos</h2>
        <ul>
            <li>Manter sua conta, biblioteca e preferências.</li>
            <li>Recomendar artistas independentes perto de você e ainda pouco ouvidos.</li>
            <li>Proteger o site (limite de tentativas de login, prevenção de abuso).</li>
        </ul>
        <p>Não vendemos nem compartilhamos seus dados com terceiros para publicidade.</p>
    </section>

    <section class="panel">
        <h2>Cookies</h2>
        <ul>
            <li><code>RSSID</code>: mantém você conectado (essencial).</li>
            <li><code>rs_local</code>: localização aproximada, só se você permitir.</li>
            <li>O player guarda no seu navegador (localStorage) a fila, o volume e a posição da música, para continuar de onde parou.</li>
        </ul>
    </section>

    <section class="panel">
        <h2>Seus direitos</h2>
        <ul>
            <li><strong>Acesso e portabilidade:</strong> baixe todos os seus dados em <a href="perfil.php#privacidade">Perfil › Privacidade</a>.</li>
            <li><strong>Correção:</strong> edite seus dados a qualquer momento no perfil.</li>
            <li><strong>Eliminação:</strong> exclua sua conta no perfil; tudo é apagado de forma definitiva, inclusive os arquivos enviados.</li>
            <li><strong>Revogar a localização:</strong> use “Esquecer” na página de recomendações ou apague os cookies.</li>
        </ul>
    </section>
</article>
<?php include "Componentes/paginas/footer.php"; ?>
