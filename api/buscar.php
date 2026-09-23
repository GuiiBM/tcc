<?php
// Busca instantânea. GET ?q=texto&tipo=tudo|musicas|artistas|albuns|playlists|podcasts
// Devolve o HTML dos resultados já renderizado (mesmos componentes das páginas).
include __DIR__ . '/../Componentes/paginas/php/app.php';

$q = trim(mb_substr($_GET['q'] ?? '', 0, 100));
$tipo = $_GET['tipo'] ?? 'tudo';
$tipos = ['tudo', 'musicas', 'artistas', 'albuns', 'playlists', 'podcasts'];
if (!in_array($tipo, $tipos, true)) {
    $tipo = 'tudo';
}
if ($q === '') {
    jsonResposta(['success' => true, 'html' => '', 'total' => 0]);
}

$tudo = $tipo === 'tudo';
$escapado = addcslashes($q, '%_\\');
$contem = '%' . $escapado . '%';
$comeca = $escapado . '%';
$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

$musicas = $artistas = $albuns = $playlists = $podcasts = $episodios = [];

if ($tudo || $tipo === 'musicas') {
    $limite = $tudo ? 4 : 50;
    $musicas = consultarFaixas($conexao, "WHERE m.musica_titulo LIKE ? OR a.artista_nome LIKE ? OR al.album_titulo LIKE ?
        OR EXISTS (SELECT 1 FROM musica_categoria mc INNER JOIN categoria c ON c.categoria_id = mc.categoria_id WHERE mc.musica_id = m.musica_id AND c.categoria_nome LIKE ?)
        ORDER BY (m.musica_titulo LIKE ?) DESC, (m.musica_titulo LIKE ?) DESC, (a.artista_nome LIKE ?) DESC,
        (SELECT COUNT(*) FROM visualizacoes v WHERE v.musica_id = m.musica_id) DESC
        LIMIT ?", "sssssssi", [$contem, $contem, $contem, $contem, $comeca, $contem, $comeca, $limite]);
}

if ($tudo || $tipo === 'artistas') {
    $limite = $tudo ? 10 : 50;
    $artistas = consultar($conexao, "SELECT a.artista_id, a.artista_nome, a.artista_image, a.artista_image_pos FROM artista a
        WHERE (a.artista_nome LIKE ? OR a.artista_cidade LIKE ?) AND " . SQL_ARTISTA_VISIVEL . "
        ORDER BY (a.artista_nome LIKE ?) DESC, a.artista_nome LIMIT ?", "sssi", [$contem, $contem, $comeca, $limite]);
}

if ($tudo || $tipo === 'albuns') {
    $limite = $tudo ? 10 : 50;
    $albuns = consultar($conexao, "SELECT al.*, a.artista_nome FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista
        WHERE al.album_titulo LIKE ? OR a.artista_nome LIKE ?
        ORDER BY (al.album_titulo LIKE ?) DESC, al.album_ano DESC LIMIT ?", "sssi", [$contem, $contem, $comeca, $limite]);
}

if ($tudo || $tipo === 'playlists') {
    $limite = $tudo ? 10 : 50;
    $playlists = consultar($conexao, "SELECT p.*, u.usuario_nome AS dono_nome FROM playlist p INNER JOIN usuarios u ON u.usuario_id = p.usuario_id
        WHERE (p.playlist_publica = 1 OR p.usuario_id = ?) AND (p.playlist_nome LIKE ? OR p.playlist_descricao LIKE ?)
        ORDER BY (p.playlist_nome LIKE ?) DESC, p.playlist_criada DESC LIMIT ?", "isssi", [$usuarioId, $contem, $contem, $comeca, $limite]);
}

if ($tudo || $tipo === 'podcasts') {
    $limite = $tudo ? 10 : 50;
    $podcasts = consultar($conexao, "SELECT * FROM podcast WHERE podcast_titulo LIKE ? OR podcast_autor LIKE ? OR podcast_descricao LIKE ?
        ORDER BY (podcast_titulo LIKE ?) DESC LIMIT ?", "ssssi", [$contem, $contem, $contem, $comeca, $limite]);
    if (!$tudo) {
        $episodios = consultar($conexao, "SELECT e.*, p.podcast_titulo, p.podcast_capa FROM episodio e INNER JOIN podcast p ON p.podcast_id = e.podcast_id
            WHERE e.episodio_titulo LIKE ? OR e.episodio_descricao LIKE ? ORDER BY e.episodio_data DESC LIMIT 30", "ss", [$contem, $contem]);
    }
}

$total = count($musicas) + count($artistas) + count($albuns) + count($playlists) + count($podcasts) + count($episodios);
if ($total === 0) {
    jsonResposta(['success' => true, 'total' => 0, 'html' => renderVazio('search', 'Nenhum resultado para “' . $q . '”', 'Confira a ortografia ou tente outras palavras.')]);
}

$mosaicos = capasPlaylists($conexao, array_column($playlists, 'playlist_id'));
$cardsArtistas = implode('', array_map('renderCardArtista', $artistas));
$cardsAlbuns = implode('', array_map('renderCardAlbum', $albuns));
$cardsPlaylists = implode('', array_map(function ($p) use ($mosaicos) {
    return renderCardPlaylist($p, $mosaicos[(int) $p['playlist_id']] ?? []);
}, $playlists));
$cardsPodcasts = implode('', array_map('renderCardPodcast', $podcasts));

$html = '';
if ($tudo) {
    // Melhor resultado: artista cujo nome começa com o termo, senão a 1ª música.
    $melhor = '';
    $artistaTopo = null;
    foreach ($artistas as $a) {
        if (mb_stripos($a['artista_nome'], $q) === 0) {
            $artistaTopo = $a;
            break;
        }
    }
    if ($artistaTopo) {
        $melhor = '<a class="top-card" href="artista.php?id=' . (int) $artistaTopo['artista_id'] . '">'
            . '<img class="is-round" src="' . e(imagemOuPadrao($artistaTopo['artista_image'])) . '" alt=""' . estiloPosicao($artistaTopo['artista_image_pos']) . '>'
            . '<strong>' . e($artistaTopo['artista_nome']) . '</strong><span class="tag">Artista</span>'
            . '<button type="button" class="card-play" data-action="play-url" data-url="api/faixas.php?tipo=artista&amp;id=' . (int) $artistaTopo['artista_id'] . '" aria-label="Tocar">' . icone('play') . '</button></a>';
    } elseif ($musicas) {
        $f = faixaParaArray($musicas[0]);
        $melhor = '<div class="top-card" data-track-group><div data-track="' . dadosFaixaAttr($f) . '" data-track-key="musica:' . $f['id'] . '">'
            . '<img src="' . e($f['cover']) . '" alt="">'
            . '<strong>' . e($f['title']) . '</strong><span class="top-sub"><span class="tag">Música</span> ' . e($f['artist']) . '</span>'
            . '<button type="button" class="card-play" data-action="play-card" aria-label="Tocar">' . icone('play', 'icon-play') . icone('pause', 'icon-pause') . '</button></div></div>';
    }

    if ($melhor || $musicas) {
        $html .= '<div class="search-top">';
        if ($melhor) {
            $html .= '<section class="top-result"><h2>Melhor resultado</h2>' . $melhor . '</section>';
        }
        if ($musicas) {
            $html .= '<section class="top-songs"><h2>Músicas</h2>' . renderListaFaixas($conexao, $musicas, ['album' => false, 'cabecalho' => false, 'contexto' => 'busca']) . '</section>';
        }
        $html .= '</div>';
    }
    $html .= renderCarrossel('Artistas', $cardsArtistas);
    $html .= renderCarrossel('Álbuns', $cardsAlbuns);
    $html .= renderCarrossel('Playlists', $cardsPlaylists);
    $html .= renderCarrossel('Podcasts', $cardsPodcasts);
} elseif ($tipo === 'musicas') {
    $html = renderListaFaixas($conexao, $musicas, ['contexto' => 'busca']);
} elseif ($tipo === 'podcasts') {
    $html = ($cardsPodcasts ? '<div class="card-grid">' . $cardsPodcasts . '</div>' : '');
    if ($episodios) {
        include_once __DIR__ . '/../Componentes/paginas/php/podcasts.php';
        $html .= '<h2 class="section-title">Episódios</h2>' . renderListaEpisodios($episodios, true);
    }
} else {
    $cards = ['artistas' => $cardsArtistas, 'albuns' => $cardsAlbuns, 'playlists' => $cardsPlaylists][$tipo];
    $html = '<div class="card-grid">' . $cards . '</div>';
}

jsonResposta(['success' => true, 'total' => $total, 'html' => $html]);
