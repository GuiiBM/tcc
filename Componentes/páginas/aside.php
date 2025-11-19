<aside>
    <div class="aside-title">
        Propagandas:
    </div>
    <div class="aside-container">
        <div class="propaganda-images">
            <?php
            $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/tcc/';
            $propagandaDir = $baseDir . 'Componentes/Armazenamento/propaganda/';
            $webPath = '/tcc/Componentes/Armazenamento/propaganda/';
            
            if (is_dir($propagandaDir)) {
                $images = glob($propagandaDir . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
                if (!empty($images)) {
                    foreach ($images as $image) {
                        $imageName = basename($image);
                        echo '<div class="propaganda-item">';
                        echo '<img src="' . $webPath . $imageName . '" alt="Propaganda" class="propaganda-img">';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="color: var(--text-secondary); text-align: center; font-style: italic;">Nenhuma propaganda disponível</p>';
                }
            }
            ?>
        </div>
    </div>
</aside>