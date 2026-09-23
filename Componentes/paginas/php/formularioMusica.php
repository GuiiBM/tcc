<?php
// Formulário de música (novo ou edição), usado em musicas.php e editarMusica.php.
// $musica: linha da música (edição) ou null (nova); $albuns: álbuns que podem
// ser escolhidos; $artistas: lista para o admin escolher (null = não mostra).
function renderFormularioMusica($conexao, $musica, $albuns, $artistas = null) {
    $editando = (bool) $musica;
    $categorias = consultar($conexao, "SELECT * FROM categoria ORDER BY categoria_tipo, categoria_ordem, categoria_nome");
    $marcadas = [];
    if ($editando) {
        foreach (consultar($conexao, "SELECT categoria_id FROM musica_categoria WHERE musica_id = ?", "i", [$musica['musica_id']]) as $c) {
            $marcadas[(int) $c['categoria_id']] = true;
        }
    }
    $maxBytes = limiteUploadBytes();
    $maxMb = round($maxBytes / 1048576);
    ob_start();
    ?>
    <form class="form-stack" action="api/musicas.php" enctype="multipart/form-data" data-api-form data-music-form <?= $editando ? 'data-reload' : 'data-reset data-redirect="musicas.php?aba=musicas"' ?> data-max-bytes="<?= $maxBytes ?>">
        <input type="hidden" name="acao" value="<?= $editando ? 'editar_musica' : 'criar_musica' ?>">
        <?php if ($editando): ?><input type="hidden" name="musica_id" value="<?= (int) $musica['musica_id'] ?>"><?php endif; ?>
        <input type="hidden" name="duracao" value="">

        <div class="form-grid">
            <label class="field"><span>Título</span><input type="text" name="titulo" required maxlength="100" value="<?= e($musica['musica_titulo'] ?? '') ?>"></label>
            <?php if ($artistas !== null && !$editando): ?>
            <label class="field"><span>Artista</span>
                <select name="artista_id" required data-artist-select>
                    <option value="">Escolha o artista…</option>
                    <?php foreach ($artistas as $a): ?>
                    <option value="<?= (int) $a['artista_id'] ?>"><?= e($a['artista_nome']) ?><?= $a['artista_cidade'] ? ' (' . e($a['artista_cidade']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Para cadastrar um artista novo, use a página <a href="artistas.php">Artistas</a>.</small>
            </label>
            <?php endif; ?>
            <label class="field"><span>Álbum</span>
                <select name="album_id" data-album-select>
                    <option value="">Nenhum (single)</option>
                    <?php foreach ($albuns as $al): ?>
                    <option value="<?= (int) $al['album_id'] ?>" data-artist="<?= (int) $al['album_artista'] ?>" <?= (int) ($musica['album_id'] ?? 0) === (int) $al['album_id'] ? 'selected' : '' ?>><?= e($al['album_titulo']) ?><?= $artistas !== null ? ' — ' . e($al['artista_nome']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field"><span>Nº da faixa no álbum</span><input type="number" name="faixa" min="1" max="999" value="<?= e($musica['musica_faixa'] ?? '') ?>"></label>
        </div>

        <div class="form-grid">
            <label class="field"><span><?= $editando ? 'Trocar capa' : 'Capa' ?> (imagem)</span><input type="file" name="capa" accept="image/*" data-max-check></label>
            <label class="field"><span><?= $editando ? 'Trocar áudio' : 'Áudio' ?> (MP3, M4A, OGG, WAV ou FLAC, até <?= $maxMb ?> MB)</span><input type="file" name="audio" accept="audio/*,.mp3,.m4a,.ogg,.wav,.flac" <?= $editando ? '' : 'required' ?> data-max-check data-duration-source></label>
            <label class="field"><span>Versão compacta (opcional, para “Economia de dados”)</span><input type="file" name="audio_baixa" accept="audio/*,.mp3,.m4a,.ogg" data-max-check>
                <small>Um MP3 de 64–96 kbps da mesma música. Se não enviar, todos ouvem o arquivo original.</small></label>
        </div>
        <?php if ($editando && $musica['musica_link_baixa']): ?>
        <label class="check"><input type="checkbox" name="remover_baixa" value="1"> Remover a versão compacta atual</label>
        <?php endif; ?>

        <fieldset class="chip-options">
            <legend>Gêneros e humores</legend>
            <?php foreach ($categorias as $c): ?>
            <label class="chip-option" style="--genre: <?= e($c['categoria_cor']) ?>"><input type="checkbox" name="categorias[]" value="<?= (int) $c['categoria_id'] ?>" <?= isset($marcadas[(int) $c['categoria_id']]) ? 'checked' : '' ?>><span><?= e($c['categoria_nome']) ?></span></label>
            <?php endforeach; ?>
        </fieldset>

        <?php if (!$editando): ?>
        <label class="field"><span>Letra (opcional — texto simples ou formato LRC sincronizado)</span>
            <textarea name="letra" rows="5" placeholder="[00:12.50] Primeiro verso&#10;[00:17.80] Segundo verso&#10;&#10;Você também pode sincronizar depois, em Editar."></textarea></label>
        <?php endif; ?>

        <div class="row-actions">
            <button type="submit" class="btn-pill btn-accent"><?= $editando ? 'Salvar alterações' : 'Publicar música' ?></button>
            <span class="upload-status" data-upload-status></span>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
?>
