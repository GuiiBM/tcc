<?php
// Fim do casco do app: fecha a área de conteúdo e adiciona o painel lateral
// (fila/letra), o player fixo, a navegação inferior (mobile), os ícones e os
// scripts. Tudo aqui fica na página enquanto o usuário navega (só o
// #app-content é trocado), por isso a música não para.
$usuarioFooter = usuarioAtual();
$configApp = [
    'logado' => (bool) $usuarioFooter,
    'usuario' => $usuarioFooter ? [
        'id' => (int) $usuarioFooter['usuario_id'],
        'nome' => $usuarioFooter['usuario_nome'],
        'admin' => $usuarioFooter['usuario_tipo'] === 'admin',
        'qualidade' => $usuarioFooter['usuario_qualidade'] ?? 'auto',
    ] : null,
    'curtidas' => array_keys(idsCurtidos($conexao)),
    'playlists' => $usuarioFooter ? array_map(function ($p) {
        return ['id' => (int) $p['playlist_id'], 'nome' => $p['playlist_nome']];
    }, playlistsDoUsuario($conexao, $usuarioFooter['usuario_id'])) : [],
];
?>
        </div><!-- /#app-content -->
    </div><!-- /.app-main -->

    <aside class="side-panel" id="sidePanel" aria-labelledby="sidePanelTitle" hidden>
        <div class="side-head">
            <h2 id="sidePanelTitle">Fila</h2>
            <div class="side-head-tools">
                <button type="button" class="btn-text" id="queueClear" data-action="queue-clear">Limpar fila</button>
                <button type="button" class="icon-btn" data-action="close-panel" aria-label="Fechar painel"><?= icone('close') ?></button>
            </div>
        </div>
        <div class="side-body" id="queuePanel" data-panel="queue">
            <h3 class="side-sub">Tocando agora</h3>
            <div id="queueNow"></div>
            <h3 class="side-sub" id="queueNextTitle">A seguir</h3>
            <p class="side-hint">Arraste pela alça <?= icone('drag') ?> para reordenar.</p>
            <ol class="queue-list" id="queueList"></ol>
        </div>
        <div class="side-body lyrics-body" id="lyricsPanel" data-panel="lyrics" hidden>
            <div class="lyrics-lines" id="lyricsLines"></div>
        </div>
    </aside>

    <section class="player is-empty" id="player" aria-label="Player de áudio">
        <audio id="audioPlayer" preload="metadata"></audio>
        <div class="mini-progress" aria-hidden="true"><div class="mini-progress-fill" id="miniProgress"></div></div>
        <button type="button" class="icon-btn player-collapse" data-action="player-collapse" aria-label="Minimizar player"><?= icone('chevron-down') ?></button>

        <div class="player-now">
            <button type="button" class="player-cover-btn" data-action="player-expand" aria-label="Abrir player">
                <img id="playerCover" class="player-cover" src="Componentes/icones/icone.png" alt="">
            </button>
            <div class="player-meta">
                <a id="playerTitle" class="player-title" href="#">Nada tocando</a>
                <a id="playerArtist" class="player-artist" href="#">Escolha uma música</a>
            </div>
            <button type="button" class="like-toggle player-like" id="playerLike" data-like-id="" aria-pressed="false" aria-label="Salvar em Músicas Curtidas" title="Curtir (C)" disabled>
                <?= icone('heart', 'icon-heart-outline') ?><?= icone('heart-fill', 'icon-heart-fill') ?>
            </button>
            <button type="button" class="icon-btn player-dislike" id="playerDislike" aria-pressed="false" aria-label="Não gostei" title="Não gostei" disabled><?= icone('thumb-down') ?><span id="dislikeCount"></span></button>
            <button type="button" class="mini-play" id="btnMiniPlay" aria-label="Tocar"><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
            <button type="button" class="icon-btn player-close close-mini" data-close-player aria-label="Fechar o player" title="Fechar o player (X)"><?= icone('close') ?></button>
        </div>

        <div class="player-center">
            <div class="player-controls">
                <button type="button" class="ctrl toggle music-only" id="btnShuffle" aria-pressed="false" aria-label="Modo aleatório" title="Aleatório (S)"><?= icone('shuffle') ?></button>
                <button type="button" class="ctrl podcast-only" id="btnBack15" aria-label="Voltar 15 segundos" title="Voltar 15 s (←)"><?= icone('replay15') ?></button>
                <button type="button" class="ctrl" id="btnPrev" aria-label="Anterior" title="Anterior (Shift + ←)"><?= icone('prev') ?></button>
                <button type="button" class="ctrl ctrl-play" id="btnPlay" aria-label="Tocar" title="Tocar/Pausar (Espaço)"><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
                <button type="button" class="ctrl" id="btnNext" aria-label="Próxima" title="Próxima (Shift + →)"><?= icone('next') ?></button>
                <button type="button" class="ctrl podcast-only" id="btnFwd15" aria-label="Avançar 15 segundos" title="Avançar 15 s (→)"><?= icone('forward15') ?></button>
                <button type="button" class="ctrl toggle music-only" id="btnRepeat" aria-pressed="false" aria-label="Repetir: desligado" title="Repetir (R)"><?= icone('repeat', 'icon-repeat') ?><?= icone('repeat-one', 'icon-repeat-one') ?></button>
            </div>
            <div class="player-scrub">
                <span class="time" id="timeElapsed">0:00</span>
                <div class="scrubber" id="scrubber" role="slider" tabindex="0" aria-label="Posição da música" aria-valuemin="0" aria-valuemax="0" aria-valuenow="0" aria-valuetext="0:00">
                    <div class="scrub-track"><div class="scrub-buffer" id="scrubBuffer"></div><div class="scrub-fill" id="scrubFill"></div><div class="scrub-thumb" id="scrubThumb"></div></div>
                    <div class="scrub-tooltip" id="scrubTooltip" hidden>0:00</div>
                </div>
                <button type="button" class="time time-toggle" id="timeRight" title="Alternar entre tempo restante e duração total">-0:00</button>
            </div>
        </div>

        <div class="player-extra">
            <button type="button" class="speed-btn podcast-only" id="btnSpeed" aria-label="Velocidade de reprodução" title="Velocidade (Shift + > / <)">1x</button>
            <button type="button" class="ctrl toggle" id="btnLyrics" data-action="toggle-lyrics" aria-pressed="false" aria-label="Letra" title="Letra (Y)"><?= icone('lyrics') ?></button>
            <button type="button" class="ctrl toggle" id="btnQueue" data-action="toggle-queue" aria-pressed="false" aria-label="Fila" title="Fila (Q)"><?= icone('queue') ?></button>
            <div class="volume">
                <button type="button" class="ctrl" id="btnMute" aria-label="Silenciar" title="Mudo (M)">
                    <?= icone('volume-high', 'vol-high') ?><?= icone('volume-mid', 'vol-mid') ?><?= icone('volume-low', 'vol-low') ?><?= icone('volume-off', 'vol-off') ?>
                </button>
                <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="70" aria-label="Volume">
            </div>
            <button type="button" class="ctrl player-close close-full" data-close-player aria-label="Fechar o player" title="Fechar o player (X)"><?= icone('close') ?></button>
        </div>
    </section>

    <nav class="bottom-nav" aria-label="Navegação">
        <a class="bottom-item<?= linkAtivo(['index.php', '']) ?>" data-nav="index.php" href="index.php"><?= icone('home') ?><span>Início</span></a>
        <a class="bottom-item<?= linkAtivo(['buscar.php', 'genero.php']) ?>" data-nav="buscar.php" href="buscar.php"><?= icone('search') ?><span>Buscar</span></a>
        <a class="bottom-item<?= linkAtivo(['biblioteca.php', 'curtidas.php', 'historico.php', 'playlist.php']) ?>" data-nav="biblioteca.php" href="biblioteca.php"><?= icone('library') ?><span>Biblioteca</span></a>
        <a class="bottom-item<?= linkAtivo('recomendados.php') ?>" data-nav="recomendados.php" href="recomendados.php"><?= icone('explore') ?><span>Para você</span></a>
        <?php if ($usuarioFooter): ?>
        <a class="bottom-item<?= linkAtivo('perfil.php') ?>" data-nav="perfil.php" href="perfil.php"><?= icone('user') ?><span>Perfil</span></a>
        <?php else: ?>
        <a class="bottom-item" href="login.php" data-no-spa><?= icone('user') ?><span>Entrar</span></a>
        <?php endif; ?>
    </nav>
</div><!-- /.app-shell -->

<div class="ctx-menu" id="ctxMenu" role="menu" hidden></div>
<div class="modal-root" id="modalRoot"></div>
<div class="toasts" id="toasts" role="status" aria-live="polite"></div>
<div class="page-loading" id="pageLoading" hidden></div>

<?php include __DIR__ . '/icones.php'; ?>

<script nonce="<?= e(nonceCsp()) ?>">window.APP = <?= json_encode($configApp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= asset('Componentes/configuracoes/JS/player.js') ?>"></script>
<script src="<?= asset('Componentes/configuracoes/JS/app.js') ?>"></script>
<script src="<?= asset('Componentes/configuracoes/JS/paginas.js') ?>"></script>
<?php if (!$paginaSpa): ?>
<script src="<?= asset('Componentes/configuracoes/JS/artistaAutocomplete.js') ?>"></script>
<?php endif; ?>
</body>
</html>
