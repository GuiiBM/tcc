<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_artista'])) {
    include 'php/DBConection.php';
    include 'php/processarUpload.php';
    
    $artista_nome = trim($_POST['artista_nome']);
    $artista_cidade = trim($_POST['artista_cidade']);
    
    // Processar upload da imagem
    $resultadoUpload = processarUpload($_FILES['artista_image'], 'imagem');
    
    if (isset($resultadoUpload['erro'])) {
        echo "<div class='alert alert-error'>Erro no upload da imagem: " . $resultadoUpload['erro'] . "</div>";
    } else {
        $artista_image = $resultadoUpload['caminho'];
        
        $stmt = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $artista_nome, $artista_cidade, $artista_image);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='alert alert-success'>Artista cadastrado com sucesso!</div>";
        } else {
            error_log("Erro ao cadastrar artista: " . mysqli_stmt_error($stmt));
            echo "<div class='alert alert-error'>Erro ao cadastrar artista. Tente novamente.</div>";
        }
    }
    echo "<script>setTimeout(function() { showForm('artista'); }, 100);</script>";
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
            <label for="artista_image_form" class="form-label">Foto do Artista</label>
            <input type="file" class="form-control" id="artista_image_form" name="artista_image" accept="image/*" required>
        </div>
        <div class="form-col-full" style="text-align: center; margin-top: 30px;">
            <button type="submit" name="cadastrar_artista" class="btn-neon">Cadastrar Artista</button>
        </div>
    </form>
</div>