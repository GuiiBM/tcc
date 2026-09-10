// ===== MICROINTERAÇÕES =====

document.addEventListener('DOMContentLoaded', function() {

    // Loading discreto para imagens ainda não carregadas
    const images = document.querySelectorAll('.image-music-card');
    images.forEach(img => {
        if (!img.complete) {
            img.classList.add('loading');
            img.addEventListener('load', function() {
                this.classList.remove('loading');
            });
        }
    });

    // Feedback visual para botões de curtida
    const likeBtn = document.getElementById('likeBtn');
    const dislikeBtn = document.getElementById('dislikeBtn');

    if (likeBtn) {
        likeBtn.addEventListener('click', function() {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 250);
        });
    }

    if (dislikeBtn) {
        dislikeBtn.addEventListener('click', function() {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 250);
        });
    }

});
