<aside>
    <div class="aside-title">
        Propagandas:
    </div>
    <div class="aside-container">
        <div class="propaganda-images">
            <?php
            include_once 'php/funcoesPropaganda.php';
            
            $webPath = '/tcc/Componentes/Armazenamento/propaganda/';
            $propagandas = listarPropagandasOrdenadas();
            
            if (!empty($propagandas)) {
                foreach ($propagandas as $propaganda) {
                    echo '<div class="propaganda-item" onclick="openImagePopup(\'' . $webPath . $propaganda['propaganda_nome'] . '\')">';
                    echo '<img src="' . $webPath . $propaganda['propaganda_nome'] . '" alt="Propaganda" class="propaganda-img" onload="adjustImageOrientation(this)">';
                    echo '</div>';
                }
            } else {
                echo '<p style="color: var(--text-secondary); text-align: center; font-style: italic;">Nenhuma propaganda disponível</p>';
            }
            ?>
        </div>
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