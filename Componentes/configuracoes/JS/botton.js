// Script universal para botões de scroll
document.addEventListener('DOMContentLoaded', function() {
    // Função universal de scroll
    function scrollContainer(direction, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const scrollAmount = 300; // Valor padrão
        const scrollValue = direction === 'left' ? -scrollAmount : scrollAmount;
        
        container.scrollBy({
            left: scrollValue,
            behavior: 'smooth'
        });
    }
    
    // Adiciona event listeners para todos os botões de scroll
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('scroll-btn')) {
            const direction = e.target.dataset.direction;
            const containerId = e.target.dataset.container;
            
            if (direction && containerId) {
                scrollContainer(direction, containerId);
            }
        }
    });
});