document.addEventListener('DOMContentLoaded', function() {
    let currentScale = 1;
    const maxScale = 1.2;
    const minScale = 0.5;
    
    function updateZoom() {
        document.documentElement.style.fontSize = `${currentScale * 16}px`;
    }
    
    document.addEventListener('wheel', function(e) {
        if (e.ctrlKey) {
            e.preventDefault();
            
            if (e.deltaY < 0) {
                currentScale = Math.min(currentScale + 0.1, maxScale);
            } else {
                currentScale = Math.max(currentScale - 0.1, minScale);
            }
            
            updateZoom();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                currentScale = Math.min(currentScale + 0.1, maxScale);
                updateZoom();
            } else if (e.key === '-') {
                e.preventDefault();
                currentScale = Math.max(currentScale - 0.1, minScale);
                updateZoom();
            } else if (e.key === '0') {
                e.preventDefault();
                currentScale = 1;
                updateZoom();
            }
        }
    });
});