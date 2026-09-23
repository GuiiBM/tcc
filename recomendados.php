<?php
// Recomendações: a lógica do Ressonance é divulgar quem ainda não é ouvido.
// Aparecem primeiro as músicas de artistas mais PERTO do ouvinte e com
// MENOS reproduções (ver consultarRecomendadas em geo.php).
$paginaId = 'recomendados';
$paginaSpa = true;
$tituloPagina = 'Recomendações';
include "Componentes/paginas/head.php";
include_once "Componentes/paginas/php/geo.php";
include "Componentes/paginas/header.php";

$musicas = consultarRecomendadas($conexao, ['limite' => 50]);
$local = localizacaoVisitante($conexao);
?>
<div class="collection">
    <header class="hero" style="--hero-color: #2d4a5a">
        <div class="hero-cover recommend-cover"><?= icone('explore') ?></div>
        <div class="hero-info">
            <span class="hero-type">Para você</span>
            <h1 class="hero-title">Recomendações</h1>
            <p class="hero-desc">Artistas independentes <?= $local ? 'perto de você' : '' ?> que ainda têm poucas reproduções aparecem primeiro. Quanto mais perto e menos ouvida, mais a música é divulgada.</p>
        </div>
    </header>
    <div class="action-bar">
        <button type="button" class="play-big" data-action="play-all" aria-label="Tocar recomendações" <?= $musicas ? '' : 'disabled' ?>><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
        <button type="button" class="icon-btn big" data-action="shuffle-all" aria-label="Tocar em ordem aleatória" title="Tocar em ordem aleatória" <?= $musicas ? '' : 'disabled' ?>><?= icone('shuffle') ?></button>
    </div>
    <?= renderAvisoLocalizacao($conexao) ?>
    <?= $musicas ? renderListaFaixas($conexao, $musicas, [
        'contexto' => 'recomendados',
        'extra' => function ($linha) { return e(motivoRecomendacao($linha)); },
        'cabecalhoExtra' => 'Por que aparece',
    ]) : renderVazio('music', 'Nenhuma música cadastrada') ?>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
