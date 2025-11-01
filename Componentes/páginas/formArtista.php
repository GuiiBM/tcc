<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_artista'])) {
    try {
        if (file_exists('Componentes/páginas/php/processarUpload.php')) {
            include 'Componentes/páginas/php/processarUpload.php';
        }
        
        $artista_nome = trim($_POST['artista_nome']);
        $artista_cidade = trim($_POST['artista_cidade']);
        $artista_descricao = trim($_POST['artista_descricao'] ?? '');
        
        if (isset($_FILES['artista_image']) && $_FILES['artista_image']['error'] === UPLOAD_ERR_OK && function_exists('processarUpload')) {
            $resultadoUpload = processarUpload($_FILES['artista_image'], 'imagem');
            $artista_image = isset($resultadoUpload['caminho']) ? $resultadoUpload['caminho'] : 'Componentes/icones/icone.png';
        } else {
            $artista_image = 'Componentes/icones/icone.png';
        }
        
        // Se não há descrição, usar descrição do usuário logado
        if (empty($artista_descricao) && isset($_SESSION['usuario_id'])) {
            $stmt_user = mysqli_prepare($conexao, "SELECT usuario_descricao FROM usuarios WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt_user, "i", $_SESSION['usuario_id']);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);
            if ($user_data = mysqli_fetch_assoc($result_user)) {
                $artista_descricao = $user_data['usuario_descricao'] ?: "Artista talentoso de $artista_cidade. Explore suas músicas e descubra seu estilo único.";
            }
        }
        
        $stmt = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image, artista_descricao) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $artista_nome, $artista_cidade, $artista_image, $artista_descricao);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='alert alert-success'>Artista cadastrado com sucesso!</div>";
        } else {
            echo "<div class='alert alert-error'>Erro ao cadastrar artista.</div>";
        }

    } catch (Exception $e) {
        echo "<div class='alert alert-error'>Erro: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="form-container">
    <h2 class="form-title">Cadastrar Novo Artista</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="form_type" value="artista">
        <div class="form-row">
            <div class="form-col">
                <label for="artista_nome_form" class="form-label">Nome do Artista</label>
                <input type="text" class="form-control" id="artista_nome_form" name="artista_nome" placeholder="Digite o nome do artista" required>
            </div>
            <div class="form-col">
                <label for="artista_cidade_form" class="form-label">Cidade de Origem</label>
                <input type="text" class="form-control" id="artista_cidade_form" name="artista_cidade" placeholder="Digite a cidade de origem" required>
            </div>
        </div>
        <div class="form-col-full">
            <label for="artista_descricao_form" class="form-label">Descrição do Artista (opcional)</label>
            <textarea class="form-control" id="artista_descricao_form" name="artista_descricao" placeholder="Deixe em branco para usar sua descrição de perfil" rows="3"></textarea>
        </div>
        <div class="form-col-full">
            <label for="artista_image_form" class="form-label">Foto do Artista</label>
            <input type="file" class="form-control" id="artista_image_form" name="artista_image" accept="image/*" required>
        </div>
        <div class="form-col-full" style="text-align: center; margin-top: 30px;">
            <button type="submit" name="cadastrar_artista" class="btn-neon">Cadastrar Artista</button>
        </div>
    </form>
</div>