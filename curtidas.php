<?php
// Playlist privada "Músicas Curtidas": tudo o que o usuário marcou com o coração.
$paginaId = 'curtidas';
$paginaSpa = true;
$tituloPagina = 'Músicas Curtidas';
include "Componentes/paginas/php/app.php";
if (!usuarioLogado()) {
    header('Location: login.php');
    exit;
}
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$usuario = usuarioAtual();
$musicas = consultarFaixas($conexao, "INNER JOIN curtidas c ON c.musica_id = m.musica_id WHERE c.usuario_id = ? AND c.tipo_curtida = 'curtida' ORDER BY c.data_curtida DESC", "i", [$usuario['usuario_id']], 'c.data_curtida');
$duracao = array_sum(array_column($musicas, 'musica_duracao'));
?>
<div class="collection" style="--hero-color: #5038a0">
    <header class="hero">
        <div class="hero-cover liked-cover"><?= icone('heart-fill') ?></div>
        <div class="hero-info">
            <span class="hero-type"><?= icone('lock') ?> Playlist privada</span>
            <h1 class="hero-title">Músicas Curtidas</h1>
            <div class="hero-meta"><span class="hero-owner"><?= e($usuario['usuario_nome']) ?></span><span data-liked-count><?= pluralizar(count($musicas), 'música', 'músicas') ?></span><?= $duracao ? '<span class="muted-strong">' . formatarDuracaoTotal($duracao) . '</span>' : '' ?></div>
        </div>
    </header>

    <div class="action-bar">
        <button type="button" class="play-big" data-action="play-all" aria-label="Tocar Músicas Curtidas" <?= $musicas ? '' : 'disabled' ?>><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
        <button type="button" class="icon-btn big" data-action="shuffle-all" aria-label="Tocar em ordem aleatória" title="Tocar em ordem aleatória" <?= $musicas ? '' : 'disabled' ?>><?= icone('shuffle') ?></button>
    </div>

    <?php if ($musicas): ?>
        <?= renderListaFaixas($conexao, $musicas, [
            'contexto' => 'curtidas',
            'extra' => function ($linha) { return e(date('d/m/Y', strtotime($linha['data_curtida']))); },
            'cabecalhoExtra' => 'Curtida em',
        ]) ?>
    <?php else: ?>
        <?= renderVazio('heart', 'Músicas que você curtir aparecem aqui', 'Toque no coração de qualquer música para salvá-la.', '<a class="btn-pill btn-light" href="buscar.php">Encontrar músicas</a>') ?>
    <?php endif; ?>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
