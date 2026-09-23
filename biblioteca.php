<?php
// Sua Biblioteca: Músicas Curtidas, playlists, artistas seguidos e histórico.
$paginaId = 'biblioteca';
$paginaSpa = true;
$tituloPagina = 'Sua Biblioteca';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$usuario = usuarioAtual();
if (!$usuario):
    echo renderVazio('library', 'Aproveite sua biblioteca', 'Entre para curtir músicas, criar playlists e seguir artistas.', '<a class="btn-pill btn-light" href="login.php" data-no-spa>Entrar</a>');
else:
    $uid = (int) $usuario['usuario_id'];
    $playlists = playlistsDoUsuario($conexao, $uid);
    $mosaicos = capasPlaylists($conexao, array_column($playlists, 'playlist_id'));
    $totalCurtidas = consultarUm($conexao, "SELECT COUNT(*) AS total FROM curtidas WHERE usuario_id = ? AND tipo_curtida = 'curtida'", "i", [$uid])['total'];
    $seguidos = consultar($conexao, "SELECT a.artista_id, a.artista_nome, a.artista_image, a.artista_image_pos FROM seguidores s INNER JOIN artista a ON a.artista_id = s.artista_id WHERE s.usuario_id = ? ORDER BY s.data_seguiu DESC", "i", [$uid]);
    $filtro = $_GET['filtro'] ?? 'tudo';
?>
<div class="library-page">
    <header class="page-head">
        <h1>Sua Biblioteca</h1>
        <button type="button" class="btn-pill btn-light" data-action="create-playlist"><?= icone('plus') ?> Criar playlist</button>
    </header>

    <div class="chips" data-filter-chips>
        <?php foreach (['tudo' => 'Tudo', 'playlists' => 'Playlists', 'artistas' => 'Artistas'] as $valor => $rotulo): ?>
        <button type="button" class="chip<?= $filtro === $valor ? ' is-active' : '' ?>" data-filter="<?= $valor ?>"><?= $rotulo ?></button>
        <?php endforeach; ?>
    </div>

    <div class="card-grid" data-filter-grid>
        <a class="media-card liked-card" href="curtidas.php" data-kind="playlists">
            <div class="media-cover liked-cover"><?= icone('heart-fill') ?>
                <button type="button" class="card-play" data-action="play-url" data-url="api/faixas.php?tipo=curtidas" aria-label="Tocar Músicas Curtidas"><?= icone('play') ?></button>
            </div>
            <div class="media-title">Músicas Curtidas</div>
            <div class="media-sub"><?= icone('lock', 'inline') ?> <?= pluralizar((int) $totalCurtidas, 'música', 'músicas') ?></div>
        </a>
        <a class="media-card" href="historico.php" data-kind="playlists">
            <div class="media-cover history-cover"><?= icone('history') ?></div>
            <div class="media-title">Histórico</div>
            <div class="media-sub">Ouvidas por completo</div>
        </a>
        <?php foreach ($playlists as $p): ?>
            <?= str_replace('<a class="media-card"', '<a class="media-card" data-kind="playlists"', renderCardPlaylist(array_merge($p, ['dono_nome' => $usuario['usuario_nome']]), $mosaicos[(int) $p['playlist_id']] ?? [])) ?>
        <?php endforeach; ?>
        <?php foreach ($seguidos as $a): ?>
            <?= str_replace('<a class="media-card is-round"', '<a class="media-card is-round" data-kind="artistas"', renderCardArtista($a)) ?>
        <?php endforeach; ?>
    </div>

    <?php if (!$playlists && !$seguidos): ?>
    <p class="muted center">Crie playlists e siga artistas para encontrá-los rapidamente aqui.</p>
    <?php endif; ?>
</div>
<?php
endif;
include "Componentes/paginas/footer.php";
