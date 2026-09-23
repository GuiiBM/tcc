<?php
// adminConfig.php - Define quem é administrador do site pelo e-mail.
//
// Os e-mails de admin vêm, em ordem de prioridade:
// 1) da variável de ambiente ADMIN_EMAILS (painel da hospedagem), separados
//    por vírgula. Ex: ADMIN_EMAILS=voce@gmail.com,outro@gmail.com
// 2) da constante ADMIN_EMAILS em adminEmails.php (ignorado pelo git), para
//    hospedagens sem variáveis de ambiente e para o XAMPP local.
//
// Quem fizer login com um desses e-mails vira admin automaticamente; os
// demais continuam como 'usuario'.
//
// Conta admin pré-definida: se adminEmails.php definir ADMIN_CONTA_EMAIL e
// ADMIN_CONTA_SENHA, essa conta é criada (já como admin) na primeira
// tentativa de login com esse e-mail. Depois de criada, a senha é a do banco:
// trocar ADMIN_CONTA_SENHA no arquivo não altera uma conta que já existe.

if (file_exists(__DIR__ . '/adminEmails.php')) {
    include_once __DIR__ . '/adminEmails.php';
}

function getEmailsAdmin() {
    $lista = getenv('ADMIN_EMAILS') ?: (defined('ADMIN_EMAILS') ? ADMIN_EMAILS : '');
    $emails = array_map('trim', explode(',', strtolower($lista)));
    if (defined('ADMIN_CONTA_EMAIL')) {
        $emails[] = strtolower(trim(ADMIN_CONTA_EMAIL));
    }
    return array_values(array_filter($emails));
}

function ehEmailAdmin($email) {
    return in_array(strtolower(trim($email)), getEmailsAdmin(), true);
}

// Garante que o tipo do usuário no banco e na sessão reflita a lista de
// admins. Retorna o tipo final ('admin' ou o tipo que já estava no banco).
function aplicarTipoAdmin($conexao, $usuario_id, $email, $tipoAtual) {
    if (!ehEmailAdmin($email) || $tipoAtual === 'admin') {
        return $tipoAtual;
    }

    $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_tipo = 'admin' WHERE usuario_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);

    return 'admin';
}

// Cria a conta admin pré-definida se ela ainda não existir. Chamada no
// login com o e-mail digitado, para não consultar o banco em toda página.
function garantirContaAdmin($conexao, $emailDigitado) {
    if (!defined('ADMIN_CONTA_EMAIL') || !defined('ADMIN_CONTA_SENHA')) {
        return;
    }
    $email = strtolower(trim(ADMIN_CONTA_EMAIL));
    if ($email === '' || ADMIN_CONTA_SENHA === '' || strtolower(trim($emailDigitado)) !== $email) {
        return;
    }

    $stmt = mysqli_prepare($conexao, "SELECT usuario_id FROM usuarios WHERE usuario_email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        return;
    }

    $senha = hashSenha(ADMIN_CONTA_SENHA);
    $nome = defined('ADMIN_CONTA_NOME') && ADMIN_CONTA_NOME !== '' ? ADMIN_CONTA_NOME : 'Administrador';
    $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (usuario_email, usuario_senha, usuario_nome, usuario_tipo) VALUES (?, ?, ?, 'admin')");
    mysqli_stmt_bind_param($stmt, "sss", $email, $senha, $nome);
    mysqli_stmt_execute($stmt);
}
?>
