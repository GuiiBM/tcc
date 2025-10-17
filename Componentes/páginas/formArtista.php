<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_artista'])) {
    $artista_nome = mysqli_real_escape_string($conexao, $_POST['artista_nome']);
    $artista_pais = mysqli_real_escape_string($conexao, $_POST['artista_pais']);
    $artista_image = mysqli_real_escape_string($conexao, $_POST['artista_image']);
    
    $sql_artista = "INSERT INTO artista (artista_nome, artista_pais, artista_image) 
                    VALUES ('$artista_nome', '$artista_pais', '$artista_image')";
    
    if (mysqli_query($conexao, $sql_artista)) {
        echo "<div class='alert alert-success'>Artista cadastrado com sucesso!</div>";
    } else {
        echo "<div class='alert alert-error'>Erro: " . mysqli_error($conexao) . "</div>";
    }
    echo "<script>setTimeout(function() { showForm('artista'); }, 100);</script>";
}
?>

<div class="form-container">
    <h2 class="form-title">Cadastrar Novo Artista</h2>
    <form method="POST">
        <input type="hidden" name="form_type" value="artista">
        <div class="form-row">
            <div class="form-col">
                <label for="artista_nome_form" class="form-label">Nome do Artista</label>
                <input type="text" class="form-control" id="artista_nome_form" name="artista_nome" placeholder="Digite o nome do artista" required>
            </div>
            <div class="form-col">
                <label for="artista_pais_form" class="form-label">País de Origem</label>
                <input type="text" class="form-control" id="artista_pais_form" name="artista_pais" placeholder="Digite o país de origem" required>
            </div>
        </div>
        <div class="form-col-full">
            <label for="artista_image_form" class="form-label">URL da Foto</label>
            <input type="url" class="form-control" id="artista_image_form" name="artista_image" placeholder="https://exemplo.com/foto.jpg" required>
        </div>
        <div class="form-col-full" style="text-align: center; margin-top: 30px;">
            <button type="submit" name="cadastrar_artista" class="btn-neon">Cadastrar Artista</button>
        </div>
    </form>
</div>