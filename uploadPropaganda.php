<?php
session_start();

// Verificar se é admin (adapte conforme sua lógica de autenticação)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$uploadDir = 'Componentes/Armazenamento/propaganda/';
$message = '';

// Processar exclusão de imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imageToDelete = $uploadDir . basename($_POST['delete_image']);
    if (file_exists($imageToDelete)) {
        unlink($imageToDelete);
        header('Location: uploadPropaganda.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['propaganda'])) {
    $file = $_FILES['propaganda'];
    
    // Verificar se não houve erro no upload
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($file['type'], $allowedTypes)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $message = 'Imagem de propaganda enviada com sucesso!';
            } else {
                $message = 'Erro ao salvar a imagem.';
            }
        } else {
            $message = 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.';
        }
    } else {
        $message = 'Erro no upload da imagem.';
    }
}

// Listar imagens existentes
$existingImages = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Propagandas</title>
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleGeral.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleVariables.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/stylePropaganda.css">
</head>
<body>
    <div class="container">
        <h1>Gerenciar Propagandas</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="form-container">
            <div class="form-group">
                <label for="propaganda">Selecionar Imagem de Propaganda:</label>
                <input type="file" id="propaganda" name="propaganda" accept="image/*" required>
            </div>
            <button type="submit" class="btn-submit">Enviar Propaganda</button>
        </form>
        
        <div class="existing-images">
            <h2>Propagandas Atuais</h2>
            <div class="images-grid">
                <?php foreach ($existingImages as $image): ?>
                    <div class="image-item">
                        <img src="<?php echo $image; ?>" alt="Propaganda" style="max-width: 200px; height: auto;">
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="delete_image" value="<?php echo basename($image); ?>">
                            <button type="submit" class="btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta propaganda?')">Excluir</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <a href="admin.php" class="btn-back">Voltar ao Admin</a>
    </div>
</body>
</html>

