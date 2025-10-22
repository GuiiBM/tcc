<?php
echo "<div style='max-width: 800px; margin: 50px auto; padding: 20px; background: rgba(22, 27, 34, 0.9); border-radius: 16px; color: #f0f6fc;'>";
echo "<h2 style='color: #ffd700; text-align: center; margin-bottom: 30px;'>Inicializando Banco de Dados</h2>";

// Criar tabela artista
echo "<p>Criando tabela 'artista'...</p>";
$sql = 'CREATE TABLE IF NOT EXISTS artista(
artista_id INT PRIMARY KEY AUTO_INCREMENT,
artista_nome VARCHAR(100) NOT NULL,
artista_cidade VARCHAR(100),
artista_image VARCHAR(255)
);';

if (mysqli_query($conexao, $sql)) {
    echo "<p style='color: #00d9ff;'>✓ Tabela 'artista' criada com sucesso!</p>";
} else {
    echo "<p style='color: #ff4444;'>✗ Erro ao criar tabela 'artista': " . mysqli_error($conexao) . "</p>";
}

// Criar tabela musica
echo "<p>Criando tabela 'musica'...</p>";
$sql = 'CREATE TABLE IF NOT EXISTS musica(
musica_id INT PRIMARY KEY AUTO_INCREMENT,
musica_titulo VARCHAR(100) NOT NULL,
musica_capa VARCHAR(255),
musica_link VARCHAR(255),
musica_artista INT,
musica_data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_musica_artista
FOREIGN KEY (musica_artista) 
REFERENCES artista(artista_id)
ON DELETE CASCADE
);';

if (mysqli_query($conexao, $sql)) {
    echo "<p style='color: #00d9ff;'>✓ Tabela 'musica' criada com sucesso!</p>";
} else {
    echo "<p style='color: #ff4444;'>✗ Erro ao criar tabela 'musica': " . mysqli_error($conexao) . "</p>";
}

// Verificar e adicionar coluna de data se não existir
echo "<p>Verificando coluna 'musica_data_adicao'...</p>";
$sql_check = "SHOW COLUMNS FROM musica LIKE 'musica_data_adicao'";
$result = mysqli_query($conexao, $sql_check);
if (mysqli_num_rows($result) == 0) {
    echo "<p>Adicionando coluna 'musica_data_adicao'...</p>";
    $sql_alter = "ALTER TABLE musica ADD COLUMN musica_data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if (mysqli_query($conexao, $sql_alter)) {
        echo "<p style='color: #00d9ff;'>✓ Coluna 'musica_data_adicao' adicionada!</p>";
    } else {
        echo "<p style='color: #ff4444;'>✗ Erro ao adicionar coluna: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: #00d9ff;'>✓ Coluna 'musica_data_adicao' já existe!</p>";
}

// Criar tabela curtidas
echo "<p>Criando tabela 'curtidas'...</p>";
$sql = 'CREATE TABLE IF NOT EXISTS curtidas(
curtida_id INT PRIMARY KEY AUTO_INCREMENT,
musica_id INT NOT NULL,
ip_usuario VARCHAR(45) NOT NULL,
tipo_curtida ENUM("curtida", "descurtida") NOT NULL,
data_curtida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_curtida_musica
FOREIGN KEY (musica_id) 
REFERENCES musica(musica_id)
ON DELETE CASCADE,
UNIQUE KEY unique_user_music (musica_id, ip_usuario)
);';

if (mysqli_query($conexao, $sql)) {
    echo "<p style='color: #00d9ff;'>✓ Tabela 'curtidas' criada com sucesso!</p>";
} else {
    echo "<p style='color: #ff4444;'>✗ Erro ao criar tabela 'curtidas': " . mysqli_error($conexao) . "</p>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<h3 style='color: #ffd700;'>Banco de dados configurado com sucesso!</h3>";
echo "<p>Você pode agora usar o sistema normalmente.</p>";
echo "<a href='admin.php' style='background: linear-gradient(135deg, #ffd700, #ffed4e); color: #0d1117; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ir para Admin</a>";
echo "</div>";
echo "</div>";
?>