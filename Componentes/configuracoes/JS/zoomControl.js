document.addEventListener('DOMContentLoaded', function() {
    const config = {
        currentScale: 1,
        maxScale: 1.2,
        minScale: 0.5,
        step: 0.1,
        baseFontSize: 16
    };
    
    function updateZoom() {
        document.documentElement.style.fontSize = `${config.currentScale * config.baseFontSize}px`;
    }
    
    document.addEventListener('wheel', function(e) {
        if (e.ctrlKey) {
            e.preventDefault();
            
            const delta = e.deltaY < 0 ? config.step : -config.step;
            config.currentScale = Math.max(config.minScale, Math.min(config.maxScale, config.currentScale + delta));
            
            updateZoom();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                config.currentScale = Math.min(config.currentScale + config.step, config.maxScale);
                updateZoom();
            } else if (e.key === '-') {
                e.preventDefault();
                config.currentScale = Math.max(config.currentScale - config.step, config.minScale);
                updateZoom();
            } else if (e.key === '0') {
                e.preventDefault();
                config.currentScale = 1;
                updateZoom();
            }
        }
    });
});