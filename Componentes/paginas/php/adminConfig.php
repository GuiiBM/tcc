<?php
// adminConfig.php - Define quem é administrador do site pelo e-mail.
//
// Os e-mails de admin vêm, em ordem de prioridade:
// 1) da variável de ambiente ADMIN_EMAILS (painel da hospedagem), separados
//    por vírgula. Ex: ADMIN_EMAILS=voce@gmail.com,outro@gmail.com
// 2) da constante ADMIN_EMAILS em adminEmails.php (ignorado pelo git), para
//    hospedagens sem variáveis de ambiente e para o XAMPP local.
//
// Quem fizer login (Google ou e-mail/senha) com um desses e-mails vira admin
// automaticamente; os demais continuam como 'usuario'.

if (file_exists(__DIR__ . '/adminEmails.php')) {
    include_once __DIR__ . '/adminEmails.php';
}

function getEmailsAdmin() {
    $lista = getenv('ADMIN_EMAILS') ?: (defined('ADMIN_EMAILS') ? ADMIN_EMAILS : '');
    $emails = array_map('trim', explode(',', strtolower($lista)));
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
?>
