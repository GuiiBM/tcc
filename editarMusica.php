<?php
// Editar uma música: dados, arquivos, categorias e letra (com ferramenta
// para sincronizar a letra com o áudio marcando o início de cada verso).
$paginaId = 'editar-musica';
$tituloPagina = 'Editar música';
include "Componentes/paginas/php/app.php";
include_once "Componentes/paginas/php/verificar_login.php";
redirecionarSeNaoLogado();
include_once "Componentes/paginas/php/formularioMusica.php";

$musica = consultarUm($conexao, "SELECT m.*, a.artista_nome FROM musica m INNER JOIN artista a ON a.artista_id = m.musica_artista WHERE m.musica_id = ?", "i", [(int) ($_GET['id'] ?? 0)]);
if (!$musica || !podeEditarArtista($musica['musica_artista'])) {
    header('Location: musicas.php');
    exit;
}
$albuns = consultar($conexao, "SELECT al.*, a.artista_nome FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista WHERE al.album_artista = ? ORDER BY al.album_titulo", "i", [$musica['musica_artista']]);

include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";
?>
<div class="manage-page">
    <header class="page-head">
        <a class="btn-pill btn-ghost small" href="musicas.php?aba=musicas"><?= icone('chevron-left', 'inline') ?> Voltar</a>
        <h1><?= e($musica['musica_titulo']) ?></h1>
        <p class="muted"><?= e($musica['artista_nome']) ?></p>
    </header>

    <section class="panel">
        <h2>Dados da música</h2>
        <?= renderFormularioMusica($conexao, $musica, $albuns) ?>
    </section>

    <section class="panel" id="letra">
        <h2>Letra</h2>
        <p class="muted">Cole a letra (um verso por linha) e use <strong>Sincronizar</strong>: toque a música e marque o início de cada verso com o botão ou a tecla <kbd>Enter</kbd>. O resultado fica no formato LRC, que o player usa para rolar a letra junto com o áudio.</p>
        <form class="form-stack" action="api/musicas.php" data-api-form>
            <input type="hidden" name="acao" value="salvar_letra">
            <input type="hidden" name="musica_id" value="<?= (int) $musica['musica_id'] ?>">
            <div class="lrc-tool" data-lrc-tool>
                <textarea name="letra" rows="14" class="lrc-text" data-lrc-text spellcheck="false" placeholder="Cole a letra aqui, um verso por linha."><?= e($musica['musica_letra']) ?></textarea>
                <div class="lrc-sync" data-lrc-panel hidden>
                    <audio controls preload="metadata" src="<?= e(linkAudio('m', $musica['musica_id'])) ?>" data-lrc-audio></audio>
                    <ol class="lrc-lines" data-lrc-lines></ol>
                    <div class="row-actions">
                        <button type="button" class="btn-pill btn-accent" data-lrc="mark">Marcar verso (Enter)</button>
                        <button type="button" class="btn-pill btn-ghost" data-lrc="undo">Desfazer (Backspace)</button>
                        <button type="button" class="btn-pill btn-ghost" data-lrc="back">−3 s</button>
                        <button type="button" class="btn-pill btn-light" data-lrc="finish">Concluir</button>
                        <button type="button" class="btn-pill btn-ghost" data-lrc="cancel">Cancelar</button>
                    </div>
                </div>
            </div>
            <div class="row-actions">
                <button type="button" class="btn-pill btn-light" data-lrc="start"><?= icone('clock', 'inline') ?> Sincronizar com o áudio</button>
                <button type="submit" class="btn-pill btn-accent">Salvar letra</button>
            </div>
        </form>
    </section>

    <section class="panel danger-zone">
        <h2>Excluir música</h2>
        <form action="api/musicas.php" data-api-form data-confirm="Excluir “<?= e($musica['musica_titulo']) ?>” para sempre?">
            <input type="hidden" name="acao" value="excluir_musica">
            <input type="hidden" name="musica_id" value="<?= (int) $musica['musica_id'] ?>">
            <button type="submit" class="btn-pill btn-danger"><?= icone('trash', 'inline') ?> Excluir música</button>
        </form>
    </section>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
