document.addEventListener('DOMContentLoaded', function() {
    function scrollContainer(direction, containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.log('Container não encontrado:', containerId);
            return;
        }
        
        const scrollAmount = 320;
        const scrollValue = direction === 'left' ? -scrollAmount : scrollAmount;
        
        container.scrollBy({
            left: scrollValue,
            behavior: 'smooth'
        });
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('scroll-btn')) {
            e.preventDefault();
            const direction = e.target.dataset.direction;
            const containerId = e.target.dataset.container;
            
            if (direction && containerId) {
                scrollContainer(direction, containerId);
            }
        }
    });
});