document.addEventListener('DOMContentLoaded', function() {
    function adjustArtistGrid() {
        const container = document.querySelector('.page-artistas .grid-container');
        if (!container) return;
        
        const containerWidth = container.offsetWidth;
        const cardWidth = 280;
        const gap = 20;
        const padding = 40;
        
        const availableWidth = containerWidth - padding;
        let columnsCount = Math.floor((availableWidth + gap) / (cardWidth + gap));
        
        // Reduzir uma coluna para evitar scroll horizontal
        if (columnsCount > 1) {
            columnsCount = columnsCount - 1;
        }
        
        if (columnsCount > 0) {
            container.style.gridTemplateColumns = `repeat(${columnsCount}, 1fr)`;
        }
    }
    
    if (document.querySelector('.page-artistas')) {
        adjustArtistGrid();
        window.addEventListener('resize', adjustArtistGrid);
    }
});