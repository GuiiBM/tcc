<?php
include "Componentes/páginas/php/DBConection.php";

echo "<h3>Estrutura da tabela musica:</h3>";
$result = mysqli_query($conexao, "DESCRIBE musica");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

echo "<h3>Estrutura da tabela artista:</h3>";
$result = mysqli_query($conexao, "DESCRIBE artista");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}
?>