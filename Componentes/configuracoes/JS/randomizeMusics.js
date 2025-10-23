async function loadRandomMusics() {
    const container = document.getElementById('cardContainer');
    if (!container || window.location.pathname.includes('artistas.php')) return;
    
    try {
        const response = await fetch('Componentes/páginas/php/buscarMusicasAleatorias.php');
        const musicas = await response.json();
        
        container.innerHTML = '';
        
        musicas.forEach(musica => {
            const card = document.createElement('div');
            card.className = 'grid-card';
            card.onclick = () => playMusic(musica.musica_link, musica.musica_titulo, musica.artista_nome, musica.musica_id);
            
            card.innerHTML = `
                <div class='title-card'>
                    <h3>${musica.musica_titulo}</h3>
                </div>
                <img src='${musica.musica_capa}' alt='${musica.musica_titulo}' class='image-music-card'>
                <div class='autor-card'>
                    <h4>${musica.artista_nome} - ${musica.artista_cidade}</h4>
                </div>
            `;
            
            container.appendChild(card);
        });
        
        // Aplicar ajustes após criar os cards
        adjustAllCards();
    } catch (error) {
        console.error('Erro ao carregar músicas aleatórias:', error);
    }
}

// Apenas executar na página principal
if (window.location.pathname.includes('index.php') || window.location.pathname.endsWith('/')) {
    document.addEventListener('DOMContentLoaded', loadRandomMusics);
    
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadRandomMusics();
        }
    });
}