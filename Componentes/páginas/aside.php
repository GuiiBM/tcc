<aside>
    <div class="aside-title">
        Propagandas:
    </div>
    <div class="aside-container">
        <?php
        include_once 'php/funcoesPropaganda.php';
        include_once 'php/url-helper.php';

        $webPath = getBasePath() . 'Componentes/Armazenamento/propaganda/';
        $propagandas = listarPropagandasOrdenadas();
        ?>
        <div class="propaganda-slider" id="propagandaSlider">
            <?php
            if (!empty($propagandas)) {
                foreach ($propagandas as $i => $propaganda) {
                    $activeClass = $i === 0 ? ' active' : '';
                    echo '<div class="propaganda-item' . $activeClass . '" onclick="openImagePopup(\'' . $webPath . $propaganda['propaganda_nome'] . '\')">';
                    echo '<span class="propaganda-badge">Publicidade</span>';
                    echo '<div class="propaganda-frame">';
                    echo '<img src="' . $webPath . $propaganda['propaganda_nome'] . '" alt="Propaganda" class="propaganda-img">';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p style="color: var(--text-secondary); text-align: center; font-style: italic;">Nenhuma propaganda disponível</p>';
            }
            ?>
        </div>
        <?php if (count($propagandas) > 1): ?>
        <div class="propaganda-nav">
            <button type="button" class="propaganda-nav-btn" id="propagandaPrev" aria-label="Anúncio anterior">‹</button>
            <div class="propaganda-dots" id="propagandaDots"></div>
            <button type="button" class="propaganda-nav-btn" id="propagandaNext" aria-label="Próximo anúncio">›</button>
        </div>
        <?php endif; ?>
    </div>
</aside>

<!-- Pop-up para exibir imagem em tamanho máximo -->
<div id="imagePopup" class="image-popup" onclick="closeImagePopup()">
    <div class="popup-content" onclick="event.stopPropagation()">
        <span class="close-btn" onclick="closeImagePopup()">&times;</span>
        <img id="popupImage" src="" alt="Propaganda">
    </div>
</div>

<script>
function adjustImageOrientation(img) {
    img.onload = function() {
        const aspectRatio = this.naturalWidth / this.naturalHeight;
        
        if (aspectRatio > 1.2) {
            // Imagem horizontal (landscape)
            this.setAttribute('data-orientation', 'horizontal');
        } else if (aspectRatio < 0.8) {
            // Imagem vertical (portrait)
            this.setAttribute('data-orientation', 'vertical');
        } else {
            // Imagem quadrada ou próxima do quadrado
            this.setAttribute('data-orientation', 'square');
        }
    };
    
    // Se a imagem já foi carregada
    if (img.complete && img.naturalWidth > 0) {
        img.onload();
    }
}

// Aplicar para imagens já carregadas
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.propaganda-img');
    images.forEach(function(img) {
        adjustImageOrientation(img);
    });
});

// Carrossel de propagandas: mostra uma por vez e avança sozinho,
// para todas ficarem visíveis com o tempo em vez de empilhadas.
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('#propagandaSlider .propaganda-item');
    if (!items.length) return;

    const dotsContainer = document.getElementById('propagandaDots');
    const prevBtn = document.getElementById('propagandaPrev');
    const nextBtn = document.getElementById('propagandaNext');
    const INTERVAL = 4500;
    let current = 0;
    let timer = null;

    if (dotsContainer) {
        items.forEach(function(item, i) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'propaganda-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', 'Ir para anúncio ' + (i + 1));
            dot.addEventListener('click', function() { goTo(i); });
            dotsContainer.appendChild(dot);
        });
    }
    const dots = dotsContainer ? dotsContainer.querySelectorAll('.propaganda-dot') : [];

    function render() {
        items.forEach(function(item, i) { item.classList.toggle('active', i === current); });
        dots.forEach(function(dot, i) { dot.classList.toggle('active', i === current); });
    }

    function goTo(index) {
        current = (index + items.length) % items.length;
        render();
        restartTimer();
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function restartTimer() {
        if (timer) clearInterval(timer);
        if (items.length > 1) {
            timer = setInterval(next, INTERVAL);
        }
    }

    if (prevBtn) prevBtn.addEventListener('click', prev);
    if (nextBtn) nextBtn.addEventListener('click', next);

    const slider = document.getElementById('propagandaSlider');
    slider.addEventListener('mouseenter', function() { if (timer) clearInterval(timer); });
    slider.addEventListener('mouseleave', restartTimer);

    restartTimer();
});

// Funções do pop-up
function openImagePopup(imageSrc) {
    const popup = document.getElementById('imagePopup');
    const popupImage = document.getElementById('popupImage');
    
    popupImage.src = imageSrc;
    popup.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImagePopup() {
    const popup = document.getElementById('imagePopup');
    popup.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Fechar pop-up com tecla ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImagePopup();
    }
});
</script>