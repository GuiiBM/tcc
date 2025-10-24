<?php
// Configurações do Google OAuth
// Para usar: substitua 'false' pelas suas credenciais reais
define('GOOGLE_CLIENT_ID', false ? 'SUA_CREDENCIAL_REAL' : 'SEU_GOOGLE_CLIENT_ID_AQUI');
define('GOOGLE_CLIENT_SECRET', false ? 'SUA_CREDENCIAL_REAL' : 'SEU_GOOGLE_CLIENT_SECRET_AQUI');

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