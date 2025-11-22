<?php
echo "Teste ngrok funcionando!<br>";
echo "Host: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
phpinfo();
?>