<?php
include "DBConection.php";
include_once __DIR__ . "/url-helper.php";

header('Content-Type: application/json');

try {
    if (isset($_POST['artist_id'])) {
        $artistId = intval($_POST['artist_id']);

        // Buscar dados do artista
        $artistStmt = mysqli_prepare($conexao, "SELECT artista_nome, artista_cidade, artista_image, artista_descricao, artista_link FROM artista WHERE artista_id = ?");
        mysqli_stmt_bind_param($artistStmt, "i", $artistId);
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
            $fullPath = getProjectRoot() . '/' . $imagePath;
            $debug = [
                'artist_data' => $artist,
                'original_path' => $artist['artista_image'],
                'corrected_path' => $imagePath,
                'full_server_path' => $fullPath,
                'image_exists' => $imagePath ? file_exists($fullPath) : false
            ];
            
            // Buscar músicas do artista
            $songsStmt = mysqli_prepare($conexao, "SELECT musica_id, musica_titulo, musica_link, musica_capa FROM musica WHERE musica_artista = ?");
            mysqli_stmt_bind_param($songsStmt, "i", $artistId);
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
                    'link' => $artist['artista_link'] ?: null
                ],
                'songs' => $songs,
                'debug' => $debug
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Artista não encontrado']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID do artista não fornecido']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>