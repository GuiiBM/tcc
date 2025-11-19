// ===== MICROINTERAÇÕES REFINADAS =====

document.addEventListener('DOMContentLoaded', function() {
    
    // Efeito de ripple nos botões
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;
        
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
        circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
        circle.classList.add('ripple');
        
        const ripple = button.getElementsByClassName('ripple')[0];
        if (ripple) {
            ripple.remove();
        }
        
        button.appendChild(circle);
    }
    
    // Aplicar efeito ripple em botões
    const buttons = document.querySelectorAll('.btn, button, .scroll-btn, .circulo');
    buttons.forEach(button => {
        button.addEventListener('click', createRipple);
    });
    
    // Animação suave de entrada para cards
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const cardObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observar cards para animação de entrada
    const cards = document.querySelectorAll('.grid-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        cardObserver.observe(card);
    });
    
    // Feedback tátil melhorado para controles do player
    const playerControls = document.querySelectorAll('#playBtn, #rewindBtn, #forwardBtn');
    playerControls.forEach(control => {
        control.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        control.addEventListener('mouseup', function() {
            this.style.transform = 'scale(1.1)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
        
        control.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    // Animação suave para barra de progresso
    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
        progressBar.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const percent = ((e.clientX - rect.left) / rect.width) * 100;
            this.style.setProperty('--hover-position', `${percent}%`);
        });
    }
    
    // Efeito de hover melhorado para links de navegação
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    // Animação de loading para imagens
    const images = document.querySelectorAll('.image-music-card');
    images.forEach(img => {
        if (!img.complete) {
            img.classList.add('loading');
            img.addEventListener('load', function() {
                this.classList.remove('loading');
            });
        }
    });
    
    // Scroll suave melhorado para containers
    const scrollContainers = document.querySelectorAll('.grid-container');
    scrollContainers.forEach(container => {
        let isScrolling = false;
        
        container.addEventListener('scroll', function() {
            if (!isScrolling) {
                window.requestAnimationFrame(() => {
                    // Adicionar classe durante scroll
                    this.classList.add('scrolling');
                    
                    clearTimeout(this.scrollTimeout);
                    this.scrollTimeout = setTimeout(() => {
                        this.classList.remove('scrolling');
                    }, 150);
                    
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });
    });
    
    // Feedback visual para botões de curtida
    const likeBtn = document.getElementById('likeBtn');
    const dislikeBtn = document.getElementById('dislikeBtn');
    
    if (likeBtn) {
        likeBtn.addEventListener('click', function() {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 300);
        });
    }
    
    if (dislikeBtn) {
        dislikeBtn.addEventListener('click', function() {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 300);
        });
    }
    
    // Animação de entrada para títulos de seção
    const sectionTitles = document.querySelectorAll('.scroll-container h2');
    const titleObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateX(0)';
            }
        });
    }, observerOptions);
    
    sectionTitles.forEach(title => {
        title.style.opacity = '0';
        title.style.transform = 'translateX(-20px)';
        title.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        titleObserver.observe(title);
    });
    
});

// CSS para efeito ripple
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(108, 92, 231, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .clicked {
        animation: pulse 0.3s ease;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .scrolling {
        scroll-behavior: smooth;
    }
    
    .grid-container.scrolling .grid-card {
        transition: transform 0.2s ease;
    }
`;

document.head.appendChild(rippleStyle);