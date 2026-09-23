<?php
include_once 'verificarPerfilCompleto.php';
include_once 'DBConection.php';

function verificarLogin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['usuario_id'])) {
        // Verificar se o perfil está completo
        global $conexao;
        if (!verificarPerfilCompleto($_SESSION['usuario_id'], $conexao)) {
            completarPerfilAutomatico($_SESSION['usuario_id'], $conexao);
        }
        return true;
    }
    
    return false;
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