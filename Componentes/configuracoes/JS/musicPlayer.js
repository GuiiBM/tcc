// Sistema de Player de Música
class MusicPlayer {
    constructor() {
        this.audio = document.getElementById('audioPlayer');
        this.progressBar = document.getElementById('progressBar');
        this.currentTime = document.getElementById('currentTime');
        this.totalTime = document.getElementById('totalTime');
        this.playBtn = document.getElementById('playBtn');
        
        this.init();
    }
    
    init() {
        if (!this.audio) return;
        
        // Event listeners
        this.audio.addEventListener('loadedmetadata', () => this.updateTotalTime());
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.onSongEnd());
        
        // Clique na barra de progresso
        this.progressBar.addEventListener('click', (e) => this.seekTo(e));
        
        // Botão play/pause
        this.playBtn.addEventListener('click', () => this.togglePlay());
    }
    
    // Carrega uma nova música
    loadSong(src, title, artist) {
        this.audio.src = src;
        document.getElementById('songTitle').textContent = title || 'Música';
        document.getElementById('songArtist').textContent = artist || 'Artista';
        this.updateTotalTime();
    }
    
    // Play/Pause
    togglePlay() {
        if (this.audio.paused) {
            this.audio.play();
            this.playBtn.classList.remove('paused');
        } else {
            this.audio.pause();
            this.playBtn.classList.add('paused');
        }
    }
    
    // Atualiza progresso da música
    updateProgress() {
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        this.progressBar.style.setProperty('--progress', `${progress}%`);
        this.currentTime.textContent = this.formatTime(this.audio.currentTime);
    }
    
    // Atualiza tempo total
    updateTotalTime() {
        this.totalTime.textContent = this.formatTime(this.audio.duration || 0);
    }
    
    // Pula para posição específica
    seekTo(e) {
        const rect = this.progressBar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
    }
    
    // Quando música termina
    onSongEnd() {
        this.playBtn.classList.add('paused');
        this.progressBar.style.setProperty('--progress', '0%');
    }
    
    // Formata tempo em MM:SS
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}

// Inicializa o player quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    window.musicPlayer = new MusicPlayer();
});

// Função global para tocar música dos cards
function playMusic(src, title, artist) {
    if (window.musicPlayer) {
        window.musicPlayer.loadSong(src, title, artist);
        window.musicPlayer.togglePlay();
    }
}