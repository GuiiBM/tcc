<?php
$sql = 'CREATE TABLE IF NOT EXISTS artista(
artista_id INT PRIMARY KEY AUTO_INCREMENT,
artista_nome VARCHAR(50) NOT NULL,
artista_pais VARCHAR(50),
artista_image VARCHAR(255)
);';

$results = mysqli_query($conexao, $sql);

$sql = 'CREATE TABLE IF NOT EXISTS musica(
musica_id INT PRIMARY KEY AUTO_INCREMENT,
musica_titulo VARCHAR(50) NOT NULL,
musica_capa VARCHAR(255),
musica_link VARCHAR(255),
musica_artista INT,
CONSTRAINT
FOREIGN KEY (musica_artista) 
REFERENCES artista(artista_id)
);';

$results = mysqli_query($conexao, $sql);
?>