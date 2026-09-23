<?php
// Página de um gênero ou humor: todas as músicas marcadas com a categoria.
$paginaId = 'genero';
$paginaSpa = true;
include "Componentes/paginas/php/app.php";
include_once "Componentes/paginas/php/geo.php";

$categoria = consultarUm($conexao, "SELECT * FROM categoria WHERE categoria_id = ?", "i", [(int) ($_GET['id'] ?? 0)]);
$tituloPagina = $categoria ? $categoria['categoria_nome'] : 'Categoria não encontrada';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

if (!$categoria):
    echo renderVazio('search', 'Categoria não encontrada', 'Ela pode ter sido removida.', '<a class="btn-pill btn-light" href="buscar.php">Voltar para a busca</a>');
else:
    // Mesma lógica das recomendações: locais e pouco ouvidas primeiro.
    $musicas = consultarRecomendadas($conexao, ['categoria' => $categoria['categoria_id'], 'limite' => 200]);
    $artistas = consultar($conexao, "SELECT DISTINCT a.artista_id, a.artista_nome, a.artista_image, a.artista_image_pos FROM artista a
        INNER JOIN musica m ON m.musica_artista = a.artista_id INNER JOIN musica_categoria mc ON mc.musica_id = m.musica_id
        WHERE mc.categoria_id = ? LIMIT 20", "i", [$categoria['categoria_id']]);
    $duracao = array_sum(array_column($musicas, 'musica_duracao'));
?>
<div class="collection" style="--hero-color: <?= e($categoria['categoria_cor']) ?>">
    <header class="hero hero-genre">
        <div class="hero-info">
            <span class="hero-type"><?= $categoria['categoria_tipo'] === 'humor' ? 'Humor' : 'Gênero' ?></span>
            <h1 class="hero-title"><?= e($categoria['categoria_nome']) ?></h1>
            <div class="hero-meta"><?= pluralizar(count($musicas), 'música', 'músicas') ?><?= $duracao ? ', ' . formatarDuracaoTotal($duracao) : '' ?></div>
        </div>
    </header>

    <div class="action-bar">
        <button type="button" class="play-big" data-action="play-all" aria-label="Tocar <?= e($categoria['categoria_nome']) ?>" <?= $musicas ? '' : 'disabled' ?>><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
        <button type="button" class="icon-btn big" data-action="shuffle-all" aria-label="Tocar em ordem aleatória" title="Tocar em ordem aleatória" <?= $musicas ? '' : 'disabled' ?>><?= icone('shuffle') ?></button>
    </div>

    <?php if ($musicas): ?>
        <?= renderAvisoLocalizacao($conexao) ?>
        <?= renderListaFaixas($conexao, $musicas, [
            'contexto' => 'genero:' . $categoria['categoria_id'],
            'extra' => function ($linha) { return e(motivoRecomendacao($linha)); },
            'cabecalhoExtra' => 'Por que aparece',
        ]) ?>
    <?php else: ?>
        <?= renderVazio('music', 'Ainda não há músicas aqui', 'Artistas podem marcar suas músicas com este gênero ao cadastrá-las.') ?>
    <?php endif; ?>

    <?= renderCarrossel('Artistas de ' . $categoria['categoria_nome'], implode('', array_map('renderCardArtista', $artistas))) ?>
</div>
<?php
endif;
include "Componentes/paginas/footer.php";
