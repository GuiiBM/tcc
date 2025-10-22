<footer class="d-flex flex-wrap justify-content-between align-items-center py-3 mt-0 border-top">
        <audio id="audioPlayer" preload="metadata"></audio>
        
        <div class="music-info col-md-4">
            <div id="songTitle">Selecione uma música</div>
            <div id="songArtist">Artista</div>
        </div>
        
        <ul class="nav col-md-4 justify-content-center">
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/setas azuis/retrocederMusicaEditado.png" alt="Ícone" width="60" height="60" class="circulo circulo2 mx-2">
            </li>
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/setas azuis/retrocederCincoSegundosEditado.png" alt="Ícone" width="60" height="60" class="circulo circulo2 mx-2">
            </li>
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/icone2.png" alt="Ícone" width="96" height="96" class="circulo mx-2 paused" id="playBtn">
            </li>
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/setas azuis/avancaCincoSegundosEditado.png" alt="Ícone" width="60" height="60" class="circulo circulo2 mx-2">
            </li>
            <li class="nav-item d-flex align-items-center">
                <img src="Componentes/icones/setas azuis/avancarMusicaEditado.png" alt="Ícone" width="60" height="60" class="circulo circulo2 mx-2">
            </li>
        </ul>
        
        <div class="volume-control col-md-4 d-flex justify-content-end align-items-center">
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
    </footer>
</div>
</body>
</html>