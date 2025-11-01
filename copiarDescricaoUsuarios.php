<?php
include "Componentes/páginas/php/DBConection.php";

echo "<div style='max-width: 800px; margin: 50px auto; padding: 20px; background: rgba(22, 27, 34, 0.9); border-radius: 16px; color: #f0f6fc;'>";
echo "<h2 style='color: #ffd700; text-align: center; margin-bottom: 30px;'>Copiando Descrições dos Usuários para Artistas</h2>";

// Adicionar coluna se não existir
$sql_check = "SHOW COLUMNS FROM artista LIKE 'artista_descricao'";
$result = mysqli_query($conexao, $sql_check);
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conexao, "ALTER TABLE artista ADD COLUMN artista_descricao TEXT");
    echo "<p style='color: #00d9ff;'>✓ Coluna artista_descricao criada!</p>";
}

// Copiar descrições dos usuários para artistas
$sql_update = "UPDATE artista a 
               JOIN usuarios u ON a.artista_id = u.artista_id 
               SET a.artista_descricao = u.usuario_descricao 
               WHERE u.usuario_descricao IS NOT NULL AND u.usuario_descricao != ''";

$result = mysqli_query($conexao, $sql_update);
$affected = mysqli_affected_rows($conexao);

echo "<p style='color: #00d9ff;'>✓ $affected descrições copiadas com sucesso!</p>";
echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='admin.php' style='background: linear-gradient(135deg, #ffd700, #ffed4e); color: #0d1117; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Voltar</a>";
echo "</div></div>";
?>