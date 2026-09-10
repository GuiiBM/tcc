<footer class="d-flex flex-wrap justify-content-between align-items-center py-3 mt-0 border-top">
        <audio id="audioPlayer" preload="metadata"></audio>
        
        <div class="music-info col-md-4">
            <div id="songTitle">Selecione uma música</div>
            <div id="songArtist">Artista</div>
            <div id="playStatus" class="play-status no-music">
                🎵 Nenhuma música
                <div class="sound-wave" style="display: none;">
                    <span></span><span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <div class="player-controls-center">
            <button type="button" class="circulo circulo2 player-btn mx-2" id="rewindBtn" title="Retroceder 5 segundos" aria-label="Retroceder 5 segundos" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11 18V6l-8.5 6 8.5 6zm.5-6l8.5 6V6l-8.5 6z"/></svg>
            </button>
            <button type="button" class="circulo player-btn player-btn-main mx-2 paused" id="playBtn" title="Play/Pause" aria-label="Play/Pause" disabled>
                <svg class="icon-play" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                <svg class="icon-pause" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zm8 0h4v14h-4z"/></svg>
            </button>
            <button type="button" class="circulo player-btn stop-btn mx-2" id="stopBtn" title="Parar" aria-label="Parar música" disabled>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
            </button>
            <button type="button" class="circulo circulo2 player-btn mx-2" id="forwardBtn" title="Avançar 5 segundos" aria-label="Avançar 5 segundos" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M13 6v12l8.5-6L13 6zm-.5 6L4 6v12l8.5-6z"/></svg>
            </button>
        </div>

        <div class="player-right-controls">
            <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="like-controls col-md-2 d-flex justify-content-center align-items-center">
                <button class="like-btn" id="likeBtn" onclick="curtirMusica('curtida')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span id="likeCount">0</span>
                </button>
                <button class="dislike-btn" id="dislikeBtn" onclick="curtirMusica('descurtida')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path>
                    </svg>
                    <span id="dislikeCount">0</span>
                </button>
            </div>
            <?php else: ?>
            <div class="col-md-2 d-flex justify-content-center align-items-center">
                <div class="login-prompt">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: 10px;">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10,17 15,12 10,7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <a href="login.php" class="login-link">Login para curtir</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="volume-control col-md-2 d-flex justify-content-end align-items-center">
                <div class="volume-icon" id="volumeIcon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" id="volumeSvg">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                    </svg>
                </div>
                <div class="volume-slider-container">
                    <input type="range" id="volumeSlider" class="volume-slider" min="0" max="100" value="50">
                    <div class="volume-percentage" id="volumePercentage">50%</div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="progress-container">
                <span id="currentTime">0:00</span>
                <div id="progressBar" class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <span id="totalTime">0:00</span>
            </div>
        </div>
</footer>

<script src="Componentes/configuracoes/JS/botton.js?v=<?php echo time(); ?>"></script>
</body>
</html>