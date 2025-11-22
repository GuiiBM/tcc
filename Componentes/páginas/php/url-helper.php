<?php
// url-helper.php - Helper para URLs adaptáveis
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Se estiver no ngrok, adicionar /tcc/ ao final
    if (strpos($host, 'ngrok') !== false) {
        return $protocol . '://' . $host . '/tcc/';
    }
    
    // Local XAMPP
    return $protocol . '://' . $host . '/tcc/';
}

function isNgrokEnvironment() {
    return isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false;
}
?>