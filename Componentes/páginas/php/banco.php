<?php
$sql = 'CREATE TABLE IF NOT EXISTS artista(
artista_id INT PRIMARY KEY AUTO_INCREMENT,
artista_nome VARCHAR(100) NOT NULL,
artista_cidade VARCHAR(100),
artista_image VARCHAR(255)
);';

if (!mysqli_query($conexao, $sql)) {
    error_log("Erro ao criar tabela artista: " . mysqli_error($conexao));
}

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

if (!mysqli_query($conexao, $sql)) {
    error_log("Erro ao criar tabela musica: " . mysqli_error($conexao));
}

// Adicionar coluna de data se não existir
$sql_check = "SHOW COLUMNS FROM musica LIKE 'musica_data_adicao'";
$result = mysqli_query($conexao, $sql_check);
if (mysqli_num_rows($result) == 0) {
    $sql_alter = "ALTER TABLE musica ADD COLUMN musica_data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    mysqli_query($conexao, $sql_alter);
}

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

if (!mysqli_query($conexao, $sql)) {
    error_log("Erro ao criar tabela curtidas: " . mysqli_error($conexao));
}
?>