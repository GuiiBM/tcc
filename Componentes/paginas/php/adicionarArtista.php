<?php
require_once __DIR__ . '/seguranca.php';
iniciarSessaoSegura();

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

// Upload da imagem: extensão sempre derivada do tipo real do arquivo,
// nunca do nome enviado pelo cliente (Content-Type/extensão são falsificáveis)
$extensoesPermitidas = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];
$tipoReal = mime_content_type($_FILES['artistImage']['tmp_name']);
if (!isset($extensoesPermitidas[$tipoReal])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de imagem não permitido']);
    exit;
}

$nomeArquivo = md5(uniqid()) . '.' . $extensoesPermitidas[$tipoReal];
$caminhoDestino = '../../../Componentes/Armazenamento/imagens/' . $nomeArquivo;

if (!move_uploaded_file($_FILES['artistImage']['tmp_name'], $caminhoDestino)) {
    echo json_encode(['success' => false, 'message' => 'Erro no upload da imagem']);
    exit;
}

$imagemPath = 'Componentes/Armazenamento/imagens/' . $nomeArquivo;

// Inserir artista
$stmt = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image, artista_descricao, artista_link) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sssss", $nome, $cidade, $imagemPath, $descricao, $link);

if (mysqli_stmt_execute($stmt)) {
    $artista_id = mysqli_insert_id($conexao);
    
    // Criar usuário para o artista
    $email = strtolower(str_replace(' ', '', $nome)) . '@artista.local';
    $senha_temp = 'temp_' . substr(md5($artista_id . time()), 0, 8);
    $senha_hash = hashSenha($senha_temp);
    
    $stmt_user = mysqli_prepare($conexao, "INSERT INTO usuarios (usuario_email, usuario_senha, usuario_nome, usuario_cidade, usuario_descricao, usuario_foto, artista_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_user, "ssssssi", $email, $senha_hash, $nome, $cidade, $descricao, $imagemPath, $artista_id);

    if (!mysqli_stmt_execute($stmt_user)) {
        // E-mail gerado colidiu com um já existente (ex.: nomes que normalizam igual);
        // usa o id do artista para garantir unicidade antes de desistir.
        $email = strtolower(str_replace(' ', '', $nome)) . $artista_id . '@artista.local';
        mysqli_stmt_bind_param($stmt_user, "ssssssi", $email, $senha_hash, $nome, $cidade, $descricao, $imagemPath, $artista_id);
        mysqli_stmt_execute($stmt_user);
    }

    echo json_encode(['success' => true, 'message' => 'Artista adicionado com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar artista']);
}
?>