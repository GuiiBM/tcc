<?php
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['musica_titulo'])) {
    include 'php/DBConection.php';
    include 'php/processarUpload.php';
    
    $artista_nome = trim($_POST['artista_nome']);
    $artista_cidade = trim($_POST['artista_cidade']);
    $musica_titulo = trim($_POST['musica_titulo']);
    
    // Processar uploads
    $resultadoCapa = processarUpload($_FILES['musica_capa'], 'imagem');
    $resultadoAudio = processarUpload($_FILES['musica_audio'], 'audio');
    
    if (isset($resultadoCapa['erro']) || isset($resultadoAudio['erro'])) {
        $erroCapa = isset($resultadoCapa['erro']) ? "Capa: " . $resultadoCapa['erro'] : '';
        $erroAudio = isset($resultadoAudio['erro']) ? "Áudio: " . $resultadoAudio['erro'] : '';
        $erro = trim($erroCapa . ' ' . $erroAudio);
        echo "<div class='alert alert-error'>Erro no upload: $erro</div>";
    } else {
        $musica_capa = $resultadoCapa['caminho'];
        $musica_link = $resultadoAudio['caminho'];
        
        $stmt = mysqli_prepare($conexao, "SELECT artista_id FROM artista WHERE artista_nome = ?");
        mysqli_stmt_bind_param($stmt, "s", $artista_nome);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $artista_id = $row['artista_id'];
        } else {
            if (isset($_FILES['artista_image']) && $_FILES['artista_image']['error'] === UPLOAD_ERR_OK) {
                $resultadoImagemArtista = processarUpload($_FILES['artista_image'], 'imagem');
                $artista_image = $resultadoImagemArtista['caminho'];
            } else {
                $artista_image = '';
            }
            
            $stmt = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $artista_nome, $artista_cidade, $artista_image);
            mysqli_stmt_execute($stmt);
            $artista_id = mysqli_insert_id($conexao);
        }
        
        $stmt = mysqli_prepare($conexao, "INSERT INTO musica (musica_titulo, musica_capa, musica_link, musica_artista) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssi", $musica_titulo, $musica_capa, $musica_link, $artista_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='alert alert-success'>Música cadastrada com sucesso!</div>";
        } else {
            error_log("Erro ao cadastrar música: " . mysqli_stmt_error($stmt));
            echo "<div class='alert alert-error'>Erro ao cadastrar música. Tente novamente.</div>";
        }
    }

}
$alerts = ob_get_clean();
?>
<div class="form-container">
    <?= $alerts ?>
    <h2 class="form-title">Cadastrar Nova Música</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="form_type" value="musica">
        <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_tipo'] !== 'admin'): 
            include_once 'php/DBConection.php';
            $stmt = mysqli_prepare($conexao, "SELECT usuario_cidade FROM usuarios WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            $usuario_cidade = $user['usuario_cidade'] ?? '';
        ?>
            <input type="hidden" name="artista_nome" value="<?= htmlspecialchars($_SESSION['usuario_nome']) ?>">
            <input type="hidden" name="artista_cidade" value="<?= htmlspecialchars($usuario_cidade) ?>">
            <div class="user-info-display">
                <p><strong>Artista:</strong> <?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
                <p><strong>Cidade:</strong> <?= htmlspecialchars($usuario_cidade ?: 'Não informada') ?></p>
            </div>
        <?php else: ?>
            <div class="form-row">
                <div class="form-col" style="position: relative;">
                    <label for="artista_nome" class="form-label">Nome do Artista</label>
                    <input type="text" class="form-control" id="artista_nome" name="artista_nome" placeholder="Digite o nome do artista" required autocomplete="off">
                    <input type="hidden" id="artista_id" name="artista_id">
                    <div id="artistaSuggestions" class="artist-suggestions"></div>
                </div>
                <div class="form-col" style="position: relative;">
                    <label for="artista_cidade" class="form-label">Cidade do Artista</label>
                    <input type="text" class="form-control" id="artista_cidade" name="artista_cidade" placeholder="Digite a cidade de origem" required autocomplete="off">
                    <div id="cidadeSuggestions" class="artist-suggestions"></div>
                    <div id="artistasPorCidade" class="artist-selection-modal" style="display: none;">
                        <div class="modal-header-custom">
                            <h5>Selecione o Artista</h5>
                            <button type="button" class="close-modal" onclick="closeArtistModal()">&times;</button>
                        </div>
                        <div class="artist-list" id="artistList"></div>
                        <div class="add-new-artist">
                            <button type="button" class="btn-add-new" onclick="addNewArtist()">+ Adicionar Novo Artista</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div id="newArtistFields" style="display: none;">
            <div class="form-col-full">
                <label for="artista_image" class="form-label">Foto do Artista</label>
                <input type="file" class="form-control" id="artista_image" name="artista_image" accept="image/*">
            </div>
        </div>
        
        <style>
        .user-info-display {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .user-info-display p {
            margin: 8px 0;
            color: #f0f6fc;
            font-size: 16px;
        }
        
        .user-info-display strong {
            color: #ffd700;
        }
        </style>
        <div class="form-col-full">
            <label for="musica_titulo" class="form-label">Título da Música</label>
            <input type="text" class="form-control" id="musica_titulo" name="musica_titulo" placeholder="Digite o título da música" required>
        </div>
        <div class="form-col-full">
            <label for="musica_capa" class="form-label">Capa da Música</label>
            <input type="file" class="form-control" id="musica_capa" name="musica_capa" accept="image/*" required>
        </div>
        <div class="form-col-full">
            <label for="musica_audio" class="form-label">Arquivo de Áudio</label>
            <input type="file" class="form-control" id="musica_audio" name="musica_audio" accept="audio/*" required>
        </div>
        <div class="form-col-full" style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn-neon">Cadastrar Música</button>
        </div>
    </form>
</div>