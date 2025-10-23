<?php
include "Componentes/páginas/php/DBConection.php";

// Adicionar coluna artista_link se não existir
$sql = "ALTER TABLE artista ADD COLUMN IF NOT EXISTS artista_link VARCHAR(255) DEFAULT NULL";

if (mysqli_query($conexao, $sql)) {
    echo "Coluna artista_link adicionada com sucesso!<br>";
} else {
    echo "Erro ao adicionar coluna: " . mysqli_error($conexao) . "<br>";
}

// Verificar se a coluna foi criada
$result = mysqli_query($conexao, "DESCRIBE artista");
echo "<h3>Estrutura da tabela artista:</h3>";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

mysqli_close($conexao);
?>