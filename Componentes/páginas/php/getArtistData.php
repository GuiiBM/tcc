<?php
include "DBConection.php";

header('Content-Type: application/json');

try {
    if (isset($_POST['artist'])) {
        $artistName = trim($_POST['artist']);
        
        // Buscar dados do artista
        $artistStmt = mysqli_prepare($conexao, "SELECT artista_nome, artista_cidade, artista_image, artista_descricao FROM artista WHERE artista_nome = ?");
        mysqli_stmt_bind_param($artistStmt, "s", $artistName);
        mysqli_stmt_execute($artistStmt);
        $artistResult = mysqli_stmt_get_result($artistStmt);
        $artist = mysqli_fetch_assoc($artistResult);
        
        if ($artist) {
            // Corrigir caminho da imagem se necessário
            $imagePath = $artist['artista_image'];
            if ($imagePath && !str_starts_with($imagePath, 'http')) {
                // Se não é URL completa, garantir que o caminho está correto
                if (!str_starts_with($imagePath, 'Componentes/')) {
                    $imagePath = 'Componentes/Armazenamento/imagens/' . basename($imagePath);
                }
            }
            
            // Debug: verificar dados do artista
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/tcc/' . $imagePath;
            $debug = [
                'artist_data' => $artist,
                'original_path' => $artist['artista_image'],
                'corrected_path' => $imagePath,
                'full_server_path' => $fullPath,
                'image_exists' => $imagePath ? file_exists($fullPath) : false
            ];
            
            // Buscar músicas do artista
            $songsStmt = mysqli_prepare($conexao, "SELECT m.musica_id, m.musica_titulo, m.musica_link, m.musica_capa FROM musica m JOIN artista a ON m.musica_artista = a.artista_id WHERE a.artista_nome = ?");
            mysqli_stmt_bind_param($songsStmt, "s", $artistName);
            mysqli_stmt_execute($songsStmt);
            $songsResult = mysqli_stmt_get_result($songsStmt);
            
            $songs = [];
            while ($song = mysqli_fetch_assoc($songsResult)) {
                $duracao = 'Carregando...';
                
                $songs[] = [
                    'id' => $song['musica_id'],
                    'titulo' => $song['musica_titulo'],
                    'audio' => $song['musica_link'],
                    'imagem' => $song['musica_capa'],
                    'duracao' => $duracao,
                    'audioPath' => $song['musica_link']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'artist' => [
                    'nome' => $artist['artista_nome'],
                    'cidade' => $artist['artista_cidade'],
                    'imagem' => $imagePath,
                    'descricao' => $artist['artista_descricao'] ?: 'Descrição não disponível',
                    'link' => '#'
                ],
                'songs' => $songs,
                'debug' => $debug
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Artista não encontrado']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Nome do artista não fornecido']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>