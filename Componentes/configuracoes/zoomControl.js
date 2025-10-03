document.addEventListener('DOMContentLoaded', function() {
    let scale = 1;
    const maxScale = 1;
    const minScale = 1;
    
    document.addEventListener('wheel', function(e) {
        if (e.ctrlKey) {
            e.preventDefault();
            
            if (e.deltaY < 0 && scale < maxScale) {
                scale += 0.1;
            } else if (e.deltaY > 0 && scale > minScale) {
                scale -= 0.1;
            }
            
            scale = Math.min(maxScale, Math.max(minScale, scale));
            document.body.style.transform = `scale(${scale})`;
            document.body.style.transformOrigin = '0 0';
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && (e.key === '+' || e.key === '=' || e.key === '-' || e.key === '0')) {
            e.preventDefault();
            
            if (e.key === '+' || e.key === '=') {
                if (scale < maxScale) scale += 0.1;
            } else if (e.key === '-') {
                if (scale > minScale) scale -= 0.1;
            } else if (e.key === '0') {
                scale = 1;
            }
            
            scale = Math.min(maxScale, Math.max(minScale, scale));
            document.body.style.transform = `scale(${scale})`;
            document.body.style.transformOrigin = '0 0';
        }
    });
});