<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/php/configGoogle.php";

if (!isset($_GET['code'])) {
    header('Location: login.php?erro=google_auth_failed');
    exit;
}

// Trocar código por token
$postData = [
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code']
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    header('Location: login.php?erro=google_token_failed');
    exit;
}

// Obter dados do usuário
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_USER_INFO_URL . '?access_token=' . $tokenData['access_token']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$userResponse = curl_exec($ch);
curl_close($ch);

$userData = json_decode($userResponse, true);

if (!isset($userData['email'])) {
    header('Location: login.php?erro=google_user_failed');
    exit;
}

// Verificar se usuário já existe
$email = mysqli_real_escape_string($conexao, $userData['email']);
$stmt = mysqli_prepare($conexao, "SELECT usuario_id, usuario_nome, usuario_tipo FROM usuarios WHERE usuario_email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    // Usuário já existe, fazer login
    $_SESSION['usuario_id'] = $user['usuario_id'];
    $_SESSION['usuario_nome'] = $user['usuario_nome'];
    $_SESSION['usuario_tipo'] = $user['usuario_tipo'];
} else {
    // Criar novo usuário
    $nome = mysqli_real_escape_string($conexao, $userData['name']);
    $foto = isset($userData['picture']) ? mysqli_real_escape_string($conexao, $userData['picture']) : '';
    
    $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (usuario_email, usuario_senha, usuario_nome, usuario_foto, usuario_tipo) VALUES (?, '', ?, ?, 'usuario')");
    mysqli_stmt_bind_param($stmt, "sss", $email, $nome, $foto);
    
    if (mysqli_stmt_execute($stmt)) {
        $usuario_id = mysqli_insert_id($conexao);
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_tipo'] = 'usuario';
        $_SESSION['usuario_foto'] = $foto;
        $_SESSION['google_incomplete'] = true;
        header('Location: completarPerfilGoogle.php');
        exit;
    } else {
        header('Location: login.php?erro=registro_failed');
        exit;
    }
}

header('Location: index.php');
exit;
?>