<?php
// Busca: campo com resultados instantâneos (divididos em Músicas, Artistas,
// Álbuns, Playlists e Podcasts) e, com o campo vazio, a grade de gêneros.
$paginaId = 'busca';
$paginaSpa = true;
$tituloPagina = 'Buscar';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$q = trim($_GET['q'] ?? '');
$categorias = consultar($conexao, "SELECT c.*, (SELECT COUNT(*) FROM musica_categoria mc WHERE mc.categoria_id = c.categoria_id) AS total
    FROM categoria c ORDER BY c.categoria_tipo, c.categoria_ordem, c.categoria_nome");
$abas = ['tudo' => 'Tudo', 'musicas' => 'Músicas', 'artistas' => 'Artistas', 'albuns' => 'Álbuns', 'playlists' => 'Playlists', 'podcasts' => 'Podcasts'];

function renderGradeCategorias($categorias, $tipo, $titulo) {
    $itens = array_filter($categorias, function ($c) use ($tipo) { return $c['categoria_tipo'] === $tipo; });
    if (!$itens) {
        return '';
    }
    $html = '<h2 class="section-title">' . e($titulo) . '</h2><div class="genre-grid">';
    foreach ($itens as $c) {
        $html .= '<a class="genre-card" href="genero.php?id=' . (int) $c['categoria_id'] . '" style="--genre: ' . e($c['categoria_cor']) . '">'
            . '<span>' . e($c['categoria_nome']) . '</span>'
            . ((int) $c['total'] > 0 ? '<small>' . pluralizar((int) $c['total'], 'música', 'músicas') . '</small>' : '')
            . icone($tipo === 'humor' ? 'music' : 'album', 'genre-icon') . '</a>';
    }
    return $html . '</div>';
}
?>
<div class="search-page">
    <form class="search-box" action="buscar.php" method="get" role="search" data-no-spa="1">
        <?= icone('search') ?>
        <input type="search" id="searchInput" name="q" value="<?= e($q) ?>" placeholder="O que você quer ouvir?" autocomplete="off" aria-label="Buscar músicas, artistas, álbuns, playlists e podcasts" aria-controls="searchResults">
        <button type="button" class="icon-btn" id="searchClear" aria-label="Limpar busca" <?= $q === '' ? 'hidden' : '' ?>><?= icone('close') ?></button>
    </form>

    <div class="chips" role="tablist" aria-label="Filtrar resultados" id="searchTabs" <?= $q === '' ? 'hidden' : '' ?>>
        <?php foreach ($abas as $valor => $rotulo): ?>
        <button type="button" class="chip<?= $valor === 'tudo' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $valor === 'tudo' ? 'true' : 'false' ?>" data-search-tab="<?= $valor ?>"><?= $rotulo ?></button>
        <?php endforeach; ?>
    </div>

    <div id="searchResults" class="search-results" aria-live="polite" <?= $q === '' ? 'hidden' : '' ?>></div>

    <div id="browseAll" <?= $q !== '' ? 'hidden' : '' ?>>
        <h2 class="section-title">Explorar</h2>
        <div class="genre-grid explore-grid">
            <a class="genre-card" href="recomendados.php" style="--genre: #2d6a8a"><span>Recomendações</span><small>Artistas perto de você</small><?= icone('explore', 'genre-icon') ?></a>
            <a class="genre-card" href="podcasts.php" style="--genre: #006450"><span>Podcasts</span><small>Programas e episódios</small><?= icone('podcast', 'genre-icon') ?></a>
            <a class="genre-card" href="artistas.php" style="--genre: #8d67ab"><span>Artistas</span><small>Todos os artistas</small><?= icone('user', 'genre-icon') ?></a>
        </div>
        <?= renderGradeCategorias($categorias, 'genero', 'Navegar por gênero') ?>
        <?= renderGradeCategorias($categorias, 'humor', 'Para cada momento') ?>
    </div>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
