<?php
include_once __DIR__ . '/url-helper.php';

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

// Monta o redirect_uri a partir do host atual, para funcionar tanto em
// localhost/tcc quanto em produção na raiz do domínio — desde que a URL
// correspondente também esteja cadastrada como "Authorized redirect URI"
// no Google Cloud Console.
function getGoogleRedirectUri() {
    return getBaseUrl() . 'callbackGoogle.php';
}
define('GOOGLE_REDIRECT_URI', getGoogleRedirectUri());

// URLs do Google OAuth
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

function getGoogleAuthUrl() {
    // State aleatório vinculado à sessão, para impedir login CSRF
    // (ver validação em callbackGoogle.php)
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;

    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'scope' => 'email profile',
        'response_type' => 'code',
        'access_type' => 'online',
        'state' => $state
    ];

    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}
?>