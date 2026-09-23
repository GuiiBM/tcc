<?php
// seguranca.php - Sessão segura, chave secreta do site, hash de senhas e de
// IPs, limite de tentativas (força bruta), cabeçalhos de segurança (CSP) e
// assinatura dos links temporários de áudio.
//
// Não depende do banco para as partes usadas pelo stream.php (sessão,
// segredo e assinatura), que precisam ser leves.

define('PASTA_PRIVADA', dirname(__DIR__, 2) . '/privado');

// ===================== Sessão =====================
// Todas as páginas chamam isto em vez de session_start(): cookie HttpOnly,
// Secure (em HTTPS) e SameSite=Lax; modo estrito (não aceita IDs de sessão
// inventados); arquivos de sessão numa pasta própria (a pasta /tmp
// compartilhada da hospedagem apaga sessões depois de 24 minutos).
function conexaoHttps() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function iniciarSessaoSegura() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $duracao = 30 * 24 * 3600; // "lembrar de mim" por 30 dias de inatividade

    $pasta = PASTA_PRIVADA . '/sessoes';
    if (!is_dir($pasta)) {
        @mkdir($pasta, 0700, true);
    }
    if (is_dir($pasta) && is_writable($pasta)) {
        session_save_path($pasta);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '200');
    }
    ini_set('session.gc_maxlifetime', (string) $duracao);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');

    session_name('RSSID');
    session_set_cookie_params([
        'lifetime' => $duracao,
        'path' => '/',
        'secure' => conexaoHttps(),
        'httponly' => true,
        // Strict quebraria o retorno do login do Google (vem de outro site);
        // Lax já impede que outros sites façam POST com a sessão do usuário.
        'samesite' => 'Lax',
    ]);
    session_start();

    // Troca o ID da sessão a cada 30 minutos (reduz o valor de um ID roubado).
    $agora = time();
    if (!isset($_SESSION['_criada'])) {
        $_SESSION['_criada'] = $agora;
    } elseif ($agora - $_SESSION['_criada'] > 1800 && !headers_sent()) {
        // false: mantém a sessão antiga até expirar, para requisições em
        // paralelo (áudio + API) que ainda usam o ID anterior não caírem.
        session_regenerate_id(false);
        $_SESSION['_criada'] = $agora;
    }
}

// ===================== Chave secreta =====================
// Gerada automaticamente na primeira vez e guardada fora do alcance do
// navegador (pasta "privado" com acesso bloqueado).
function segredoApp() {
    static $segredo = null;
    if ($segredo !== null) {
        return $segredo;
    }
    $arquivo = PASTA_PRIVADA . '/segredo.php';
    if (is_file($arquivo)) {
        $segredo = (string) include $arquivo;
    }
    if ($segredo && strlen($segredo) >= 32) {
        return $segredo;
    }
    // Sem arquivo: usa (ou cria) a chave guardada no banco. Serve quando a
    // pasta "privado" não tem permissão de escrita.
    global $conexao;
    if (isset($conexao) && $conexao instanceof mysqli) {
        $r = @mysqli_query($conexao, "SELECT valor FROM sistema WHERE chave = 'segredo_app'");
        if ($r && ($linha = mysqli_fetch_row($r)) && strlen($linha[0]) >= 32) {
            return $segredo = $linha[0];
        }
    }
    $novo = bin2hex(random_bytes(32));
    if (!is_dir(PASTA_PRIVADA)) {
        @mkdir(PASTA_PRIVADA, 0700, true);
    }
    if (@file_put_contents($arquivo, "<?php\n// Chave secreta gerada automaticamente. Não compartilhe.\nreturn '$novo';\n", LOCK_EX)) {
        @chmod($arquivo, 0600);
    } elseif (isset($conexao) && $conexao instanceof mysqli) {
        $stmt = mysqli_prepare($conexao, "INSERT IGNORE INTO sistema (chave, valor) VALUES ('segredo_app', ?)");
        mysqli_stmt_bind_param($stmt, "s", $novo);
        mysqli_stmt_execute($stmt);
        // Se outra requisição gravou antes, usa a dela.
        $r = mysqli_query($conexao, "SELECT valor FROM sistema WHERE chave = 'segredo_app'");
        $novo = ($r && ($linha = mysqli_fetch_row($r))) ? $linha[0] : $novo;
    }
    return $segredo = $novo;
}

// ===================== Senhas =====================
function algoritmoSenha() {
    return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
}

function hashSenha($senha) {
    return password_hash($senha, algoritmoSenha());
}

// Depois de um login correto, atualiza hashes antigos para o algoritmo atual.
function atualizarHashSeNecessario($conexao, $usuarioId, $senha, $hashAtual) {
    if (password_needs_rehash($hashAtual, algoritmoSenha())) {
        $novo = hashSenha($senha);
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_senha = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $novo, $usuarioId);
        mysqli_stmt_execute($stmt);
    }
}

// ===================== IP anonimizado =====================
// Estatísticas (ouvintes, limite de tentativas) guardam só um hash do IP,
// nunca o IP em si (LGPD: minimização de dados pessoais).
function ipVisitante() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function hashIp($ip = null) {
    return 'h:' . substr(hash('sha256', segredoApp() . ($ip ?? ipVisitante())), 0, 38);
}

// ===================== Limite de tentativas =====================
// Conta falhas por IP (e por IP + e-mail) numa janela de tempo. Ex: login
// bloqueia depois de 5 senhas erradas em 15 minutos.
function chaveTentativa($tipo, $extra = '') {
    return $tipo . ':' . substr(hash('sha256', segredoApp() . ipVisitante() . '|' . mb_strtolower($extra)), 0, 40);
}

function minutosBloqueado($conexao, $tipo, $maximo, $janelaMinutos, $extra = '') {
    $chave = chaveTentativa($tipo, $extra);
    $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) AS total, MIN(criada) AS primeira FROM tentativa WHERE chave = ? AND criada > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    mysqli_stmt_bind_param($stmt, "si", $chave, $janelaMinutos);
    mysqli_stmt_execute($stmt);
    $linha = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ((int) $linha['total'] < $maximo) {
        return 0;
    }
    $restante = strtotime($linha['primeira']) + $janelaMinutos * 60 - time();
    return max(1, (int) ceil($restante / 60));
}

function registrarTentativa($conexao, $tipo, $extra = '') {
    $chave = chaveTentativa($tipo, $extra);
    $stmt = mysqli_prepare($conexao, "INSERT INTO tentativa (chave) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $chave);
    mysqli_stmt_execute($stmt);
    // Faxina ocasional: registros com mais de 1 dia não servem para nada.
    if (random_int(1, 50) === 1) {
        mysqli_query($conexao, "DELETE FROM tentativa WHERE criada < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }
}

function limparTentativas($conexao, $tipo, $extra = '') {
    $chave = chaveTentativa($tipo, $extra);
    $stmt = mysqli_prepare($conexao, "DELETE FROM tentativa WHERE chave = ?");
    mysqli_stmt_bind_param($stmt, "s", $chave);
    mysqli_stmt_execute($stmt);
}

// ===================== Cabeçalhos de segurança =====================
function nonceCsp() {
    static $nonce = null;
    return $nonce ?? ($nonce = base64_encode(random_bytes(16)));
}

// $legado = páginas antigas do admin que usam onclick="..." no HTML; nelas
// scripts inline continuam permitidos. As demais só executam scripts do
// próprio site ou com o nonce desta resposta.
function enviarCabecalhosSeguranca($legado = false, $json = false) {
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    if (conexaoHttps()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
    if ($json) {
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        return;
    }
    $scripts = $legado ? "'self' 'unsafe-inline'" : "'self' 'nonce-" . nonceCsp() . "'";
    header('Content-Security-Policy: ' . implode('; ', [
        "default-src 'self'",
        "script-src $scripts",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "font-src 'self' https://fonts.gstatic.com",
        // Fotos de perfil do Google/Facebook
        "img-src 'self' data: blob: https://*.googleusercontent.com https://*.fbcdn.net https://platform-lookaside.fbsbx.com",
        // Áudio do próprio site; episódios de podcast podem ter link https externo
        "media-src 'self' blob: https:",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
    ]));
}

// ===================== Links temporários de áudio =====================
// O caminho real do arquivo nunca vai para a página: o player recebe um
// link stream.php?... assinado, que vale por alguns minutos e só para a
// sessão de quem o recebeu. A pasta de áudios é bloqueada para acesso direto.
define('VALIDADE_LINK_AUDIO', 20 * 60);

function assinaturaAudio($tipo, $id, $qualidade, $expira, $sessao) {
    return substr(hash_hmac('sha256', "$tipo|$id|$qualidade|$expira|$sessao", segredoApp()), 0, 32);
}

function linkAudio($tipo, $id, $qualidade = 'a') {
    $expira = time() + VALIDADE_LINK_AUDIO;
    $assinatura = assinaturaAudio($tipo, (int) $id, $qualidade, $expira, session_id());
    return 'stream.php?' . http_build_query(['t' => $tipo, 'id' => (int) $id, 'q' => $qualidade, 'e' => $expira, 's' => $assinatura]);
}
?>
