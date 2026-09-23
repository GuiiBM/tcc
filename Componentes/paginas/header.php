<?php
// Início do "casco" do app: barra lateral, barra superior e a área de
// conteúdo (#app-content). Fechado em footer.php, que também traz o player.
//
// Páginas novas definem antes de incluir head.php/header.php:
//   $paginaId  - identificador usado pelo JS (ex: 'home', 'busca')
//   $paginaSpa - true se a página pode ser trocada sem recarregar (o player
//                continua tocando). Páginas antigas/admin ficam com false.
$paginaId = $paginaId ?? 'legado';
$paginaSpa = $paginaSpa ?? false;
$usuarioShell = usuarioAtual();
$playlistsShell = $usuarioShell ? playlistsDoUsuario($conexao, $usuarioShell['usuario_id']) : [];
$scriptAtual = basename($_SERVER['SCRIPT_NAME']);
include_once __DIR__ . '/php/funcoesPropaganda.php';
$propagandasShell = listarPropagandasOrdenadas();

function linkAtivo($arquivos) {
    global $scriptAtual;
    return in_array($scriptAtual, (array) $arquivos, true) ? ' is-active' : '';
}
?>
<div class="app-shell">
    <a class="skip-link" href="#app-content">Pular para o conteúdo</a>

    <nav class="sidebar" aria-label="Navegação principal">
        <div class="sidebar-block sidebar-nav">
            <a class="brand" href="index.php">
                <img src="Componentes/icones/icone2.png" alt="" width="36" height="36">
                <span>Ressonance</span>
            </a>
            <a class="nav-item<?= linkAtivo(['index.php', '']) ?>" data-nav="index.php" href="index.php"><?= icone('home') ?><span>Início</span></a>
            <a class="nav-item<?= linkAtivo(['buscar.php', 'genero.php']) ?>" data-nav="buscar.php" href="buscar.php"><?= icone('search') ?><span>Buscar</span></a>
            <a class="nav-item<?= linkAtivo('biblioteca.php') ?>" data-nav="biblioteca.php" href="biblioteca.php"><?= icone('library') ?><span>Sua Biblioteca</span></a>
            <a class="nav-item<?= linkAtivo(['artistas.php', 'artista.php']) ?>" data-nav="artistas.php" href="artistas.php"><?= icone('user') ?><span>Artistas</span></a>
            <a class="nav-item<?= linkAtivo('recomendados.php') ?>" data-nav="recomendados.php" href="recomendados.php"><?= icone('explore') ?><span>Recomendações</span></a>
        </div>

        <div class="sidebar-block sidebar-library">
            <div class="library-head">
                <span>Playlists</span>
                <?php if ($usuarioShell): ?>
                <button type="button" class="icon-btn" data-action="create-playlist" aria-label="Criar playlist" title="Criar playlist"><?= icone('plus') ?></button>
                <?php endif; ?>
            </div>
            <?php if ($usuarioShell): ?>
            <div class="library-list">
                <a class="library-item<?= linkAtivo('curtidas.php') ?>" data-nav="curtidas.php" href="curtidas.php">
                    <span class="library-thumb liked-thumb"><?= icone('heart-fill') ?></span>
                    <span class="library-text"><strong>Músicas Curtidas</strong><small>Playlist • Privada</small></span>
                </a>
                <a class="library-item<?= linkAtivo('historico.php') ?>" data-nav="historico.php" href="historico.php">
                    <span class="library-thumb history-thumb"><?= icone('history') ?></span>
                    <span class="library-text"><strong>Histórico</strong><small>Ouvidas recentemente</small></span>
                </a>
                <div id="sidebarPlaylists">
                    <?php foreach ($playlistsShell as $p): ?>
                    <a class="library-item" data-nav="playlist.php?id=<?= (int) $p['playlist_id'] ?>" href="playlist.php?id=<?= (int) $p['playlist_id'] ?>">
                        <span class="library-thumb"><?php if ($p['playlist_capa']): ?><img src="<?= e($p['playlist_capa']) ?>" alt="" loading="lazy"><?php else: ?><?= icone('music') ?><?php endif; ?></span>
                        <span class="library-text"><strong><?= e($p['playlist_nome']) ?></strong><small>Playlist • <?= pluralizar((int) $p['total'], 'música', 'músicas') ?></small></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="library-cta">
                <strong>Crie sua primeira playlist</strong>
                <p>Entre com sua conta para curtir músicas, criar playlists e seguir artistas.</p>
                <a class="btn-pill btn-light" href="login.php">Entrar</a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($propagandasShell): ?>
        <aside class="sidebar-block sidebar-ad" aria-label="Publicidade">
            <?= renderPropagandas($propagandasShell) ?>
        </aside>
        <?php endif; ?>
        <a class="sidebar-legal" href="privacidade.php">Privacidade</a>
    </nav>

    <div class="app-main" id="appMain">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="icon-btn round nav-history" data-action="history-back" aria-label="Voltar"><?= icone('chevron-left') ?></button>
                <button type="button" class="icon-btn round nav-history" data-action="history-forward" aria-label="Avançar"><?= icone('chevron-right') ?></button>
                <a class="brand brand-mobile" href="index.php"><img src="Componentes/icones/icone2.png" alt="" width="30" height="30"><span>Ressonance</span></a>
            </div>
            <div class="topbar-right">
                <button type="button" class="icon-btn round" data-action="show-shortcuts" aria-label="Atalhos do teclado" title="Atalhos do teclado (?)"><?= icone('keyboard') ?></button>
                <?php if ($usuarioShell): ?>
                    <?php if (ehAdmin()): ?>
                    <a class="btn-pill btn-outline hide-mobile" href="admin.php"><?= icone('shield') ?> Admin</a>
                    <?php endif; ?>
                    <div class="user-menu">
                        <button type="button" class="avatar-btn" data-action="user-menu" aria-haspopup="true" aria-expanded="false" aria-label="Menu da conta">
                            <?php if ($usuarioShell['usuario_foto']): ?>
                            <img src="<?= e($usuarioShell['usuario_foto']) ?>" alt="" referrerpolicy="no-referrer">
                            <?php else: ?>
                            <span><?= e(mb_strtoupper(mb_substr($usuarioShell['usuario_nome'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu-app" id="userMenu" hidden>
                            <div class="dropdown-header-app"><strong><?= e($usuarioShell['usuario_nome']) ?></strong><small><?= e($usuarioShell['usuario_email']) ?></small></div>
                            <a href="perfil.php"><?= icone('settings') ?> Perfil e configurações</a>
                            <?php if ($usuarioShell['artista_id']): ?>
                            <a href="artista.php?id=<?= (int) $usuarioShell['artista_id'] ?>"><?= icone('user') ?> Minha página de artista</a>
                            <?php endif; ?>
                            <a href="musicas.php" data-no-spa><?= icone('music') ?> Minhas músicas e álbuns</a>
                            <a href="gerenciarPodcasts.php" data-no-spa><?= icone('podcast') ?> Meus podcasts</a>
                            <?php if (ehAdmin()): ?>
                            <a href="admin.php" data-no-spa><?= icone('shield') ?> Painel administrativo</a>
                            <?php endif; ?>
                            <a href="logout.php" data-no-spa><?= icone('logout') ?> Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="btn-pill btn-ghost hide-mobile" href="login.php?modo=registro" data-no-spa>Cadastre-se</a>
                    <a class="btn-pill btn-light" href="login.php" data-no-spa>Entrar</a>
                <?php endif; ?>
            </div>
        </header>

        <div id="app-content" class="app-content rs-page-<?= e($paginaId) ?>" data-page="<?= e($paginaId) ?>"<?= $paginaSpa ? ' data-spa="1"' : '' ?> data-title="<?= e($tituloPagina ?? '') ?>" tabindex="-1">
