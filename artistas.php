<?php
// Todos os artistas (com formulário rápido de cadastro para o admin).
$paginaId = 'artistas';
$paginaSpa = true;
$tituloPagina = 'Artistas';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$artistas = consultar($conexao, "SELECT a.artista_id, a.artista_nome, a.artista_image, a.artista_image_pos, a.artista_cidade FROM artista a WHERE " . SQL_ARTISTA_VISIVEL . " ORDER BY a.artista_nome");
?>
<div class="artists-page">
    <header class="page-head">
        <h1>Artistas</h1>
        <p class="muted"><?= pluralizar(count($artistas), 'artista independente', 'artistas independentes') ?> na plataforma.</p>
        <?php if (ehAdmin()): ?>
        <button type="button" class="btn-pill btn-light" data-toggle-target="#artistForm"><?= icone('plus') ?> Adicionar artista</button>
        <?php endif; ?>
    </header>

    <?php if (ehAdmin()): ?>
    <form id="artistForm" class="panel form-stack" action="Componentes/paginas/php/adicionarArtista.php" enctype="multipart/form-data" data-api-form data-reload hidden>
        <h2>Novo artista</h2>
        <div class="form-grid">
            <label class="field"><span>Nome</span><input type="text" name="artistName" required maxlength="100"></label>
            <label class="field"><span>Cidade</span><input type="text" name="artistCity" required maxlength="100"></label>
            <label class="field"><span>Página oficial (opcional)</span><input type="url" name="artistLink" placeholder="https://..."></label>
            <label class="field"><span>Foto</span><input type="file" name="artistImage" accept="image/*" required></label>
        </div>
        <label class="field"><span>Descrição (mínimo 8 palavras)</span><textarea name="artistDescription" rows="3" maxlength="512" required></textarea></label>
        <div><button type="submit" class="btn-pill btn-accent">Cadastrar artista</button></div>
    </form>
    <?php endif; ?>

    <?php if ($artistas): ?>
    <div class="card-grid">
        <?php foreach ($artistas as $a): ?>
            <?= renderCardColecao('artista.php?id=' . (int) $a['artista_id'], $a['artista_image'], $a['artista_nome'], $a['artista_cidade'] ?: 'Artista', 'api/faixas.php?tipo=artista&id=' . (int) $a['artista_id'], true, [], $a['artista_image_pos']) ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <?= renderVazio('user', 'Nenhum artista ainda') ?>
    <?php endif; ?>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
