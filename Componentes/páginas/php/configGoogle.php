<?php
// Configurações do Google OAuth
// Carrega credenciais dos arquivos separados (ignorados pelo git)
if (file_exists(__DIR__ . '/clientId.php')) {
    include_once 'clientId.php';
}
if (file_exists(__DIR__ . '/clientSecret.php')) {
    include_once 'clientSecret.php';
}

// Define valores padrão se não foram carregados
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', 'SEU_GOOGLE_CLIENT_ID_AQUI');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', 'SEU_GOOGLE_CLIENT_SECRET_AQUI');
}

define('GOOGLE_REDIRECT_URI', 'http://localhost/tcc/callbackGoogle.php');

// URLs do Google OAuth
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'scope' => 'email profile',
        'response_type' => 'code',
        'access_type' => 'online'
    ];
    
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}
?>