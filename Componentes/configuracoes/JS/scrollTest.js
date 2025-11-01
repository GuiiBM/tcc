// Script de teste para verificar funcionamento dos botões de scroll
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== TESTE DE SCROLL INICIADO ===');
    
    // Verificar se o container existe
    const container = document.getElementById('ultimasMusicasContainer');
    console.log('Container ultimasMusicasContainer:', container);
    
    if (container) {
        console.log('Container encontrado! Propriedades:');
        console.log('- scrollWidth:', container.scrollWidth);
        console.log('- clientWidth:', container.clientWidth);
        console.log('- scrollLeft:', container.scrollLeft);
    }
    
    // Verificar botões
    const leftBtn = document.querySelector('[data-container="ultimasMusicasContainer"][data-direction="left"]');
    const rightBtn = document.querySelector('[data-container="ultimasMusicasContainer"][data-direction="right"]');
    
    console.log('Botão esquerdo:', leftBtn);
    console.log('Botão direito:', rightBtn);
    
    // Adicionar listeners diretos para teste
    if (leftBtn) {
        leftBtn.addEventListener('click', function(e) {
            console.log('TESTE: Botão esquerdo clicado!');
            e.preventDefault();
            if (container) {
                container.scrollBy({ left: -320, behavior: 'smooth' });
                console.log('Scroll para esquerda executado');
            }
        });
    }
    
    if (rightBtn) {
        rightBtn.addEventListener('click', function(e) {
            console.log('TESTE: Botão direito clicado!');
            e.preventDefault();
            if (container) {
                container.scrollBy({ left: 320, behavior: 'smooth' });
                console.log('Scroll para direita executado');
            }
        });
    }
});