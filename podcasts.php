<?php
// Podcasts: programas e episódios mais recentes.
$paginaId = 'podcasts';
$paginaSpa = true;
$tituloPagina = 'Podcasts';
include "Componentes/paginas/head.php";
include_once "Componentes/paginas/php/podcasts.php";
include "Componentes/paginas/header.php";

$podcasts = consultar($conexao, "SELECT p.*, (SELECT COUNT(*) FROM episodio e WHERE e.podcast_id = p.podcast_id) AS total FROM podcast p ORDER BY p.podcast_data DESC");
$recentes = consultar($conexao, "SELECT e.*, p.podcast_titulo, p.podcast_capa FROM episodio e INNER JOIN podcast p ON p.podcast_id = e.podcast_id ORDER BY e.episodio_data DESC LIMIT 15");
?>
<div class="podcasts-page">
    <header class="page-head">
        <h1>Podcasts</h1>
        <p class="muted">Episódios longos com velocidade ajustável, pulo de 15 segundos e retomada de onde você parou.</p>
        <?php if (usuarioLogado()): ?>
        <a class="btn-pill btn-light" href="gerenciarPodcasts.php" data-no-spa><?= icone('plus') ?> Publicar podcast</a>
        <?php endif; ?>
    </header>

    <?php if ($podcasts): ?>
        <h2 class="section-title">Programas</h2>
        <div class="card-grid"><?= implode('', array_map('renderCardPodcast', $podcasts)) ?></div>
        <?php if ($recentes): ?>
        <h2 class="section-title">Episódios recentes</h2>
        <?= renderListaEpisodios($recentes, true, 'episodios-recentes') ?>
        <?php endif; ?>
    <?php else: ?>
        <?= renderVazio('podcast', 'Nenhum podcast publicado ainda', 'Seja o primeiro a publicar um programa.', usuarioLogado() ? '<a class="btn-pill btn-light" href="gerenciarPodcasts.php" data-no-spa>Publicar podcast</a>' : '') ?>
    <?php endif; ?>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
