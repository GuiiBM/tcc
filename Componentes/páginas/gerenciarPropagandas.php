<?php
include_once 'php/funcoesPropaganda.php';

$baseDir = $_SERVER['DOCUMENT_ROOT'] . '/tcc/';
$uploadDir = $baseDir . 'Componentes/Armazenamento/propaganda/';
$webPath = '/tcc/Componentes/Armazenamento/propaganda/';
$message = '';

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

$existingImages = listarPropagandas($uploadDir);
?>

<main class="main">
    <section class="principal">
        <div class="principal-content">
            <h1 style="color: var(--neon-white); text-align: center; margin-bottom: 40px;">Gerenciar Propagandas</h1>
            
            <?php if ($message): ?>
                <div style="background: var(--accent-gold-light); color: var(--bg-primary); padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" style="background: var(--bg-secondary); padding: 30px; border-radius: 12px; margin: 20px 0;">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--text-white);">Adicionar Nova Propaganda:</label>
                    <input type="file" name="propaganda" accept="image/*" required style="width: 100%; padding: 12px; border: 2px solid var(--border-secondary); border-radius: 8px; background: var(--bg-primary); color: var(--text-primary);">
                </div>
                <button type="submit" class="btn-neon">Adicionar Propaganda</button>
            </form>
            
            <div style="margin-top: 40px;">
                <h2 style="color: var(--text-white); margin-bottom: 20px;">Propagandas Atuais (<?php echo count($existingImages); ?>)</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                    <?php if (empty($existingImages)): ?>
                        <p style="color: var(--text-secondary); text-align: center; grid-column: 1 / -1;">Nenhuma propaganda cadastrada ainda.</p>
                    <?php else: ?>
                        <?php foreach ($existingImages as $image): ?>
                            <?php $imageName = basename($image); ?>
                            <div style="background: var(--bg-secondary); padding: 15px; border-radius: 12px; text-align: center;">
                                <img src="<?php echo $webPath . $imageName; ?>" alt="Propaganda" style="max-width: 100%; height: 150px; object-fit: cover; border-radius: 8px;">
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="delete_image" value="<?php echo $imageName; ?>">
                                    <button type="submit" onclick="return confirm('Excluir esta propaganda?')" style="background: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">Excluir</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="admin.php" class="btn-neon">Voltar ao Painel Admin</a>
            </div>
        </div>
    </section>
</main>