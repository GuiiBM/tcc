<?php
// loginSocial.php - Login com Google, Facebook e Apple.
//
// Credenciais (arquivos ignorados pelo git, ou variáveis de ambiente):
//   Google:   clientId.php / clientSecret.php  (ver configGoogle.php)
//   Facebook: oauthConfig.php -> FACEBOOK_APP_ID, FACEBOOK_APP_SECRET
//   Apple:    oauthConfig.php -> APPLE_CLIENT_ID (Services ID), APPLE_TEAM_ID,
//             APPLE_KEY_ID, APPLE_PRIVATE_KEY (conteúdo do arquivo .p8)
// Um provedor sem credenciais simplesmente não aparece na tela de login.

include_once __DIR__ . '/url-helper.php';
include_once __DIR__ . '/configGoogle.php';
include_once __DIR__ . '/adminConfig.php';
if (file_exists(__DIR__ . '/oauthConfig.php')) {
    include_once __DIR__ . '/oauthConfig.php';
}

function configOAuth($nome) {
    if (defined($nome)) {
        return constant($nome);
    }
    $valor = getenv($nome);
    return $valor === false ? '' : $valor;
}

function googleConfigurado() {
    return GOOGLE_CLIENT_ID !== 'SEU_GOOGLE_CLIENT_ID_AQUI' && GOOGLE_CLIENT_SECRET !== 'SEU_GOOGLE_CLIENT_SECRET_AQUI';
}

function facebookConfigurado() {
    return configOAuth('FACEBOOK_APP_ID') !== '' && configOAuth('FACEBOOK_APP_SECRET') !== '';
}

function appleConfigurado() {
    return configOAuth('APPLE_CLIENT_ID') !== '' && configOAuth('APPLE_TEAM_ID') !== ''
        && configOAuth('APPLE_KEY_ID') !== '' && configOAuth('APPLE_PRIVATE_KEY') !== '';
}

function urlCallback($provedor) {
    return getBaseUrl() . 'callback' . ucfirst($provedor) . '.php';
}

// Requisição HTTP simples com cURL. Retorna o JSON decodificado ou null.
function requisicaoJson($url, $post = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']);
    }
    $resposta = curl_exec($ch);
    if ($resposta === false) {
        error_log('OAuth cURL: ' . curl_error($ch));
    }
    curl_close($ch);
    return $resposta ? json_decode($resposta, true) : null;
}

// ----- Facebook -----
function urlLoginFacebook() {
    $state = bin2hex(random_bytes(16));
    $_SESSION['facebook_oauth_state'] = $state;
    return 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query([
        'client_id' => configOAuth('FACEBOOK_APP_ID'),
        'redirect_uri' => urlCallback('facebook'),
        'state' => $state,
        'scope' => 'email,public_profile',
        'response_type' => 'code',
    ]);
}

// ----- Apple -----
function base64UrlEncode($dados) {
    return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
}

function base64UrlDecode($dados) {
    return base64_decode(strtr($dados, '-_', '+/') . str_repeat('=', (4 - strlen($dados) % 4) % 4));
}

// A Apple exige que o "client secret" seja um JWT assinado (ES256) com a
// chave privada .p8 da conta de desenvolvedor.
function clientSecretApple() {
    $chave = configOAuth('APPLE_PRIVATE_KEY');
    if (is_file($chave)) {
        $chave = file_get_contents($chave);
    }
    $cabecalho = base64UrlEncode(json_encode(['alg' => 'ES256', 'kid' => configOAuth('APPLE_KEY_ID')]));
    $agora = time();
    $corpo = base64UrlEncode(json_encode([
        'iss' => configOAuth('APPLE_TEAM_ID'),
        'iat' => $agora,
        'exp' => $agora + 3600,
        'aud' => 'https://appleid.apple.com',
        'sub' => configOAuth('APPLE_CLIENT_ID'),
    ]));
    $assinaturaDer = '';
    if (!openssl_sign("$cabecalho.$corpo", $assinaturaDer, $chave, OPENSSL_ALGO_SHA256)) {
        throw new Exception('Chave privada da Apple inválida');
    }
    return "$cabecalho.$corpo." . base64UrlEncode(assinaturaDerParaBruta($assinaturaDer));
}

// O OpenSSL gera a assinatura ECDSA em DER; JWT usa R||S (64 bytes).
function assinaturaDerParaBruta($der) {
    $pos = 2;
    if (ord($der[1]) & 0x80) {
        $pos += ord($der[1]) & 0x7f;
    }
    $partes = [];
    for ($i = 0; $i < 2; $i++) {
        $pos++; // tag INTEGER
        $tamanho = ord($der[$pos++]);
        $valor = ltrim(substr($der, $pos, $tamanho), "\x00");
        $partes[] = str_pad($valor, 32, "\x00", STR_PAD_LEFT);
        $pos += $tamanho;
    }
    return $partes[0] . $partes[1];
}

function urlLoginApple() {
    // O retorno da Apple é um POST vindo de outro site: o cookie de sessão
    // (SameSite=Lax) não é enviado, então o "state" vai num cookie próprio.
    $state = bin2hex(random_bytes(16));
    setcookie('apple_oauth_state', $state, [
        'expires' => time() + 600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
    return 'https://appleid.apple.com/auth/authorize?' . http_build_query([
        'client_id' => configOAuth('APPLE_CLIENT_ID'),
        'redirect_uri' => urlCallback('apple'),
        'response_type' => 'code',
        'response_mode' => 'form_post',
        'scope' => 'name email',
        'state' => $state,
    ]);
}

// ----- Conclusão comum a todos os provedores -----
// Entra com a conta do e-mail informado (criando-a se não existir) e
// redireciona. Contas novas passam por completarPerfilGoogle.php.
function concluirLoginSocial($conexao, $provedor, $email, $nome, $foto) {
    $email = strtolower(trim($email));
    $nome = trim($nome) ?: explode('@', $email)[0];

    $stmt = mysqli_prepare($conexao, "SELECT usuario_id, usuario_nome, usuario_tipo, usuario_foto FROM usuarios WHERE usuario_email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    session_regenerate_id(true);
    $_SESSION['login_metodo'] = $provedor;

    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['usuario_id'];
        $_SESSION['usuario_nome'] = $usuario['usuario_nome'];
        $_SESSION['usuario_tipo'] = aplicarTipoAdmin($conexao, $usuario['usuario_id'], $email, $usuario['usuario_tipo']);
        if (!$usuario['usuario_foto'] && $foto) {
            $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_foto = ? WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $foto, $usuario['usuario_id']);
            mysqli_stmt_execute($stmt);
        }
        header('Location: index.php');
        exit;
    }

    // Conta nova: cria o perfil de artista vinculado (modelo do projeto) e o usuário.
    $fotoArtista = $foto ?: 'Componentes/icones/icone.png';
    $descricao = "Artista conectado via " . ucfirst($provedor) . ". Complete seu perfil para personalizar esta descrição.";
    $stmt = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image, artista_descricao) VALUES (?, '', ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $nome, $fotoArtista, $descricao);
    if (!mysqli_stmt_execute($stmt)) {
        header('Location: login.php?erro=artista_creation_failed');
        exit;
    }
    $artistaId = mysqli_insert_id($conexao);

    $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (usuario_email, usuario_senha, usuario_nome, usuario_foto, usuario_tipo, artista_id) VALUES (?, '', ?, ?, 'usuario', ?)");
    mysqli_stmt_bind_param($stmt, "sssi", $email, $nome, $foto, $artistaId);
    if (!mysqli_stmt_execute($stmt)) {
        header('Location: login.php?erro=registro_failed');
        exit;
    }
    $usuarioId = mysqli_insert_id($conexao);

    $_SESSION['usuario_id'] = $usuarioId;
    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['usuario_tipo'] = aplicarTipoAdmin($conexao, $usuarioId, $email, 'usuario');
    $_SESSION['usuario_foto'] = $foto;
    $_SESSION['artista_id'] = $artistaId;
    $_SESSION['google_incomplete'] = true;
    header('Location: completarPerfilGoogle.php');
    exit;
}
?>
