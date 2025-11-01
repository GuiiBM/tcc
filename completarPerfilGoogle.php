<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado e veio do Google
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['google_incomplete'])) {
    header('Location: index.php');
    exit;
}

include "Componentes/páginas/head.php";
include "Componentes/páginas/php/DBConection.php";

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idade = intval($_POST['idade']);
    $cidade = mysqli_real_escape_string($conexao, $_POST['cidade']);
    $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
    $senha = isset($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : '';
    
    // Upload da foto (opcional, já tem do Google)
    $foto_atual = $_SESSION['usuario_foto'] ?? '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nomeArquivo = md5(uniqid()) . '.' . $extensao;
        $caminhoDestino = 'Componentes/Armazenamento/imagens/' . $nomeArquivo;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoDestino)) {
            $foto_atual = $caminhoDestino;
        }
    }
    
    // Atualizar usuário
    if ($senha) {
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_idade = ?, usuario_cidade = ?, usuario_descricao = ?, usuario_foto = ?, usuario_senha = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "issssi", $idade, $cidade, $descricao, $foto_atual, $senha, $_SESSION['usuario_id']);
    } else {
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_idade = ?, usuario_cidade = ?, usuario_descricao = ?, usuario_foto = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "isssi", $idade, $cidade, $descricao, $foto_atual, $_SESSION['usuario_id']);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        // Atualizar também o perfil de artista
        if (isset($_SESSION['artista_id'])) {
            $stmt_artista = mysqli_prepare($conexao, "UPDATE artista SET artista_cidade = ?, artista_image = ? WHERE artista_id = ?");
            mysqli_stmt_bind_param($stmt_artista, "ssi", $cidade, $foto_atual, $_SESSION['artista_id']);
            mysqli_stmt_execute($stmt_artista);
        }
        
        unset($_SESSION['google_incomplete']);
        header('Location: index.php');
        exit;
    } else {
        $erro = 'Erro ao completar perfil. Tente novamente.';
    }
}
?>

<body>

<div class="login-container">
    <?php if ($erro): ?>
        <div class="alert alert-error"><?= $erro ?></div>
    <?php endif; ?>

    <div id="registroForm">
        <h2>Complete seu Perfil</h2>
        <p style="color: #8b949e; text-align: center; margin-bottom: 20px;">
            Olá <?= $_SESSION['usuario_nome'] ?>! Complete algumas informações para finalizar seu cadastro.
        </p>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="idade">Idade:</label>
                <input type="number" id="idade" name="idade" min="13" max="120" required>
            </div>
            
            <div class="form-group">
                <label for="cidade">Cidade:</label>
                <input type="text" id="cidade" name="cidade" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha (opcional - para login sem Google):</label>
                <input type="password" id="senha" name="senha" minlength="6" placeholder="Deixe em branco para usar apenas Google">
            </div>
            
            <div class="form-group">
                <label for="foto">Foto do Perfil (opcional - já temos do Google):</label>
                <input type="file" id="foto" name="foto" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição (conte sobre suas músicas e vida como artista):</label>
                <textarea id="descricao" name="descricao" rows="4" placeholder="Conte um pouco sobre sua trajetória musical, estilo e inspirações..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Finalizar Cadastro</button>
        </form>
    </div>
</div>

<script src="Componentes/configuracoes/JS/login.js" defer></script>

</body>
</html>