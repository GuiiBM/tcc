<?php
include "DBConection.php";

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

// Criar tabela usuarios
echo "<p>Criando tabela 'usuarios'...</p>";
$sql = 'CREATE TABLE IF NOT EXISTS usuarios(
usuario_id INT PRIMARY KEY AUTO_INCREMENT,
usuario_email VARCHAR(255) UNIQUE NOT NULL,
usuario_senha VARCHAR(255) NOT NULL,
usuario_nome VARCHAR(100) NOT NULL,
usuario_idade INT,
usuario_cidade VARCHAR(100),
usuario_descricao TEXT,
usuario_foto VARCHAR(255),
usuario_tipo ENUM("admin", "usuario") DEFAULT "usuario",
usuario_data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
artista_id INT,
CONSTRAINT fk_usuario_artista
FOREIGN KEY (artista_id) 
REFERENCES artista(artista_id)
ON DELETE SET NULL
);';

if (mysqli_query($conexao, $sql)) {
    echo "<p style='color: #00d9ff;'>✓ Tabela 'usuarios' criada com sucesso!</p>";
} else {
    echo "<p style='color: #ff4444;'>✗ Erro ao criar tabela 'usuarios': " . mysqli_error($conexao) . "</p>";
}

// Criar tabela curtidas
echo "<p>Criando tabela 'curtidas'...</p>";
$sql = 'CREATE TABLE IF NOT EXISTS curtidas(
curtida_id INT PRIMARY KEY AUTO_INCREMENT,
musica_id INT NOT NULL,
usuario_id INT NOT NULL,
tipo_curtida ENUM("curtida", "descurtida") NOT NULL,
data_curtida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_curtida_musica
FOREIGN KEY (musica_id) 
REFERENCES musica(musica_id)
ON DELETE CASCADE,
CONSTRAINT fk_curtida_usuario
FOREIGN KEY (usuario_id)
REFERENCES usuarios(usuario_id)
ON DELETE CASCADE,
UNIQUE KEY unique_user_music (musica_id, usuario_id)
);';

if (mysqli_query($conexao, $sql)) {
    echo "<p style='color: #00d9ff;'>✓ Tabela 'curtidas' criada com sucesso!</p>";
} else {
    echo "<p style='color: #ff4444;'>✗ Erro ao criar tabela 'curtidas': " . mysqli_error($conexao) . "</p>";
}

// Migrar para usuario_id se necessário
echo "<p>Verificando migração para usuario_id...</p>";
$sql_check = "SHOW COLUMNS FROM curtidas LIKE 'session_id'";
$result = mysqli_query($conexao, $sql_check);
if (mysqli_num_rows($result) > 0) {
    echo "<p>Limpando curtidas antigas e migrando para usuario_id...</p>";
    mysqli_query($conexao, "TRUNCATE TABLE curtidas");
    $sql_migrate = "ALTER TABLE curtidas CHANGE session_id usuario_id INT NOT NULL";
    if (mysqli_query($conexao, $sql_migrate)) {
        $sql_update_constraint = "ALTER TABLE curtidas DROP INDEX unique_session_music, ADD UNIQUE KEY unique_user_music (musica_id, usuario_id), ADD CONSTRAINT fk_curtida_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE";
        mysqli_query($conexao, $sql_update_constraint);
        echo "<p style='color: #00d9ff;'>✓ Migração para usuario_id concluída!</p>";
    } else {
        echo "<p style='color: #ff4444;'>✗ Erro na migração: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: #00d9ff;'>✓ Tabela já usa usuario_id!</p>";
}

// Verificar e adicionar coluna artista_id se não existir
echo "<p>Verificando coluna 'artista_id' na tabela usuarios...</p>";
$sql_check = "SHOW COLUMNS FROM usuarios LIKE 'artista_id'";
$result = mysqli_query($conexao, $sql_check);
if (mysqli_num_rows($result) == 0) {
    echo "<p>Adicionando coluna 'artista_id'...</p>";
    $sql_alter = "ALTER TABLE usuarios ADD COLUMN artista_id INT, ADD CONSTRAINT fk_usuario_artista FOREIGN KEY (artista_id) REFERENCES artista(artista_id) ON DELETE SET NULL";
    if (mysqli_query($conexao, $sql_alter)) {
        echo "<p style='color: #00d9ff;'>✓ Coluna 'artista_id' adicionada!</p>";
    } else {
        echo "<p style='color: #ff4444;'>✗ Erro ao adicionar coluna: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: #00d9ff;'>✓ Coluna 'artista_id' já existe!</p>";
}

// Migrar usuários existentes para artistas
echo "<p>Verificando usuários sem perfil de artista...</p>";
$sql_usuarios_sem_artista = "SELECT u.usuario_id, u.usuario_nome, u.usuario_cidade, u.usuario_foto FROM usuarios u WHERE u.artista_id IS NULL";
$result_usuarios = mysqli_query($conexao, $sql_usuarios_sem_artista);

if (mysqli_num_rows($result_usuarios) > 0) {
    echo "<p>Criando perfis de artista para usuários existentes...</p>";
    while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
        // Criar artista para o usuário
        $stmt_artista = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image) VALUES (?, ?, ?)");
        $foto_artista = $usuario['usuario_foto'] ?: 'Componentes/icones/icone.png';
        mysqli_stmt_bind_param($stmt_artista, "sss", $usuario['usuario_nome'], $usuario['usuario_cidade'], $foto_artista);
        
        if (mysqli_stmt_execute($stmt_artista)) {
            $artista_id = mysqli_insert_id($conexao);
            
            // Vincular usuário ao artista
            $stmt_update = mysqli_prepare($conexao, "UPDATE usuarios SET artista_id = ? WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt_update, "ii", $artista_id, $usuario['usuario_id']);
            mysqli_stmt_execute($stmt_update);
            
            echo "<p style='color: #00d9ff;'>✓ Perfil de artista criado para: " . $usuario['usuario_nome'] . "</p>";
        }
    }
} else {
    echo "<p style='color: #00d9ff;'>✓ Todos os usuários já possuem perfil de artista!</p>";
}

// Verificar usuários do Google sem senha
echo "<p>Verificando usuários do Google sem senha...</p>";
$sql_google_users = "SELECT usuario_id, usuario_nome FROM usuarios WHERE usuario_senha = '' OR usuario_senha IS NULL";
$result_google = mysqli_query($conexao, $sql_google_users);

if (mysqli_num_rows($result_google) > 0) {
    echo "<p>Gerando senhas temporárias para usuários do Google...</p>";
    while ($usuario = mysqli_fetch_assoc($result_google)) {
        $senha_temporaria = 'temp_' . substr(md5($usuario['usuario_id'] . time()), 0, 8);
        $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
        
        $stmt_senha = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_senha = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt_senha, "si", $senha_hash, $usuario['usuario_id']);
        
        if (mysqli_stmt_execute($stmt_senha)) {
            echo "<p style='color: #00d9ff;'>✓ Senha temporária criada para: " . $usuario['usuario_nome'] . " (Senha: $senha_temporaria)</p>";
        }
    }
} else {
    echo "<p style='color: #00d9ff;'>✓ Todos os usuários já possuem senha!</p>";
}

// Criar tabela visualizacoes
echo "<p>Criando tabela 'visualizacoes'...</p>";
$sql = 'CREATE TABLE IF NOT EXISTS visualizacoes(
visualizacao_id INT PRIMARY KEY AUTO_INCREMENT,
musica_id INT NOT NULL,
ip_usuario VARCHAR(45) NOT NULL,
data_visualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_visualizacao_musica
FOREIGN KEY (musica_id) 
REFERENCES musica(musica_id)
ON DELETE CASCADE
);';

if (mysqli_query($conexao, $sql)) {
    echo "<p style='color: #00d9ff;'>✓ Tabela 'visualizacoes' criada com sucesso!</p>";
} else {
    echo "<p style='color: #ff4444;'>✗ Erro ao criar tabela 'visualizacoes': " . mysqli_error($conexao) . "</p>";
}

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
    $check = mysqli_prepare($conexao, "SELECT propaganda_id FROM propagandas WHERE propaganda_nome = ?");
    mysqli_stmt_bind_param($check, "s", $imageName);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);
    
    if (mysqli_num_rows($result) == 0) {
        $stmt = mysqli_prepare($conexao, "INSERT INTO propagandas (propaganda_nome, propaganda_ordem) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $imageName, $ordem);
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: #00d9ff;'>✓ Propaganda migrada: $imageName</p>";
            $ordem++;
        }
    }
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<h3 style='color: #ffd700;'>Banco de dados configurado com sucesso!</h3>";
echo "<p>Sistema completo com usuários, artistas, visualizações e propagandas ordenáveis.</p>";
echo "<a href='admin.php' style='background: linear-gradient(135deg, #ffd700, #ffed4e); color: #0d1117; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ir para Menu</a>";
echo "</div>";
echo "</div>";
?>