<?php
// Perfil e configurações: dados pessoais, foto, senha, qualidade do
// streaming e (para quem publica músicas) a página de artista.
$paginaId = 'perfil';
$paginaSpa = true;
$tituloPagina = 'Perfil e configurações';
include "Componentes/paginas/php/app.php";
if (!usuarioLogado()) {
    header('Location: login.php');
    exit;
}
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$usuario = usuarioAtual();
$artista = $usuario['artista_id'] ? consultarUm($conexao, "SELECT * FROM artista WHERE artista_id = ?", "i", [$usuario['artista_id']]) : null;
$loginSocial = in_array($_SESSION['login_metodo'] ?? '', ['google', 'facebook', 'apple'], true);
$qualidades = [
    'auto' => ['Automática', 'Usa a versão leve quando o aparelho está em modo de economia de dados ou numa conexão lenta.'],
    'alta' => ['Alta', 'Sempre o arquivo original, com a melhor qualidade.'],
    'baixa' => ['Economia de dados', 'Prefere a versão compacta (64 kbps) quando o artista a disponibiliza.'],
];
?>
<div class="settings-page">
    <header class="profile-head">
        <div class="profile-avatar">
            <?php if ($usuario['usuario_foto']): ?>
            <img src="<?= e($usuario['usuario_foto']) ?>" alt="" referrerpolicy="no-referrer" id="profileAvatar"<?= estiloPosicao($usuario['usuario_foto_pos']) ?>>
            <?php else: ?>
            <span id="profileAvatar"><?= e(mb_strtoupper(mb_substr($usuario['usuario_nome'], 0, 1))) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <span class="hero-type">Perfil</span>
            <h1 class="hero-title"><?= e($usuario['usuario_nome']) ?></h1>
            <p class="muted"><?= e($usuario['usuario_email']) ?><?= $usuario['usuario_tipo'] === 'admin' ? ' • Administrador' : '' ?></p>
        </div>
    </header>

    <nav class="chips settings-nav" aria-label="Seções">
        <a class="chip" href="perfil.php#dados">Dados pessoais</a>
        <a class="chip" href="perfil.php#foto">Foto</a>
        <a class="chip" href="perfil.php#senha">Senha</a>
        <a class="chip" href="perfil.php#reproducao">Reprodução</a>
        <?php if ($artista): ?><a class="chip" href="perfil.php#artista">Página de artista</a><?php endif; ?>
        <a class="chip" href="perfil.php#privacidade">Privacidade</a>
    </nav>

    <section class="panel" id="dados">
        <h2>Dados pessoais</h2>
        <form class="form-stack" action="api/perfil.php" data-api-form data-full-reload>
            <input type="hidden" name="acao" value="dados">
            <div class="form-grid">
                <label class="field"><span>Nome</span><input type="text" name="nome" required maxlength="100" value="<?= e($usuario['usuario_nome']) ?>"></label>
                <label class="field"><span>E-mail</span><input type="email" value="<?= e($usuario['usuario_email']) ?>" disabled><small>O e-mail identifica sua conta e não pode ser alterado.</small></label>
                <label class="field"><span>Idade</span><input type="number" name="idade" min="13" max="120" value="<?= e($usuario['usuario_idade']) ?>"></label>
                <label class="field"><span>Cidade</span><input type="text" name="cidade" maxlength="100" value="<?= e($usuario['usuario_cidade']) ?>"></label>
            </div>
            <label class="field"><span>Sobre você</span><textarea name="descricao" rows="3" maxlength="1000"><?= e($usuario['usuario_descricao']) ?></textarea></label>
            <div><button type="submit" class="btn-pill btn-accent">Salvar dados</button></div>
        </form>
    </section>

    <section class="panel" id="foto">
        <h2>Foto de perfil</h2>
        <p class="muted">Troque a foto, ajuste o enquadramento arrastando a imagem<?= $artista ? ' e escolha uma imagem própria para a seção "Sobre" da sua página de artista' : '' ?>.</p>
        <div><a class="btn-pill btn-accent" href="editarFoto.php"><?= icone('image') ?> Editar foto e enquadramento</a></div>
    </section>

    <section class="panel" id="senha">
        <h2>Senha</h2>
        <form class="form-stack" action="api/perfil.php" data-api-form data-reset>
            <input type="hidden" name="acao" value="senha">
            <?php if ($loginSocial): ?>
            <p class="muted">Você entrou com uma conta social. Defina uma senha para também poder entrar com e-mail e senha.</p>
            <?php else: ?>
            <label class="field"><span>Senha atual</span><input type="password" name="senha_atual" required autocomplete="current-password"></label>
            <?php endif; ?>
            <div class="form-grid">
                <label class="field"><span>Nova senha</span><input type="password" name="nova_senha" required minlength="6" autocomplete="new-password"></label>
                <label class="field"><span>Confirmar nova senha</span><input type="password" name="confirmar_senha" required minlength="6" autocomplete="new-password"></label>
            </div>
            <div><button type="submit" class="btn-pill btn-accent">Alterar senha</button></div>
        </form>
    </section>

    <section class="panel" id="reproducao">
        <h2>Reprodução</h2>
        <form class="form-stack" action="api/perfil.php" data-api-form data-quality-form>
            <input type="hidden" name="acao" value="qualidade">
            <fieldset class="radio-cards">
                <legend>Qualidade do streaming</legend>
                <?php foreach ($qualidades as $valor => [$rotulo, $descricao]): ?>
                <label class="radio-card">
                    <input type="radio" name="qualidade" value="<?= $valor ?>" <?= ($usuario['usuario_qualidade'] ?? 'auto') === $valor ? 'checked' : '' ?>>
                    <span><strong><?= e($rotulo) ?></strong><small><?= e($descricao) ?></small></span>
                </label>
                <?php endforeach; ?>
            </fieldset>
            <div><button type="submit" class="btn-pill btn-accent">Salvar preferência</button></div>
        </form>
    </section>

    <?php if ($artista): ?>
    <section class="panel" id="artista">
        <h2>Página de artista</h2>
        <p class="muted">É assim que você aparece em <a href="artista.php?id=<?= (int) $artista['artista_id'] ?>">sua página pública</a>.</p>
        <form class="form-stack" action="api/perfil.php" enctype="multipart/form-data" data-api-form data-reload>
            <input type="hidden" name="acao" value="artista">
            <div class="form-grid">
                <label class="field"><span>Nome artístico</span><input type="text" name="artista_nome" required maxlength="100" value="<?= e($artista['artista_nome']) ?>"></label>
                <label class="field"><span>Cidade</span><input type="text" name="artista_cidade" maxlength="100" value="<?= e($artista['artista_cidade']) ?>"></label>
                <label class="field"><span>Página oficial</span><input type="url" name="artista_link" placeholder="https://..." value="<?= e($artista['artista_link']) ?>"></label>
                <label class="field"><span>Foto do artista</span><input type="file" name="artista_image" accept="image/*"><small>Para ajustar o enquadramento, use <a href="editarFoto.php">Editar fotos</a>.</small></label>
                <label class="field"><span>Imagem de capa (banner, ideal 1600×600)</span><input type="file" name="artista_capa" accept="image/*"></label>
            </div>
            <?php if ($artista['artista_capa']): ?>
            <label class="check"><input type="checkbox" name="remover_capa" value="1"> Remover a imagem de capa atual</label>
            <?php endif; ?>
            <label class="field"><span>Sobre (biografia)</span><textarea name="artista_descricao" rows="5" maxlength="2000"><?= e($artista['artista_descricao']) ?></textarea></label>
            <div><button type="submit" class="btn-pill btn-accent">Salvar página de artista</button></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="panel" id="privacidade">
        <h2>Privacidade e dados</h2>
        <p class="muted">Seus direitos pela LGPD. Veja a <a href="privacidade.php">política de privacidade</a>.</p>
        <div class="row-actions">
            <a class="btn-pill btn-ghost" href="api/conta.php?acao=exportar" data-no-spa download><?= icone('upload', 'inline') ?> Baixar meus dados (JSON)</a>
        </div>
        <form class="form-stack danger-box" action="api/conta.php" data-api-form data-full-reload data-confirm="Excluir sua conta para sempre? Playlists, curtidas, histórico e tudo o que você publicou (músicas, álbuns, podcasts) serão apagados e não poderão ser recuperados.">
            <input type="hidden" name="acao" value="excluir">
            <h3>Excluir conta</h3>
            <p class="muted small">Apaga definitivamente seu perfil, playlists, curtidas, histórico, artistas seguidos e tudo o que você publicou, incluindo os arquivos enviados.</p>
            <?php if ($loginSocial): ?>
            <label class="field"><span>Para confirmar, digite seu e-mail (<?= e($usuario['usuario_email']) ?>)</span><input type="email" name="confirmacao" required autocomplete="off"></label>
            <?php else: ?>
            <label class="field"><span>Para confirmar, digite sua senha</span><input type="password" name="confirmacao" required autocomplete="current-password"></label>
            <?php endif; ?>
            <div><button type="submit" class="btn-pill btn-danger"><?= icone('trash', 'inline') ?> Excluir minha conta</button></div>
        </form>
    </section>

    <section class="panel">
        <h2>Conta</h2>
        <div class="row-actions">
            <a class="btn-pill btn-ghost" href="musicas.php" data-no-spa><?= icone('music') ?> Minhas músicas e álbuns</a>
            <a class="btn-pill btn-ghost" href="gerenciarPodcasts.php" data-no-spa><?= icone('podcast') ?> Meus podcasts</a>
            <a class="btn-pill btn-ghost danger" href="logout.php" data-no-spa><?= icone('logout') ?> Sair</a>
        </div>
    </section>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
