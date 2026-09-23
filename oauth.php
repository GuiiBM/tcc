<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
// Inicia o login social: oauth.php?provedor=facebook|apple
iniciarSessaoSegura();
include "Componentes/paginas/php/loginSocial.php";

$provedor = $_GET['provedor'] ?? '';
if ($provedor === 'facebook' && facebookConfigurado()) {
    header('Location: ' . urlLoginFacebook());
} elseif ($provedor === 'apple' && appleConfigurado()) {
    header('Location: ' . urlLoginApple());
} else {
    header('Location: login.php?erro=provedor_indisponivel');
}
exit;
