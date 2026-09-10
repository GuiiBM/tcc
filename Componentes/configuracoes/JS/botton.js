document.addEventListener('DOMContentLoaded', function() {
    // Função para fazer scroll no container, sempre por exatamente 1 card + gap
    // (medido em tempo real, para não desalinhar do scroll-snap em nenhum breakpoint)
    function scrollContainer(direction, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const card = container.querySelector(':scope > *');
        if (!card) return;

        const gap = parseFloat(window.getComputedStyle(container).columnGap) || 0;
        const scrollAmount = card.getBoundingClientRect().width + gap;
        const scrollValue = direction === 'left' ? -scrollAmount : scrollAmount;

        container.scrollBy({
            left: scrollValue,
            behavior: 'smooth'
        });
    }
    
    // Listener delegado no document: cobre também botões adicionados
    // dinamicamente depois do carregamento inicial da página.
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
});