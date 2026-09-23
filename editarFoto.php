<?php
// Editor de fotos: troca a foto de perfil, ajusta o enquadramento (arrastando
// a imagem) e, para quem tem página de artista, a foto do artista e a imagem
// da seção "Sobre".
$paginaId = 'editar-foto';
$paginaSpa = true;
$tituloPagina = 'Editar foto';
include "Componentes/paginas/php/app.php";
if (!usuarioLogado()) {
    header('Location: login.php');
    exit;
}
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$usuario = usuarioAtual();
$artista = $usuario['artista_id'] ? consultarUm($conexao, "SELECT * FROM artista WHERE artista_id = ?", "i", [$usuario['artista_id']]) : null;
$maxMb = round(limiteUploadBytes() / 1048576);
$fotoPadrao = 'Componentes/icones/icone.png';
// A foto do artista só ganha editor próprio quando é diferente da foto de perfil.
$fotoArtistaPropria = $artista && $artista['artista_image'] && $artista['artista_image'] !== $fotoPadrao && $artista['artista_image'] !== $usuario['usuario_foto'];

// Um bloco do editor: pré-visualização arrastável + escolher arquivo + salvar.
function blocoEditorFoto($o) {
    $posicao = posicaoImagem($o['posicao']);
    [$x, $y] = array_map('floatval', explode(' ', str_replace('%', '', $posicao)));
    ob_start(); ?>
    <section class="panel photo-editor-block" id="<?= e($o['id']) ?>">
        <h2><?= e($o['titulo']) ?></h2>
        <p class="muted"><?= e($o['descricao']) ?></p>
        <form class="form-stack" action="api/perfil.php" enctype="multipart/form-data" data-api-form data-full-reload data-photo-editor>
            <input type="hidden" name="acao" value="<?= e($o['acao']) ?>">
            <input type="hidden" name="posicao" value="<?= e($posicao) ?>" data-pos-input>
            <div class="photo-editor <?= e($o['formato']) ?>">
                <div class="crop-frame<?= $o['imagem'] ? '' : ' is-empty' ?>" data-crop-frame tabindex="0" role="application" aria-label="Arraste para ajustar o enquadramento. Use as setas do teclado para mover.">
                    <img src="<?= e($o['imagem'] ?: $GLOBALS['fotoPadrao']) ?>" alt="" referrerpolicy="no-referrer" draggable="false" data-crop-img style="object-position: <?= e($posicao) ?>">
                    <?php if ($o['formato'] === 'is-about'): ?>
                    <div class="about-overlay crop-about-overlay" aria-hidden="true">
                        <p class="about-text"><?= e(mb_strimwidth($o['texto'] ?: 'Sua biografia aparece aqui.', 0, 160, '…')) ?></p>
                    </div>
                    <?php endif; ?>
                    <span class="crop-hint" aria-hidden="true"><?= icone('drag', 'inline') ?> Arraste para ajustar</span>
                </div>
                <div class="photo-editor-side">
                    <?php if ($o['formato'] === 'is-circle'): ?>
                    <div class="crop-thumbs" aria-hidden="true">
                        <img src="<?= e($o['imagem'] ?: $GLOBALS['fotoPadrao']) ?>" alt="" referrerpolicy="no-referrer" data-crop-mirror style="object-position: <?= e($posicao) ?>" class="thumb-lg">
                        <img src="<?= e($o['imagem'] ?: $GLOBALS['fotoPadrao']) ?>" alt="" referrerpolicy="no-referrer" data-crop-mirror style="object-position: <?= e($posicao) ?>" class="thumb-sm">
                    </div>
                    <?php endif; ?>
                    <label class="field"><span>Horizontal</span><input type="range" min="0" max="100" step="0.5" value="<?= $x ?>" data-pos-axis="x"></label>
                    <label class="field"><span>Vertical</span><input type="range" min="0" max="100" step="0.5" value="<?= $y ?>" data-pos-axis="y"></label>
                    <button type="button" class="btn-pill btn-ghost" data-pos-reset>Centralizar</button>
                </div>
            </div>
            <label class="field"><span><?= e($o['rotuloArquivo']) ?> (JPG, PNG, WEBP ou GIF, até <?= $GLOBALS['maxMb'] ?> MB)</span><input type="file" name="foto" accept="image/*" data-crop-file></label>
            <div class="row-actions">
                <button type="submit" class="btn-pill btn-accent">Salvar</button>
                <?php if ($o['podeRemover']): ?>
                <button type="submit" class="btn-pill btn-ghost" name="remover" value="1" formnovalidate><?= e($o['rotuloRemover']) ?></button>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <?php return ob_get_clean();
}
?>
<div class="settings-page">
    <header class="page-head">
        <h1><?= icone('image') ?> Editar foto</h1>
        <p class="muted">Arraste a imagem (ou use os controles) até o ponto certo aparecer no enquadramento e clique em Salvar.</p>
    </header>

    <?= blocoEditorFoto([
        'id' => 'perfil', 'acao' => 'foto', 'formato' => 'is-circle',
        'titulo' => 'Foto de perfil',
        'descricao' => $fotoArtistaPropria || !$artista
            ? 'Aparece no menu da conta e nas suas playlists.'
            : 'Aparece no menu da conta, nas suas playlists e como foto da sua página de artista.',
        'imagem' => $usuario['usuario_foto'], 'posicao' => $usuario['usuario_foto_pos'],
        'rotuloArquivo' => 'Trocar foto', 'podeRemover' => (bool) $usuario['usuario_foto'], 'rotuloRemover' => 'Remover foto',
    ]) ?>

    <?php if ($fotoArtistaPropria): ?>
    <?= blocoEditorFoto([
        'id' => 'artista', 'acao' => 'foto_artista', 'formato' => 'is-circle',
        'titulo' => 'Foto do artista',
        'descricao' => 'Aparece na sua página de artista e nos cards de artista.',
        'imagem' => $artista['artista_image'], 'posicao' => $artista['artista_image_pos'],
        'rotuloArquivo' => 'Trocar foto do artista', 'podeRemover' => false, 'rotuloRemover' => '',
    ]) ?>
    <?php endif; ?>

    <?php if ($artista): ?>
    <?= blocoEditorFoto([
        'id' => 'sobre', 'acao' => 'sobre', 'formato' => 'is-about',
        'titulo' => 'Imagem do "Sobre"',
        'descricao' => $artista['artista_sobre']
            ? 'Imagem de fundo da seção "Sobre" da sua página de artista.'
            : 'No momento o "Sobre" usa sua foto de artista. Envie uma imagem mais larga (paisagem) para ela se encaixar melhor, ou só ajuste o enquadramento.',
        'imagem' => $artista['artista_sobre'] ?: imagemOuPadrao($artista['artista_image']),
        'posicao' => $artista['artista_sobre'] ? $artista['artista_sobre_pos'] : $artista['artista_image_pos'],
        'texto' => $artista['artista_descricao'],
        'rotuloArquivo' => 'Nova imagem para o "Sobre" (ideal em paisagem, ex.: 1600×900)',
        'podeRemover' => (bool) $artista['artista_sobre'], 'rotuloRemover' => 'Voltar a usar a foto do artista',
    ]) ?>
    <p class="muted small"><a href="artista.php?id=<?= (int) $artista['artista_id'] ?>">Ver minha página de artista</a></p>
    <?php endif; ?>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
