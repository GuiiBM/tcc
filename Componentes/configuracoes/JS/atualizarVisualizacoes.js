function atualizarMusicasVisualizadas() {
    fetch('Componentes/páginas/php/buscar_musicas_visualizadas.php')
        .then(response => response.text())
        .then(data => {
            const container = document.getElementById('maisVisualizadasContainer');
            if (container) {
                container.innerHTML = data;
            }
        })
        .catch(error => console.log('Erro ao atualizar:', error));
}

// Atualizar a cada 5 segundos, só nas páginas que têm o container
if (document.getElementById('maisVisualizadasContainer')) {
    setInterval(atualizarMusicasVisualizadas, 5000);
}