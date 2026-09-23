<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
iniciarSessaoSegura();

// Verificar se usuário está logado e veio do Google
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['google_incomplete'])) {
    header('Location: index.php');
    exit;
}

include "Componentes/paginas/head.php";
include "Componentes/paginas/php/DBConection.php";

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idade = intval($_POST['idade']);
    $cidade = trim($_POST['cidade'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $senha = !empty($_POST['senha']) ? hashSenha($_POST['senha']) : '';
    
    // Upload da foto (opcional, já tem do Google)
    $foto_atual = $_SESSION['usuario_foto'] ?? '';
    // Só imagens: extensão e tipo real validados (antes aceitava qualquer
    // extensão, o que permitiria enviar um .php e executá-lo no servidor).
    try {
        $foto_atual = salvarUploadValidado($_FILES['foto'] ?? null, 'imagem') ?: $foto_atual;
    } catch (Exception $e) {
        $erro = 'Foto: ' . $e->getMessage();
    }
    
    // Atualizar usuário
    if ($senha) {
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_idade = ?, usuario_cidade = ?, usuario_descricao = ?, usuario_foto = ?, usuario_senha = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "issssi", $idade, $cidade, $descricao, $foto_atual, $senha, $_SESSION['usuario_id']);
    } else {
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_idade = ?, usuario_cidade = ?, usuario_descricao = ?, usuario_foto = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "isssi", $idade, $cidade, $descricao, $foto_atual, $_SESSION['usuario_id']);
    }
    
    if (!$erro && mysqli_stmt_execute($stmt)) {
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
        <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div id="registroForm">
        <h2>Complete seu Perfil</h2>
        <p style="color: #8b949e; text-align: center; margin-bottom: 20px;">
            Olá <?= htmlspecialchars($_SESSION['usuario_nome']) ?>! Complete algumas informações para finalizar seu cadastro.
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