<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

include "Componentes/páginas/head.php";
include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/php/configGoogle.php";

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'login') {
            $email = mysqli_real_escape_string($conexao, $_POST['email']);
            $senha = $_POST['senha'];
            
            $stmt = mysqli_prepare($conexao, "SELECT usuario_id, usuario_senha, usuario_nome, usuario_tipo FROM usuarios WHERE usuario_email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                if (password_verify($senha, $user['usuario_senha'])) {
                    $_SESSION['usuario_id'] = $user['usuario_id'];
                    $_SESSION['usuario_nome'] = $user['usuario_nome'];
                    $_SESSION['usuario_tipo'] = $user['usuario_tipo'];
                    echo "<script>window.location.href = 'index.php';</script>";
                    exit;
                } else {
                    $erro = 'Email ou senha incorretos';
                }
            } else {
                $erro = 'Email ou senha incorretos';
            }
        } elseif ($_POST['acao'] === 'registro') {
            $email = mysqli_real_escape_string($conexao, $_POST['email']);
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
            $idade = intval($_POST['idade']);
            $cidade = mysqli_real_escape_string($conexao, $_POST['cidade']);
            $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
            
            // Upload da foto
            $foto = '';
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nomeArquivo = md5(uniqid()) . '.' . $extensao;
                $caminhoDestino = 'Componentes/Armazenamento/imagens/' . $nomeArquivo;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoDestino)) {
                    $foto = $caminhoDestino;
                }
            }
            
            $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (usuario_email, usuario_senha, usuario_nome, usuario_idade, usuario_cidade, usuario_descricao, usuario_foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssisss", $email, $senha, $nome, $idade, $cidade, $descricao, $foto);
            
            if (mysqli_stmt_execute($stmt)) {
                $sucesso = 'Conta criada com sucesso! Faça login.';
            } else {
                $erro = 'Erro ao criar conta. Email já pode estar em uso.';
            }
        }
    }
}
?>

<body>

<div class="login-container">
    <?php if ($erro): ?>
        <div class="alert alert-error"><?= $erro ?></div>
    <?php endif; ?>
    
    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= $sucesso ?></div>
    <?php endif; ?>

    <!-- Formulário de Login -->
    <div id="loginForm">
        <h2>Login</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="login">
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
        
        <div class="google-login">
            <div class="divider">
                <span>ou</span>
            </div>
            <a href="<?= getGoogleAuthUrl() ?>" class="btn btn-google">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Entrar com Google
            </a>
        </div>
        
        <div class="toggle-form">
            Ainda não tem conta? <a href="#" onclick="toggleForm()">Registre Aqui</a>
        </div>
    </div>

    <!-- Formulário de Registro -->
    <div id="registroForm" class="hidden">
        <h2>Criar Conta de Artista</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="registro">
            
            <div class="form-group">
                <label for="reg_email">Email:</label>
                <input type="email" id="reg_email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="reg_senha">Senha:</label>
                <input type="password" id="reg_senha" name="senha" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="nome">Nome do Artista:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="idade">Idade:</label>
                <input type="number" id="idade" name="idade" min="13" max="120" required>
            </div>
            
            <div class="form-group">
                <label for="cidade">Cidade:</label>
                <input type="text" id="cidade" name="cidade" required>
            </div>
            
            <div class="form-group">
                <label for="foto">Foto do Perfil:</label>
                <input type="file" id="foto" name="foto" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição (conte sobre suas músicas e vida como artista):</label>
                <textarea id="descricao" name="descricao" rows="4" placeholder="Conte um pouco sobre sua trajetória musical, estilo e inspirações..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Criar Conta</button>
        </form>
        
        <div class="toggle-form">
            Já tem conta? <a href="#" onclick="toggleForm()">Faça Login</a>
        </div>
    </div>
    
    <div class="back-button">
        <a href="index.php" class="btn-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M19 12H5"></path>
                <path d="M12 19l-7-7 7-7"></path>
            </svg>
            Voltar ao Início
        </a>
    </div>
</div>

<script src="Componentes/configuracoes/JS/login.js" defer></script>

</body>
</html>