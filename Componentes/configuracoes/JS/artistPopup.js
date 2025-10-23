class ArtistPopup {
    constructor() {
        this.createPopupHTML();
        this.bindEvents();
    }
    
    createPopupHTML() {
        const popupHTML = `
            <div id="artistPopup" class="artist-popup">
                <div class="popup-content">
                    <div class="popup-header">
                        <h2 id="artistName"></h2>
                        <button class="close-btn" id="closePopup">&times;</button>
                    </div>
                    <div class="popup-body">
                        <div class="artist-info">
                            <img id="artistImage" src="" alt="" class="artist-popup-image">
                            <div class="artist-details">
                                <p id="artistCity"></p>
                                <a id="artistLink" href="#" target="_blank" class="artist-link">Página do Artista</a>
                            </div>
                        </div>
                        <div class="songs-section">
                            <h3>Músicas do Artista</h3>
                            <div id="songsList" class="songs-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', popupHTML);
    }
    
    bindEvents() {
        const popup = document.getElementById('artistPopup');
        const closeBtn = document.getElementById('closePopup');
        
        closeBtn.addEventListener('click', () => this.closePopup());
        popup.addEventListener('click', (e) => {
            if (e.target === popup) this.closePopup();
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closePopup();
        });
    }
    
    async openPopup(artistName) {
        console.log('Abrindo popup para:', artistName);
        
        // Mostrar popup imediatamente com dados básicos
        document.getElementById('artistName').textContent = artistName;
        document.getElementById('artistPopup').classList.add('show');
        document.body.style.overflow = 'hidden';
        
        try {
            const response = await fetch('Componentes/páginas/php/getArtistData.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `artist=${encodeURIComponent(artistName)}`
            });
            
            const data = await response.json();
            console.log('Dados recebidos:', data);
            
            if (data.success) {
                this.populatePopup(data.artist, data.songs);
                console.log('Debug:', data.debug);
            } else {
                console.log('Erro:', data.message);
                document.getElementById('songsList').innerHTML = '<p>Nenhuma música encontrada</p>';
            }
        } catch (error) {
            console.error('Erro ao carregar dados do artista:', error);
            document.getElementById('songsList').innerHTML = '<p>Erro ao carregar músicas</p>';
        }
    }
    
    populatePopup(artist, songs) {
        document.getElementById('artistName').textContent = artist.nome;
        document.getElementById('artistImage').src = artist.imagem || 'https://via.placeholder.com/150x150?text=Sem+Foto';
        document.getElementById('artistCity').textContent = artist.cidade || 'Cidade não informada';
        document.getElementById('artistLink').href = artist.link || '#';
        
        const songsList = document.getElementById('songsList');
        songsList.innerHTML = '';
        
        songs.forEach(song => {
            const songElement = document.createElement('div');
            songElement.className = 'song-item';
            const isArtistPage = document.querySelector('.page-artistas');
            const buttonAction = isArtistPage ? 
                `this.closest('.song-item').querySelector('form').submit();` : 
                `playMusic('${song.audio}', '${song.titulo}', '${artist.nome}', ${song.id}); window.artistPopup.closePopup();`;
            
            songElement.innerHTML = `
                <img src="${song.imagem || 'https://via.placeholder.com/60x60?text=♪'}" alt="${song.titulo}" class="song-thumb">
                <div class="song-info">
                    <h4>${song.titulo}</h4>
                    <p class="song-duration" data-audio="${song.audioPath}">Carregando...</p>
                </div>
                ${isArtistPage ? `
                    <form method="POST" action="index.php" style="display: inline;">
                        <input type="hidden" name="audio" value="${song.audio}">
                        <input type="hidden" name="titulo" value="${song.titulo}">
                        <input type="hidden" name="artista" value="${artist.nome}">
                        <input type="hidden" name="id" value="${song.id}">
                    </form>
                ` : ''}
                <button class="play-song-btn" onclick="${buttonAction}">
                    ▶️
                </button>
            `;
            songsList.appendChild(songElement);
            
            // Calcular duração do áudio
            this.loadAudioDuration(song.audioPath, songElement.querySelector('.song-duration'));
        });
    }
    
    loadAudioDuration(audioPath, durationElement) {
        const audio = new Audio(audioPath);
        audio.addEventListener('loadedmetadata', () => {
            const duration = audio.duration;
            if (duration && !isNaN(duration)) {
                const minutes = Math.floor(duration / 60);
                const seconds = Math.floor(duration % 60);
                durationElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            } else {
                durationElement.textContent = '0:00';
            }
        });
        audio.addEventListener('error', () => {
            durationElement.textContent = '0:00';
        });
    }
    
    closePopup() {
        document.getElementById('artistPopup').classList.remove('show');
        document.body.style.overflow = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    window.artistPopup = new ArtistPopup();
    
    // Seletores específicos para artistas
    const artistSelectors = [
        '.page-artistas .grid-card',  // artistas.php
        '#artistasContainer .grid-card'  // principal.php - novos artistas
    ];
    
    artistSelectors.forEach(selector => {
        const cards = document.querySelectorAll(selector);
        cards.forEach(card => {
            card.addEventListener('click', function() {
                const h3Element = this.querySelector('h3');
                const h2Element = this.querySelector('h2');
                
                if (h3Element) {
                    const artistName = h3Element.textContent;
                    console.log('Clicou no artista:', artistName);
                    window.artistPopup.openPopup(artistName);
                } else if (h2Element) {
                    const artistName = h2Element.textContent;
                    console.log('Clicou no artista:', artistName);
                    window.artistPopup.openPopup(artistName);
                }
            });
        });
    });
});