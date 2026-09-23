<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
// Retorno do login com Facebook (URL cadastrada no painel Meta for Developers).
iniciarSessaoSegura();
include "Componentes/paginas/php/DBConection.php";
include "Componentes/paginas/php/loginSocial.php";

if (!facebookConfigurado() || !isset($_GET['code'])) {
    header('Location: login.php?erro=' . (isset($_GET['error']) ? 'cancelado' : 'facebook_failed'));
    exit;
}

$stateEsperado = $_SESSION['facebook_oauth_state'] ?? null;
unset($_SESSION['facebook_oauth_state']);
if (!$stateEsperado || !hash_equals($stateEsperado, $_GET['state'] ?? '')) {
    header('Location: login.php?erro=state_invalido');
    exit;
}

$token = requisicaoJson('https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
    'client_id' => configOAuth('FACEBOOK_APP_ID'),
    'client_secret' => configOAuth('FACEBOOK_APP_SECRET'),
    'redirect_uri' => urlCallback('facebook'),
    'code' => $_GET['code'],
]));
if (empty($token['access_token'])) {
    header('Location: login.php?erro=facebook_failed');
    exit;
}

$perfil = requisicaoJson('https://graph.facebook.com/v19.0/me?' . http_build_query([
    'fields' => 'id,name,email,picture.width(300).height(300)',
    'access_token' => $token['access_token'],
]));
if (empty($perfil['email'])) {
    // Contas do Facebook criadas só com telefone não têm e-mail.
    header('Location: login.php?erro=sem_email');
    exit;
}

concluirLoginSocial($conexao, 'facebook', $perfil['email'], $perfil['name'] ?? '', $perfil['picture']['data']['url'] ?? '');
