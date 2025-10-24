<?php
function verificarLogin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['usuario_id']);
}

function verificarAdmin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';
}

function redirecionarSeNaoLogado() {
    if (!verificarLogin()) {
        header('Location: login.php');
        exit;
    }
}

function redirecionarSeNaoAdmin() {
    if (!verificarAdmin()) {
        header('Location: index.php');
        exit;
    }
}
?>