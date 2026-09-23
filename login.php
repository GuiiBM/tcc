<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
// Login e cadastro: e-mail/senha e login social (Google, Facebook, Apple).
iniciarSessaoSegura();

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$tituloPagina = 'Entrar';
$paginaId = 'login';
include "Componentes/paginas/head.php";
include_once "Componentes/paginas/php/loginSocial.php";

$erro = '';
$sucesso = '';
$modo = ($_GET['modo'] ?? '') === 'registro' ? 'registro' : 'login';

$mensagensErro = [
    'cancelado' => 'Login cancelado.',
    'google_auth_failed' => 'Não foi possível entrar com o Google. Tente novamente.',
    'google_state_invalido' => 'A sessão de login expirou. Tente novamente.',
    'state_invalido' => 'A sessão de login expirou. Tente novamente.',
    'google_token_failed' => 'O Google não confirmou o login. Tente novamente.',
    'google_user_failed' => 'Não foi possível ler os dados da sua conta Google.',
    'facebook_failed' => 'Não foi possível entrar com o Facebook. Tente novamente.',
    'apple_failed' => 'Não foi possível entrar com a Apple. Tente novamente.',
    'sem_email' => 'Sua conta não informou um e-mail. Use outra forma de login.',
    'provedor_indisponivel' => 'Esse tipo de login não está disponível.',
    'registro_failed' => 'Não foi possível criar sua conta.',
    'artista_creation_failed' => 'Não foi possível criar seu perfil de artista.',
];
if (isset($_GET['erro'])) {
    $erro = $mensagensErro[$_GET['erro']] ?? 'Não foi possível entrar. Tente novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'login') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = $_POST['senha'] ?? '';

        // Força bruta: no máximo 5 senhas erradas para o mesmo e-mail e 20 no
        // total vindas do mesmo IP a cada 15 minutos.
        $bloqueio = max(minutosBloqueado($conexao, 'login', 5, 15, $email), minutosBloqueado($conexao, 'login-ip', 20, 15));
        $user = null;
        if ($bloqueio) {
            $erro = "Muitas tentativas de login. Tente novamente em $bloqueio " . ($bloqueio === 1 ? 'minuto' : 'minutos') . '.';
        } else {
            $stmt = mysqli_prepare($conexao, "SELECT usuario_id, usuario_senha, usuario_nome, usuario_tipo FROM usuarios WHERE usuario_email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        }

        if (!$bloqueio && $user && $user['usuario_senha'] !== '' && password_verify($senha, $user['usuario_senha'])) {
            limparTentativas($conexao, 'login', $email);
            atualizarHashSeNecessario($conexao, $user['usuario_id'], $senha, $user['usuario_senha']);
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $user['usuario_id'];
            $_SESSION['usuario_nome'] = $user['usuario_nome'];
            $_SESSION['usuario_tipo'] = aplicarTipoAdmin($conexao, $user['usuario_id'], $email, $user['usuario_tipo']);
            $_SESSION['login_metodo'] = 'senha';
            header('Location: index.php');
            exit;
        }
        if (!$bloqueio) {
            registrarTentativa($conexao, 'login', $email);
            registrarTentativa($conexao, 'login-ip');
            $erro = 'E-mail ou senha incorretos';
        }
    } elseif ($_POST['acao'] === 'registro') {
        $modo = 'registro';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senhaTexto = $_POST['senha'] ?? '';
        $nome = trim($_POST['nome'] ?? '');
        $idade = (int) ($_POST['idade'] ?? 0);
        $cidade = trim($_POST['cidade'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        $bloqueioCadastro = minutosBloqueado($conexao, 'cadastro', 5, 60);
        if ($bloqueioCadastro) {
            $erro = "Muitos cadastros feitos daqui. Tente novamente em $bloqueioCadastro minutos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail válido.';
        } elseif (mb_strlen($senhaTexto) < 6) {
            $erro = 'A senha precisa ter pelo menos 6 caracteres.';
        } elseif ($nome === '' || mb_strlen($nome) > 100) {
            $erro = 'Informe seu nome (até 100 caracteres).';
        } elseif ($idade < 13 || $idade > 120) {
            $erro = 'Idade deve estar entre 13 e 120 anos.';
        } else {
            $stmt = mysqli_prepare($conexao, "SELECT usuario_id FROM usuarios WHERE usuario_email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                $erro = 'Já existe uma conta com esse e-mail. Faça login.';
            }
        }

        // Foto: só imagens (extensão e tipo real validados em processarUpload).
        $foto = '';
        if (!$erro) {
            try {
                $foto = salvarUploadValidado($_FILES['foto'] ?? null, 'imagem') ?: '';
            } catch (Exception $e) {
                $erro = 'Foto: ' . $e->getMessage();
            }
        }

        if (!$erro) {
            $senha = hashSenha($senhaTexto);
            $fotoArtista = $foto ?: 'Componentes/icones/icone.png';
            $stmtArtista = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image, artista_descricao) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmtArtista, "ssss", $nome, $cidade, $fotoArtista, $descricao);

            if (mysqli_stmt_execute($stmtArtista)) {
                $artistaId = mysqli_insert_id($conexao);
                $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (usuario_email, usuario_senha, usuario_nome, usuario_idade, usuario_cidade, usuario_descricao, usuario_foto, artista_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssisssi", $email, $senha, $nome, $idade, $cidade, $descricao, $foto, $artistaId);

                if (mysqli_stmt_execute($stmt)) {
                    registrarTentativa($conexao, 'cadastro');
                    $sucesso = 'Conta criada com sucesso! Faça login.';
                    $modo = 'login';
                } else {
                    $erro = 'Erro ao criar conta. O e-mail pode já estar em uso.';
                }
            } else {
                $erro = 'Erro ao criar perfil de artista.';
            }
        }
    }
}

$provedores = [];
if (googleConfigurado()) {
    $provedores['google'] = 'Continuar com Google';
}
if (facebookConfigurado()) {
    $provedores['facebook'] = 'Continuar com Facebook';
}
if (appleConfigurado()) {
    $provedores['apple'] = 'Continuar com Apple';
}
$iconesProvedor = [
    'google' => '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>',
    'facebook' => '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M24 12.07C24 5.41 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07"/></svg>',
    'apple' => '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16.37 12.75c-.03-2.6 2.12-3.85 2.22-3.91-1.21-1.77-3.09-2.01-3.76-2.04-1.6-.16-3.12.94-3.93.94-.81 0-2.06-.92-3.39-.89-1.74.03-3.35 1.01-4.25 2.57-1.81 3.14-.46 7.79 1.3 10.34.86 1.25 1.89 2.65 3.23 2.6 1.3-.05 1.79-.84 3.36-.84 1.57 0 2.01.84 3.38.81 1.4-.02 2.28-1.27 3.13-2.52.99-1.45 1.39-2.85 1.42-2.92-.03-.01-2.72-1.05-2.75-4.14zM13.8 5.12c.71-.87 1.2-2.07 1.06-3.27-1.03.04-2.28.69-3.02 1.55-.66.77-1.24 2-1.09 3.18 1.15.09 2.33-.58 3.05-1.46z"/></svg>',
];
?>
<main class="auth-page">
    <a class="auth-brand" href="index.php"><img src="Componentes/icones/icone2.png" alt="" width="44" height="44"> Ressonance</a>

    <div class="auth-card">
        <h1><?= $modo === 'registro' ? 'Crie sua conta' : 'Entre no Ressonance' ?></h1>

        <?php if ($erro): ?><div class="auth-alert is-error" role="alert"><?= e($erro) ?></div><?php endif; ?>
        <?php if ($sucesso): ?><div class="auth-alert is-success" role="status"><?= e($sucesso) ?></div><?php endif; ?>

        <?php if ($provedores): ?>
        <div class="social-buttons">
            <?php foreach ($provedores as $provedor => $rotulo): ?>
            <a class="social-btn social-<?= $provedor ?>" href="oauth.php?provedor=<?= $provedor ?>"><?= $iconesProvedor[$provedor] ?><span><?= e($rotulo) ?></span></a>
            <?php endforeach; ?>
        </div>
        <div class="auth-divider"><span>ou</span></div>
        <?php endif; ?>

        <?php if ($modo === 'login'): ?>
        <form method="POST" class="form-stack" action="login.php">
            <input type="hidden" name="acao" value="login">
            <label class="field"><span>E-mail</span><input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
            <label class="field"><span>Senha</span><input type="password" name="senha" required autocomplete="current-password"></label>
            <button type="submit" class="btn-pill btn-accent btn-block">Entrar</button>
        </form>
        <p class="auth-switch">Não tem conta? <a href="login.php?modo=registro">Cadastre-se</a></p>
        <?php else: ?>
        <form method="POST" class="form-stack" action="login.php?modo=registro" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="registro">
            <label class="field"><span>E-mail</span><input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
            <label class="field"><span>Senha (mínimo 6 caracteres)</span><input type="password" name="senha" required minlength="6" autocomplete="new-password"></label>
            <label class="field"><span>Nome (ou nome artístico)</span><input type="text" name="nome" required maxlength="100" value="<?= e($_POST['nome'] ?? '') ?>"></label>
            <div class="form-grid">
                <label class="field"><span>Idade</span><input type="number" name="idade" min="13" max="120" required value="<?= e($_POST['idade'] ?? '') ?>"></label>
                <label class="field"><span>Cidade</span><input type="text" name="cidade" required maxlength="100" value="<?= e($_POST['cidade'] ?? '') ?>"></label>
            </div>
            <label class="field"><span>Foto de perfil (opcional)</span><input type="file" name="foto" accept="image/*"></label>
            <label class="field"><span>Sobre você (opcional)</span><textarea name="descricao" rows="3" maxlength="1000" placeholder="Se você é artista, conte sobre sua música."><?= e($_POST['descricao'] ?? '') ?></textarea></label>
            <button type="submit" class="btn-pill btn-accent btn-block">Criar conta</button>
        </form>
        <p class="auth-switch">Já tem conta? <a href="login.php">Entrar</a></p>
        <?php endif; ?>
    </div>

    <a class="auth-back" href="index.php">← Voltar ao início</a>
</main>
</body>
</html>
