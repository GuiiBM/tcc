function adjustAllCards() {
    const cards = document.querySelectorAll('.grid-card');
    
    cards.forEach(card => {
        // Ajustar títulos (h2, h3)
        const titleElement = card.querySelector('.title-card h2, .title-card h3');
        if (titleElement) {
            titleElement.style.width = '97%';
            const textLength = titleElement.textContent.length;
            
            if (textLength > 20) {
                titleElement.style.fontSize = '1.1rem';
            } else if (textLength > 15) {
                titleElement.style.fontSize = '1.3rem';
            } else if (textLength > 10) {
                titleElement.style.fontSize = '1.5rem';
            } else {
                titleElement.style.fontSize = '1.7rem';
            }
        }
        
        // Ajustar subtítulos (autor-card h4)
        const authorElement = card.querySelector('.autor-card h4');
        if (authorElement) {
            authorElement.style.width = '97%';
            const textLength = authorElement.textContent.length;
            
            if (textLength > 30) {
                authorElement.style.fontSize = '0.9rem';
            } else if (textLength > 25) {
                authorElement.style.fontSize = '1rem';
            } else if (textLength > 20) {
                authorElement.style.fontSize = '1.1rem';
            } else if (textLength > 15) {
                authorElement.style.fontSize = '1.2rem';
            } else {
                authorElement.style.fontSize = '1.3rem';
            }
            
            authorElement.style.textAlign = 'center';
            authorElement.style.lineHeight = '1.3';
        }
    });
}

document.addEventListener('DOMContentLoaded', adjustAllCards);

// Reajustar quando conteúdo dinâmico for carregado
const observer = new MutationObserver(adjustAllCards);
observer.observe(document.body, { childList: true, subtree: true });