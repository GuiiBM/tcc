<?php
require_once __DIR__ . '/seguranca.php';
include "DBConection.php";
include_once __DIR__ . "/url-helper.php";

echo "<div style='max-width: 800px; margin: 50px auto; padding: 20px; background: rgba(22, 27, 34, 0.9); border-radius: 16px; color: var(--text-primary);'>";
echo "<h2 style='color: var(--accent); text-align: center; margin-bottom: 30px;'>Inicializando Banco de Dados</h2>";

// Cria/atualiza todas as tabelas (mesmas migrações que rodam sozinhas no
// primeiro acesso ao site - ver migracoes.php).
include_once __DIR__ . "/migracoes.php";
foreach (executarMigracoes($conexao) as [$status, $mensagem]) {
    $cor = $status === 'ok' ? 'var(--accent-info)' : 'var(--danger)';
    echo "<p style='color: $cor;'>" . ($status === 'ok' ? '✓' : '✗') . ' ' . htmlspecialchars($mensagem) . "</p>";
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
        echo "<p style='color: var(--accent-info);'>✓ Migração para usuario_id concluída!</p>";
    } else {
        echo "<p style='color: var(--danger);'>✗ Erro na migração: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: var(--accent-info);'>✓ Tabela já usa usuario_id!</p>";
}

// Verificar e adicionar coluna artista_id se não existir
echo "<p>Verificando coluna 'artista_id' na tabela usuarios...</p>";
$sql_check = "SHOW COLUMNS FROM usuarios LIKE 'artista_id'";
$result = mysqli_query($conexao, $sql_check);
if (mysqli_num_rows($result) == 0) {
    echo "<p>Adicionando coluna 'artista_id'...</p>";
    $sql_alter = "ALTER TABLE usuarios ADD COLUMN artista_id INT, ADD CONSTRAINT fk_usuario_artista FOREIGN KEY (artista_id) REFERENCES artista(artista_id) ON DELETE SET NULL";
    if (mysqli_query($conexao, $sql_alter)) {
        echo "<p style='color: var(--accent-info);'>✓ Coluna 'artista_id' adicionada!</p>";
    } else {
        echo "<p style='color: var(--danger);'>✗ Erro ao adicionar coluna: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: var(--accent-info);'>✓ Coluna 'artista_id' já existe!</p>";
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
            
            echo "<p style='color: var(--accent-info);'>✓ Perfil de artista criado para: " . $usuario['usuario_nome'] . "</p>";
        }
    }
} else {
    echo "<p style='color: var(--accent-info);'>✓ Todos os usuários já possuem perfil de artista!</p>";
}

// Verificar usuários do Google sem senha
echo "<p>Verificando usuários do Google sem senha...</p>";
$sql_google_users = "SELECT usuario_id, usuario_nome FROM usuarios WHERE usuario_senha = '' OR usuario_senha IS NULL";
$result_google = mysqli_query($conexao, $sql_google_users);

if (mysqli_num_rows($result_google) > 0) {
    echo "<p>Gerando senhas temporárias para usuários do Google...</p>";
    while ($usuario = mysqli_fetch_assoc($result_google)) {
        $senha_temporaria = 'temp_' . substr(md5($usuario['usuario_id'] . time()), 0, 8);
        $senha_hash = hashSenha($senha_temporaria);
        
        $stmt_senha = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_senha = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt_senha, "si", $senha_hash, $usuario['usuario_id']);
        
        if (mysqli_stmt_execute($stmt_senha)) {
            echo "<p style='color: var(--accent-info);'>✓ Senha temporária criada para: " . htmlspecialchars($usuario['usuario_nome']) . "</p>";
        }
    }
} else {
    echo "<p style='color: var(--accent-info);'>✓ Todos os usuários já possuem senha!</p>";
}

// Migrar propagandas existentes
echo "<p>Migrando propagandas existentes...</p>";
$uploadDir = getArmazenamentoPath('propaganda');
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
            echo "<p style='color: var(--accent-info);'>✓ Propaganda migrada: $imageName</p>";
            $ordem++;
        }
    }
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<h3 style='color: var(--accent);'>Banco de dados configurado com sucesso!</h3>";
echo "<p>Sistema completo com usuários, artistas, visualizações e propagandas ordenáveis.</p>";
echo "<a href='admin.php' style='background: var(--accent); color: var(--bg-canvas); padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ir para Menu</a>";
echo "</div>";
echo "</div>";
?>