document.addEventListener('DOMContentLoaded', function() {
    // Função para fazer scroll no container
    function scrollContainer(direction, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const scrollAmount = 305; // 275px (card) + 30px (gap)
        const scrollValue = direction === 'left' ? -scrollAmount : scrollAmount;
        
        container.scrollBy({
            left: scrollValue,
            behavior: 'smooth'
        });
    }
    
    // Event listener principal
    document.addEventListener('click', function(e) {
        if (e.target.matches('.scroll-btn')) {
            e.preventDefault();
            const direction = e.target.getAttribute('data-direction');
            const containerId = e.target.getAttribute('data-container');
            
            if (direction && containerId) {
                scrollContainer(direction, containerId);
            }
        }
    });
    
    // Backup: listeners diretos nos botões
    setTimeout(() => {
        document.querySelectorAll('.scroll-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const direction = this.getAttribute('data-direction');
                const containerId = this.getAttribute('data-container');
                
                if (direction && containerId) {
                    scrollContainer(direction, containerId);
                }
            });
        });
    }, 500);
});