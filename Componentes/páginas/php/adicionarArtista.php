<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "DBConection.php";
include "verificar_login.php";

header('Content-Type: application/json');

if (!verificarAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$nome = trim($_POST['artistName'] ?? '');
$cidade = trim($_POST['artistCity'] ?? '');
$descricao = trim($_POST['artistDescription'] ?? '');
$link = trim($_POST['artistLink'] ?? '');

if (empty($nome) || empty($cidade) || empty($descricao)) {
    echo json_encode(['success' => false, 'message' => 'Nome, cidade e descrição são obrigatórios']);
    exit;
}

if (strlen($descricao) > 512) {
    echo json_encode(['success' => false, 'message' => 'Descrição deve ter no máximo 512 caracteres']);
    exit;
}

$palavras = array_filter(explode(' ', trim($descricao)));
if (count($palavras) < 8) {
    echo json_encode(['success' => false, 'message' => 'Descrição deve ter pelo menos 8 palavras']);
    exit;
}

if (!isset($_FILES['artistImage']) || $_FILES['artistImage']['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'Imagem é obrigatória']);
    exit;
}

// Upload da imagem
$extensao = pathinfo($_FILES['artistImage']['name'], PATHINFO_EXTENSION);
$nomeArquivo = md5(uniqid()) . '.' . $extensao;
$caminhoDestino = '../../../Componentes/Armazenamento/imagens/' . $nomeArquivo;

if (!move_uploaded_file($_FILES['artistImage']['tmp_name'], $caminhoDestino)) {
    echo json_encode(['success' => false, 'message' => 'Erro no upload da imagem']);
    exit;
}

$imagemPath = 'Componentes/Armazenamento/imagens/' . $nomeArquivo;

// Inserir artista
$stmt = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $nome, $cidade, $imagemPath);

if (mysqli_stmt_execute($stmt)) {
    $artista_id = mysqli_insert_id($conexao);
    
    // Criar usuário para o artista
    $email = strtolower(str_replace(' ', '', $nome)) . '@artista.local';
    $senha_temp = 'temp_' . substr(md5($artista_id . time()), 0, 8);
    $senha_hash = password_hash($senha_temp, PASSWORD_DEFAULT);
    
    $stmt_user = mysqli_prepare($conexao, "INSERT INTO usuarios (usuario_email, usuario_senha, usuario_nome, usuario_cidade, usuario_descricao, usuario_foto, artista_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_user, "ssssssi", $email, $senha_hash, $nome, $cidade, $descricao, $imagemPath, $artista_id);
    mysqli_stmt_execute($stmt_user);
    
    echo json_encode(['success' => true, 'message' => 'Artista adicionado com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar artista']);
}
?>