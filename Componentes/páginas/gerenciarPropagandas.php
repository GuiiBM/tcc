<?php
include_once 'php/funcoesPropaganda.php';

$baseDir = $_SERVER['DOCUMENT_ROOT'] . '/tcc/';
$uploadDir = $baseDir . 'Componentes/Armazenamento/propaganda/';
$webPath = '/tcc/Componentes/Armazenamento/propaganda/';
$message = '';

// Processar movimentação
if (isset($_POST['mover_propaganda'])) {
    $result = moverPropaganda($_POST['propaganda_id'], $_POST['direcao']);
    $message = $result['message'];
}

// Processar exclusão
if ($_POST['delete_image'] ?? false) {
    $result = deletePropaganda($_POST['delete_image'], $uploadDir);
    $message = $result['message'];
}

// Processar upload
if ($_FILES['propaganda'] ?? false) {
    $result = uploadPropaganda($_FILES['propaganda'], $uploadDir);
    $message = $result['message'];
}

$propagandas = listarPropagandasOrdenadas();
?>

<main class="main">
    <section class="principal">
        <div class="principal-content">
            <div class="page-header">
                <h1 class="page-title">Gerenciar Propagandas</h1>
                <div class="header-decoration"></div>
            </div>
            
            <?php if ($message): ?>
                <div class="message-alert">
                    <div class="alert-icon">✓</div>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="upload-section">
                <div class="section-header">
                    <h2>Nova Propaganda</h2>
                    <div class="section-line"></div>
                </div>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="file-input-wrapper">
                        <input type="file" name="propaganda" accept="image/*" required id="file-input" onchange="previewImage(this)">
                        <label for="file-input" class="file-label">
                            <span class="file-icon">📁</span>
                            <span class="file-text">Escolher Imagem</span>
                        </label>
                        <div id="image-preview" style="display: none; margin-top: 15px; text-align: center;">
                            <img id="preview-img" style="max-width: 100%; max-height: 200px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                        </div>
                    </div>
                    <button type="submit" class="btn-upload">Adicionar Propaganda</button>
                </form>
            </div>
            
            <div class="propagandas-section">
                <div class="section-header">
                    <h2>Propagandas Atuais <span class="count-badge"><?php echo count($propagandas); ?></span></h2>
                    <div class="section-line"></div>
                </div>
                <div class="images-grid">
                    <?php if (empty($propagandas)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📢</div>
                            <h3>Nenhuma propaganda cadastrada</h3>
                            <p>Adicione sua primeira propaganda usando o formulário acima</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($propagandas as $index => $propaganda): ?>
                            <div class="image-item">
                                <div class="propaganda-ordem">
                                    #<?php echo $propaganda['propaganda_ordem']; ?>
                                </div>
                                
                                <div class="image-container">
                                    <img src="<?php echo $webPath . $propaganda['propaganda_nome']; ?>" alt="Propaganda" class="propaganda-preview" onload="adjustImageOrientation(this)">
                                </div>
                                
                                <div class="ordem-buttons">
                                    <?php if ($index > 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="propaganda_id" value="<?php echo $propaganda['propaganda_id']; ?>">
                                            <input type="hidden" name="direcao" value="subir">
                                            <button type="submit" name="mover_propaganda" class="btn-ordem btn-subir" title="Subir">↑</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($index < count($propagandas) - 1): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="propaganda_id" value="<?php echo $propaganda['propaganda_id']; ?>">
                                            <input type="hidden" name="direcao" value="descer">
                                            <button type="submit" name="mover_propaganda" class="btn-ordem btn-descer" title="Descer">↓</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="action-buttons">
                                    <form method="POST" class="delete-form">
                                        <input type="hidden" name="delete_image" value="<?php echo $propaganda['propaganda_nome']; ?>">
                                        <button type="submit" onclick="return confirm('Tem certeza que deseja excluir esta propaganda?')" class="btn-delete">
                                            <span class="btn-icon">🗑️</span>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="back-section">
                <a href="admin.php" class="btn-back-admin">← Voltar ao Painel Admin</a>
            </div>
        </div>
    </section>
</main>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function adjustImageOrientation(img) {
    img.onload = function() {
        const aspectRatio = this.naturalWidth / this.naturalHeight;
        
        if (aspectRatio > 1.2) {
            this.setAttribute('data-orientation', 'horizontal');
        } else if (aspectRatio < 0.8) {
            this.setAttribute('data-orientation', 'vertical');
        } else {
            this.setAttribute('data-orientation', 'square');
        }
    };
    
    if (img.complete && img.naturalWidth > 0) {
        img.onload();
    }
}
</script>