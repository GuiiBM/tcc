<?php
// Página do artista: capa, foto, seguir, populares, discografia e "Sobre".
$paginaId = 'artista';
$paginaSpa = true;
include "Componentes/paginas/php/app.php";

$artista = consultarUm($conexao, "SELECT * FROM artista WHERE artista_id = ?", "i", [(int) ($_GET['id'] ?? 0)]);
$tituloPagina = $artista ? $artista['artista_nome'] : 'Artista não encontrado';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

if (!$artista):
    echo renderVazio('user', 'Artista não encontrado', '', '<a class="btn-pill btn-light" href="artistas.php">Ver todos os artistas</a>');
else:
    $id = (int) $artista['artista_id'];
    $populares = consultarFaixas($conexao, "LEFT JOIN (SELECT musica_id, COUNT(*) AS total FROM visualizacoes GROUP BY musica_id) v ON v.musica_id = m.musica_id
        WHERE m.musica_artista = ? ORDER BY COALESCE(v.total, 0) DESC, m.musica_data_adicao DESC LIMIT 10", "i", [$id], 'COALESCE(v.total, 0) AS total_views');
    $albuns = consultar($conexao, "SELECT al.*, a.artista_nome FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista WHERE al.album_artista = ? ORDER BY al.album_ano DESC, al.album_data DESC", "i", [$id]);
    $singles = consultarFaixas($conexao, "WHERE m.musica_artista = ? AND m.album_id IS NULL ORDER BY m.musica_data_adicao DESC", "i", [$id]);
    $seguidores = (int) consultarUm($conexao, "SELECT COUNT(*) AS total FROM seguidores WHERE artista_id = ?", "i", [$id])['total'];
    $ouvintes = (int) consultarUm($conexao, "SELECT COUNT(DISTINCT v.ip_usuario) AS total FROM visualizacoes v INNER JOIN musica m ON m.musica_id = v.musica_id WHERE m.musica_artista = ? AND v.data_visualizacao > DATE_SUB(NOW(), INTERVAL 30 DAY)", "i", [$id])['total'];
    $segue = usuarioLogado() && consultarUm($conexao, "SELECT 1 FROM seguidores WHERE usuario_id = ? AND artista_id = ?", "ii", [$_SESSION['usuario_id'], $id]);
    $similares = consultar($conexao, "SELECT a.artista_id, a.artista_nome, a.artista_image, a.artista_image_pos FROM artista a WHERE a.artista_id <> ? AND " . SQL_ARTISTA_VISIVEL . "
        ORDER BY (a.artista_cidade = ?) DESC, RAND() LIMIT 10", "is", [$id, $artista['artista_cidade'] ?? '']);
    $imagem = imagemOuPadrao($artista['artista_image']);
    $capa = $artista['artista_capa'] ?: $imagem;
    // "Sobre" usa a imagem própria da seção; sem ela, a foto do artista.
    $imagemSobre = $artista['artista_sobre'] ?: $imagem;
    $posicaoSobre = posicaoImagem($artista['artista_sobre'] ? $artista['artista_sobre_pos'] : $artista['artista_image_pos']);
    $ehDono = podeEditarArtista($id);
?>
<div class="artist-page">
    <header class="artist-hero<?= $artista['artista_capa'] ? ' has-cover' : '' ?>">
        <div class="artist-hero-bg" aria-hidden="true" style="background-image: url('<?= e($capa) ?>')"></div>
        <div class="artist-hero-content">
            <img class="artist-avatar" src="<?= e($imagem) ?>" alt="" referrerpolicy="no-referrer"<?= estiloPosicao($artista['artista_image_pos']) ?>>
            <div>
                <span class="hero-type"><?= icone('check', 'verified') ?> Artista</span>
                <h1 class="hero-title"><?= e($artista['artista_nome']) ?></h1>
                <p class="artist-stats"><?= number_format($ouvintes, 0, ',', '.') ?> <?= $ouvintes === 1 ? 'ouvinte mensal' : 'ouvintes mensais' ?> • <span data-followers><?= number_format($seguidores, 0, ',', '.') ?></span> <?= $seguidores === 1 ? 'seguidor' : 'seguidores' ?></p>
            </div>
        </div>
    </header>

    <div class="action-bar">
        <button type="button" class="play-big" data-action="play-all" data-target="#popularList" aria-label="Tocar <?= e($artista['artista_nome']) ?>" <?= $populares ? '' : 'disabled' ?>><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
        <button type="button" class="btn-pill btn-outline follow-btn<?= $segue ? ' is-following' : '' ?>" data-follow="<?= $id ?>" aria-pressed="<?= $segue ? 'true' : 'false' ?>"><?= $segue ? 'Seguindo' : 'Seguir' ?></button>
        <?php if ($ehDono): ?>
        <a class="btn-pill btn-ghost" href="perfil.php#artista"><?= icone('edit') ?> Editar página</a>
        <a class="btn-pill btn-ghost" href="editarFoto.php"><?= icone('image') ?> Editar fotos</a>
        <?php endif; ?>
    </div>

    <section class="artist-section">
        <h2 class="section-title">Populares</h2>
        <?php if ($populares): ?>
        <div id="popularList" class="popular-list">
            <?= renderListaFaixas($conexao, $populares, [
                'album' => false,
                'cabecalho' => false,
                'contexto' => 'artista:' . $id,
                'extra' => function ($linha) { return (int) $linha['total_views'] > 0 ? number_format((int) $linha['total_views'], 0, ',', '.') . ' plays' : ''; },
            ]) ?>
        </div>
        <?php else: ?>
        <p class="muted">Este artista ainda não publicou músicas.</p>
        <?php endif; ?>
    </section>

    <?php if ($albuns || $singles): ?>
    <section class="artist-section">
        <div class="section-head">
            <h2 class="section-title">Discografia</h2>
            <div class="chips small" data-disco-tabs>
                <?php if ($albuns): ?><button type="button" class="chip is-active" data-disco="albuns">Álbuns e EPs</button><?php endif; ?>
                <?php if ($singles): ?><button type="button" class="chip<?= $albuns ? '' : ' is-active' ?>" data-disco="singles">Singles</button><?php endif; ?>
            </div>
        </div>
        <?php if ($albuns): ?>
        <div class="card-grid" data-disco-panel="albuns"><?= implode('', array_map('renderCardAlbum', $albuns)) ?></div>
        <?php endif; ?>
        <?php if ($singles): ?>
        <div class="card-grid" data-disco-panel="singles" <?= $albuns ? 'hidden' : '' ?>><?= implode('', array_map('renderCardFaixa', $singles)) ?></div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="artist-section">
        <h2 class="section-title">Sobre</h2>
        <div class="about-card" style="background-image: url('<?= e($imagemSobre) ?>'); background-position: <?= e($posicaoSobre) ?>">
            <div class="about-overlay">
                <p class="about-stats"><strong><?= number_format($ouvintes, 0, ',', '.') ?></strong> ouvintes mensais • <strong data-followers><?= number_format($seguidores, 0, ',', '.') ?></strong> seguidores</p>
                <p class="about-text"><?= nl2br(e($artista['artista_descricao'] ?: 'Este artista ainda não escreveu uma descrição.')) ?></p>
                <p class="about-meta">
                    <?php if ($artista['artista_cidade']): ?><span><?= icone('home', 'inline') ?> <?= e($artista['artista_cidade']) ?></span><?php endif; ?>
                    <?php if ($artista['artista_link'] && preg_match('#^https?://#i', $artista['artista_link'])): ?>
                    <a href="<?= e($artista['artista_link']) ?>" target="_blank" rel="noopener noreferrer"><?= icone('external', 'inline') ?> Página oficial</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </section>

    <?= renderCarrossel('Você também pode gostar', implode('', array_map('renderCardArtista', $similares))) ?>
</div>
<?php
endif;
include "Componentes/paginas/footer.php";
