<?php
echo "Debug Index:<br>";
echo "1. Verificando arquivos...<br>";

$files = [
    "Componentes/páginas/php/verificarPerfilCompleto.php",
    "Componentes/páginas/head.php", 
    "Componentes/páginas/header.php",
    "Componentes/páginas/main.php",
    "Componentes/páginas/footer.php"
];

foreach($files as $file) {
    if(file_exists($file)) {
        echo "✓ $file existe<br>";
    } else {
        echo "✗ $file NÃO existe<br>";
    }
}

echo "<br>2. Testando includes...<br>";
try {
    include "Componentes/páginas/head.php";
    echo "Head incluído com sucesso<br>";
} catch(Exception $e) {
    echo "Erro no head: " . $e->getMessage() . "<br>";
}
?>