async function loadRandomMusics() {
    const container = document.getElementById('cardContainer');
    // artistas.php e recomendados.php também usam #cardContainer, mas com listas
    // já renderizadas pelo PHP (artistas / mais visualizadas) — não sobrescrever.
    if (!container || document.querySelector('main.page-artistas')) return;
    
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

// loadRandomMusics() já verifica se #cardContainer existe e não é o da página
// de artistas/recomendados, então basta tentar em qualquer página.
document.addEventListener('DOMContentLoaded', loadRandomMusics);

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        loadRandomMusics();
    }
});