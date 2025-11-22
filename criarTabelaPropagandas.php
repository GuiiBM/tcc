<?php
include "Componentes/páginas/php/DBConection.php";

echo "<div style='max-width: 800px; margin: 50px auto; padding: 20px; background: rgba(22, 27, 34, 0.9); border-radius: 16px; color: #f0f6fc;'>";
echo "<h2 style='color: #ffd700; text-align: center; margin-bottom: 30px;'>Criando Tabela de Propagandas</h2>";

// Criar tabela propagandas
echo "<p>Criando tabela 'propagandas'...</p>";
$sql = 'CREATE TABLE IF NOT EXISTS propagandas(
propaganda_id INT PRIMARY KEY AUTO_INCREMENT,
propaganda_nome VARCHAR(255) NOT NULL,
propaganda_ordem INT NOT NULL DEFAULT 0,
propaganda_ativa BOOLEAN DEFAULT TRUE,
data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);';

if (mysqli_query($conexao, $sql)) {
    echo "<p style='color: #00d9ff;'>✓ Tabela 'propagandas' criada com sucesso!</p>";
} else {
    echo "<p style='color: #ff4444;'>✗ Erro ao criar tabela 'propagandas': " . mysqli_error($conexao) . "</p>";
}

// Migrar propagandas existentes
echo "<p>Migrando propagandas existentes...</p>";
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/tcc/Componentes/Armazenamento/propaganda/';
$existingImages = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);

$ordem = 1;
foreach ($existingImages as $image) {
    $imageName = basename($image);
    
    // Verificar se já existe no banco
    $check = mysqli_prepare($conexao, "SELECT propaganda_id FROM propagandas WHERE propaganda_nome = ?");
    mysqli_stmt_bind_param($check, "s", $imageName);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);
    
    if (mysqli_num_rows($result) == 0) {
        $stmt = mysqli_prepare($conexao, "INSERT INTO propagandas (propaganda_nome, propaganda_ordem) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $imageName, $ordem);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: #00d9ff;'>✓ Propaganda migrada: $imageName (ordem: $ordem)</p>";
            $ordem++;
        }
    }
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<h3 style='color: #ffd700;'>Tabela de propagandas configurada!</h3>";
echo "<a href='gerenciarPropagandas.php' style='background: linear-gradient(135deg, #ffd700, #ffed4e); color: #0d1117; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Gerenciar Propagandas</a>";
echo "</div>";
echo "</div>";
?>