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
CONSTRAINT fk_musica_artista
FOREIGN KEY (musica_artista) 
REFERENCES artista(artista_id)
ON DELETE CASCADE
);';

if (!mysqli_query($conexao, $sql)) {
    error_log("Erro ao criar tabela musica: " . mysqli_error($conexao));
}
?>