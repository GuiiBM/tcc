<?php
require_once __DIR__ . '/php/seguranca.php';
iniciarSessaoSegura();
include_once 'php/verificar_login.php';
redirecionarSeNaoAdmin();
include_once 'php/funcoesPropaganda.php';
include_once 'php/url-helper.php';

$uploadDir = getArmazenamentoPath('propaganda');
$webPath = getBasePath() . 'Componentes/Armazenamento/propaganda/';
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

                <div class="size-guide">
                    <div class="size-guide-text">
                        <h3>Tamanho ideal</h3>
                        <p>
                            As propagandas aparecem num quadro <strong>retrato 4:5</strong> na barra lateral
                            (computador) e no início (celular), trocando sozinhas a cada 7 segundos.
                        </p>
                        <p>
                            Tamanho ideal: <strong>1080 × 1350 px</strong> (o mesmo de um post retrato do Instagram).
                            A arte aparece <strong>inteira</strong>, sem cortes; se tiver outra proporção, as sobras
                            são preenchidas com a própria imagem desfocada.
                        </p>
                        <p>A pré-visualização ao lado mostra exatamente como a imagem escolhida vai aparecer na sua tela agora.</p>
                    </div>
                    <div class="size-guide-preview">
                        <span class="size-guide-label">Pré-visualização real</span>
                        <div class="propaganda-frame size-guide-frame" id="sizeGuideFrame">
                            <img id="sizeGuideImg" class="propaganda-img" style="display: none;">
                            <div class="size-guide-placeholder" id="sizeGuidePlaceholder">Escolha uma imagem para ver o recorte aqui</div>
                        </div>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="file-input-wrapper">
                        <input type="file" name="propaganda" accept="image/*" required id="file-input" onchange="previewImage(this)">
                        <label for="file-input" class="file-label">
                            <span class="file-icon">📁</span>
                            <span class="file-text">Escolher Imagem</span>
                        </label>
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
                                    <img src="<?php echo $webPath . $propaganda['propaganda_nome']; ?>" alt="Propaganda" class="propaganda-preview">
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
    const guideImg = document.getElementById('sizeGuideImg');
    const placeholder = document.getElementById('sizeGuidePlaceholder');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            guideImg.src = e.target.result;
            guideImg.style.display = 'block';
            placeholder.style.display = 'none';
            adjustImageOrientation(guideImg);
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

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.propaganda-preview').forEach(adjustImageOrientation);
});
</script>