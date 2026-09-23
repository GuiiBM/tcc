<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
// Retorno do "Iniciar sessão com a Apple" (response_mode=form_post).
iniciarSessaoSegura();
include "Componentes/paginas/php/DBConection.php";
include "Componentes/paginas/php/loginSocial.php";

$stateEsperado = $_COOKIE['apple_oauth_state'] ?? '';
setcookie('apple_oauth_state', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'None']);

if (!appleConfigurado() || empty($_POST['code'])) {
    header('Location: login.php?erro=' . (isset($_POST['error']) ? 'cancelado' : 'apple_failed'));
    exit;
}
if ($stateEsperado === '' || !hash_equals($stateEsperado, $_POST['state'] ?? '')) {
    header('Location: login.php?erro=state_invalido');
    exit;
}

try {
    $token = requisicaoJson('https://appleid.apple.com/auth/token', [
        'client_id' => configOAuth('APPLE_CLIENT_ID'),
        'client_secret' => clientSecretApple(),
        'code' => $_POST['code'],
        'grant_type' => 'authorization_code',
        'redirect_uri' => urlCallback('apple'),
    ]);
} catch (Exception $e) {
    error_log('Apple: ' . $e->getMessage());
    $token = null;
}
if (empty($token['id_token'])) {
    header('Location: login.php?erro=apple_failed');
    exit;
}

// O id_token veio direto da Apple por HTTPS em troca do nosso client secret,
// então basta conferir emissor, destinatário e validade.
$partes = explode('.', $token['id_token']);
$dados = count($partes) === 3 ? json_decode(base64UrlDecode($partes[1]), true) : null;
if (!$dados || ($dados['iss'] ?? '') !== 'https://appleid.apple.com' || ($dados['aud'] ?? '') !== configOAuth('APPLE_CLIENT_ID')
    || ($dados['exp'] ?? 0) < time() || empty($dados['email'])) {
    header('Location: login.php?erro=apple_failed');
    exit;
}

// A Apple só envia o nome no primeiro login.
$nome = '';
if (!empty($_POST['user'])) {
    $user = json_decode($_POST['user'], true);
    $nome = trim(($user['name']['firstName'] ?? '') . ' ' . ($user['name']['lastName'] ?? ''));
}

concluirLoginSocial($conexao, 'apple', $dados['email'], $nome, '');
