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
        
        this.audio.addEventListener('loadedmetadata', () => this.updateTotalTime());
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.onSongEnd());
        
        if (this.progressBar) {
            this.progressBar.addEventListener('click', (e) => this.seekTo(e));
        }
        
        if (this.playBtn) {
            this.playBtn.addEventListener('click', () => this.togglePlay());
        }
    }
    
    loadSong(src, title, artist) {
        if (!this.audio) return;
        
        this.audio.src = src;
        const songTitle = document.getElementById('songTitle');
        const songArtist = document.getElementById('songArtist');
        
        if (songTitle) songTitle.textContent = title || 'Música';
        if (songArtist) songArtist.textContent = artist || 'Artista';
        
        this.updateTotalTime();
    }
    
    togglePlay() {
        if (this.audio.paused) {
            this.audio.play();
            this.playBtn.classList.remove('paused');
        } else {
            this.audio.pause();
            this.playBtn.classList.add('paused');
        }
    }
    
    updateProgress() {
        if (!this.audio || !this.audio.duration) return;
        
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        
        if (this.progressBar) {
            this.progressBar.style.setProperty('--progress', `${progress}%`);
        }
        
        if (this.currentTime) {
            this.currentTime.textContent = this.formatTime(this.audio.currentTime);
        }
    }
    
    updateTotalTime() {
        if (this.totalTime && this.audio) {
            this.totalTime.textContent = this.formatTime(this.audio.duration || 0);
        }
    }
    
    seekTo(e) {
        const rect = this.progressBar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
    }
    
    onSongEnd() {
        this.playBtn.classList.add('paused');
        this.progressBar.style.setProperty('--progress', '0%');
    }
    
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    window.musicPlayer = new MusicPlayer();
});

function playMusic(src, title, artist) {
    if (window.musicPlayer) {
        window.musicPlayer.loadSong(src, title, artist);
        window.musicPlayer.togglePlay();
    }
}