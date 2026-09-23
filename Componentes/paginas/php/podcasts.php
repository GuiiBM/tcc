<?php
// podcasts.php - Renderização de episódios (página do podcast e busca).
include_once __DIR__ . '/app.php';

function formatarDataCurta($data) {
    $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    $ts = strtotime($data);
    if (!$ts) {
        return '';
    }
    $texto = (int) date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1];
    return date('Y', $ts) !== date('Y') ? $texto . ' de ' . date('Y', $ts) : $texto;
}

function renderListaEpisodios($episodios, $mostrarPodcast = false, $contexto = 'episodios') {
    if (empty($episodios)) {
        return '';
    }
    $html = '<div class="episode-list" data-tracklist data-context="' . e($contexto) . '">';
    foreach ($episodios as $ep) {
        $faixa = episodioParaArray($ep);
        $duracao = $faixa['duration'] ? formatarDuracaoTotal($faixa['duration']) : '';
        $html .= '<article class="episode-row" data-track="' . dadosFaixaAttr($faixa) . '" data-track-key="episodio:' . $faixa['id'] . '">'
            . ($mostrarPodcast ? '<img class="ep-cover" src="' . e($faixa['cover']) . '" alt="" loading="lazy">' : '')
            . '<div class="ep-main">'
            . ($mostrarPodcast ? '<a class="ep-podcast" href="podcast.php?id=' . $faixa['podcastId'] . '">' . e($ep['podcast_titulo']) . '</a>' : '')
            . '<h3 class="ep-title">' . e($faixa['title']) . '</h3>'
            . ($ep['episodio_descricao'] ? '<p class="ep-desc">' . e($ep['episodio_descricao']) . '</p>' : '')
            . '<div class="ep-meta">'
            . '<button type="button" class="ep-play" data-action="play-card" aria-label="Tocar episódio">' . icone('play', 'icon-play') . icone('pause', 'icon-pause') . '</button>'
            . '<span>' . e(formatarDataCurta($ep['episodio_data'])) . ($duracao ? ' • <span data-duration-for="e' . $faixa['id'] . '">' . e($duracao) . '</span>' : '') . '</span>'
            . '<span class="ep-progress" data-episode-progress="' . $faixa['id'] . '" hidden><span></span></span>'
            . '<button type="button" class="icon-btn ep-more" data-action="track-menu" aria-label="Mais opções">' . icone('more') . '</button>'
            . '</div></div></article>';
    }
    return $html . '</div>';
}
?>
