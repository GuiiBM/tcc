<?php
// Página de um podcast com a lista de episódios.
$paginaId = 'podcast';
$paginaSpa = true;
include "Componentes/paginas/php/app.php";
include_once "Componentes/paginas/php/podcasts.php";

$podcast = consultarUm($conexao, "SELECT * FROM podcast WHERE podcast_id = ?", "i", [(int) ($_GET['id'] ?? 0)]);
$tituloPagina = $podcast ? $podcast['podcast_titulo'] : 'Podcast não encontrado';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

if (!$podcast):
    echo renderVazio('podcast', 'Podcast não encontrado', '', '<a class="btn-pill btn-light" href="podcasts.php">Ver podcasts</a>');
else:
    $episodios = consultar($conexao, "SELECT e.*, p.podcast_titulo, p.podcast_capa FROM episodio e INNER JOIN podcast p ON p.podcast_id = e.podcast_id WHERE e.podcast_id = ? ORDER BY e.episodio_data DESC", "i", [$podcast['podcast_id']]);
    $podeEditar = ehAdmin() || ((int) ($podcast['usuario_id'] ?? 0) === (int) ($_SESSION['usuario_id'] ?? -1));
?>
<div class="collection">
    <?= renderCabecalhoColecao([
        'tipo' => 'Podcast',
        'titulo' => $podcast['podcast_titulo'],
        'imagem' => $podcast['podcast_capa'],
        'meta' => '<span class="hero-owner">' . e($podcast['podcast_autor']) . '</span><span>' . pluralizar(count($episodios), 'episódio', 'episódios') . '</span>',
    ]) ?>

    <div class="action-bar">
        <button type="button" class="play-big" data-action="play-all" aria-label="Tocar episódio mais recente" <?= $episodios ? '' : 'disabled' ?>><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
        <?php if ($podeEditar): ?>
        <a class="btn-pill btn-ghost" href="gerenciarPodcasts.php?podcast=<?= (int) $podcast['podcast_id'] ?>" data-no-spa><?= icone('edit') ?> Gerenciar episódios</a>
        <?php endif; ?>
    </div>

    <?php if ($podcast['podcast_descricao']): ?>
    <section class="podcast-about">
        <h2 class="section-title">Sobre</h2>
        <p><?= nl2br(e($podcast['podcast_descricao'])) ?></p>
    </section>
    <?php endif; ?>

    <h2 class="section-title">Todos os episódios</h2>
    <?= $episodios ? renderListaEpisodios($episodios, false, 'podcast:' . $podcast['podcast_id']) : renderVazio('podcast', 'Nenhum episódio publicado ainda') ?>
</div>
<?php
endif;
include "Componentes/paginas/footer.php";
