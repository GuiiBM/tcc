<?php
include "Componentes/páginas/php/DBConection.php";

echo "<div style='max-width: 800px; margin: 50px auto; padding: 20px; background: rgba(22, 27, 34, 0.9); border-radius: 16px; color: #f0f6fc;'>";
echo "<h2 style='color: #ffd700; text-align: center; margin-bottom: 30px;'>Adicionando Campo de Descrição para Artistas</h2>";

// Verificar se a coluna já existe
echo "<p>Verificando se a coluna 'artista_descricao' já existe...</p>";
$sql_check = "SHOW COLUMNS FROM artista LIKE 'artista_descricao'";
$result = mysqli_query($conexao, $sql_check);

if (mysqli_num_rows($result) == 0) {
    echo "<p>Adicionando coluna 'artista_descricao'...</p>";
    $sql_alter = "ALTER TABLE artista ADD COLUMN artista_descricao TEXT";
    
    if (mysqli_query($conexao, $sql_alter)) {
        echo "<p style='color: #00d9ff;'>✓ Coluna 'artista_descricao' adicionada com sucesso!</p>";
        
        // Adicionar descrições de exemplo para artistas existentes
        echo "<p>Adicionando descrições de exemplo...</p>";
        $sql_update = "UPDATE artista SET artista_descricao = CONCAT('Artista talentoso de ', COALESCE(artista_cidade, 'localização não informada'), '. Explore suas músicas e descubra seu estilo único.') WHERE artista_descricao IS NULL OR artista_descricao = ''";
        
        if (mysqli_query($conexao, $sql_update)) {
            echo "<p style='color: #00d9ff;'>✓ Descrições de exemplo adicionadas!</p>";
        }
    } else {
        echo "<p style='color: #ff4444;'>✗ Erro ao adicionar coluna: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: #00d9ff;'>✓ Coluna 'artista_descricao' já existe!</p>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<h3 style='color: #ffd700;'>Atualização concluída!</h3>";
echo "<a href='admin.php' style='background: linear-gradient(135deg, #ffd700, #ffed4e); color: #0d1117; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Voltar ao Menu</a>";
echo "</div>";
echo "</div>";
?>