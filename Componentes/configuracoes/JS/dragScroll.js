// Permite arrastar as trilhas horizontais (carrosséis) com o mouse,
// além do toque, já que overflow-x:auto sozinho só aceita arraste de
// mouse pela scrollbar, não pelo conteúdo.
document.addEventListener('DOMContentLoaded', function () {
    const candidates = document.querySelectorAll('.grid-container');
    // Só conta como arraste (e cancela o click) depois de mover mais que
    // isso — um clique de verdade sempre treme alguns pixels entre o
    // mousedown e o mouseup, e não pode ser confundido com arraste.
    const DRAG_THRESHOLD = 10;

    candidates.forEach(function (container) {
        if (container.scrollWidth <= container.clientWidth) return;
        container.classList.add('drag-scrollable');

        // Impede o navegador de iniciar o arraste nativo de imagem
        // (o "fantasma" da imagem seguindo o cursor), que sequestra o
        // gesto e nunca deixa o scroll customizado nem o click acontecer.
        container.querySelectorAll('img').forEach(function (img) {
            img.draggable = false;
        });
        container.addEventListener('dragstart', function (e) { e.preventDefault(); });

        let isDown = false;
        let dragged = false;
        let startX = 0;
        let startScrollLeft = 0;

        function pointerX(e) {
            return e.touches ? e.touches[0].pageX : e.pageX;
        }

        function start(e) {
            isDown = true;
            dragged = false;
            startX = pointerX(e);
            startScrollLeft = container.scrollLeft;
        }

        function move(e) {
            if (!isDown) return;
            const delta = pointerX(e) - startX;
            if (Math.abs(delta) > DRAG_THRESHOLD) {
                if (!dragged) container.classList.add('is-dragging');
                dragged = true;
            }
            if (!dragged) return;
            if (!e.touches) e.preventDefault();
            container.scrollLeft = startScrollLeft - delta;
        }

        function end() {
            isDown = false;
            container.classList.remove('is-dragging');
        }

        container.addEventListener('mousedown', start);
        document.addEventListener('mousemove', move);
        document.addEventListener('mouseup', end);
        container.addEventListener('mouseleave', end);

        container.addEventListener('touchstart', start, { passive: true });
        container.addEventListener('touchmove', move, { passive: true });
        container.addEventListener('touchend', end);

        // Depois de um arraste de verdade, engole o click seguinte para
        // não disparar o playMusic()/abrir popup do card ao soltar.
        container.addEventListener('click', function (e) {
            if (dragged) {
                e.stopPropagation();
                e.preventDefault();
                dragged = false;
            }
        }, true);
    });
});
