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
        
        <ul class="nav col-md-4 justify-content-center">
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/setas azuis/retrocederCincoSegundosEditado.png" alt="Retroceder 5 segundos" title="Retroceder 5 segundos" width="60" height="60" class="circulo circulo2 mx-2" id="rewindBtn">
            </li>
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/icone2.png" alt="Play/Pause" title="Play/Pause" width="96" height="96" class="circulo mx-2 paused" id="playBtn">
            </li>
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/setas azuis/avancaCincoSegundosEditado.png" alt="Avançar 5 segundos" title="Avançar 5 segundos" width="60" height="60" class="circulo circulo2 mx-2" id="forwardBtn">
            </li>
        </ul>
        
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

<script src="Componentes/configuracoes/JS/botton.js"></script>
<script src="Componentes/configuracoes/JS/scrollTest.js"></script>
</body>
</html>