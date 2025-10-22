class MusicPlayer {
    constructor() {
        this.audio = document.getElementById('audioPlayer');
        this.progressBar = document.getElementById('progressBar');
        this.currentTime = document.getElementById('currentTime');
        this.totalTime = document.getElementById('totalTime');
        this.playBtn = document.getElementById('playBtn');
        this.volumeSlider = document.getElementById('volumeSlider');
        this.volumePercentage = document.getElementById('volumePercentage');
        this.volumeIcon = document.getElementById('volumeIcon');
        this.volumeSvg = document.getElementById('volumeSvg');
        this.previousVolume = 50;
        this.isMuted = false;
        
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
        
        if (this.volumeSlider) {
            this.volumeSlider.addEventListener('input', (e) => this.setVolume(e.target.value));
            this.setVolume(this.volumeSlider.value);
        }
        
        if (this.volumeIcon) {
            this.volumeIcon.addEventListener('click', () => this.toggleMute());
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
    
    setVolume(value) {
        if (this.audio) {
            this.audio.volume = value / 100;
        }
        if (this.volumePercentage) {
            this.volumePercentage.textContent = `${value}%`;
        }
        
        // Atualizar cor do slider baseado no volume
        if (this.volumeSlider) {
            const percentage = (value / 100) * 100;
            this.volumeSlider.style.background = `linear-gradient(to right, #ffd700 0%, #ffd700 ${percentage}%, rgba(255, 215, 0, 0.2) ${percentage}%, rgba(255, 215, 0, 0.2) 100%)`;
        }
        
        this.updateVolumeIcon(value);
    }
    
    toggleMute() {
        if (this.isMuted) {
            // Desmuta e volta ao volume anterior
            this.isMuted = false;
            this.volumeIcon.classList.remove('muted');
            this.setVolume(this.previousVolume);
            this.volumeSlider.value = this.previousVolume;
        } else {
            // Salva volume atual e muta
            this.previousVolume = this.volumeSlider.value;
            this.isMuted = true;
            this.volumeIcon.classList.add('muted');
            this.setVolume(0);
            this.volumeSlider.value = 0;
        }
    }
    
    updateVolumeIcon(volume) {
        if (!this.volumeSvg) return;
        
        let path;
        if (this.isMuted || volume == 0) {
            // Ícone mudo
            path = "M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z";
        } else if (volume < 30) {
            // Volume baixo
            path = "M3 9v6h4l5 5V4L7 9H3z";
        } else if (volume < 70) {
            // Volume médio
            path = "M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z";
        } else {
            // Volume alto
            path = "M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z";
        }
        
        this.volumeSvg.innerHTML = `<path d="${path}"/>`;
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

function playMusic(src, title, artist, musicaId) {
    if (window.musicPlayer) {
        window.musicPlayer.loadSong(src, title, artist);
        window.musicPlayer.togglePlay();
        
        // Definir música atual para curtidas
        if (typeof setMusicaAtual === 'function' && musicaId) {
            setMusicaAtual(musicaId);
        }
    }
}