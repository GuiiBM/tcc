<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
// Retorno do login com Google (URL cadastrada no Google Cloud Console).
iniciarSessaoSegura();
include "Componentes/paginas/php/DBConection.php";
include "Componentes/paginas/php/loginSocial.php";

if (!isset($_GET['code'])) {
    header('Location: login.php?erro=' . (isset($_GET['error']) ? 'cancelado' : 'google_auth_failed'));
    exit;
}

// Valida o state para impedir login CSRF (ver geração em configGoogle.php::getGoogleAuthUrl)
$stateEsperado = $_SESSION['google_oauth_state'] ?? null;
unset($_SESSION['google_oauth_state']);
if (!$stateEsperado || !isset($_GET['state']) || !hash_equals($stateEsperado, $_GET['state'])) {
    header('Location: login.php?erro=google_state_invalido');
    exit;
}

$tokenData = requisicaoJson(GOOGLE_TOKEN_URL, [
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
]);
if (!isset($tokenData['access_token'])) {
    header('Location: login.php?erro=google_token_failed');
    exit;
}

$userData = requisicaoJson(GOOGLE_USER_INFO_URL . '?access_token=' . urlencode($tokenData['access_token']));
if (empty($userData['email']) || (isset($userData['verified_email']) && !$userData['verified_email'])) {
    header('Location: login.php?erro=google_user_failed');
    exit;
}

concluirLoginSocial($conexao, 'google', $userData['email'], $userData['name'] ?? '', $userData['picture'] ?? '');
