<?php
// Seguir / deixar de seguir um artista. POST {artista_id}
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();
exigirLoginApi();

$artistaId = (int) (dadosRequisicao()['artista_id'] ?? 0);
$usuarioId = (int) $_SESSION['usuario_id'];

if (!consultarUm($conexao, "SELECT artista_id FROM artista WHERE artista_id = ?", "i", [$artistaId])) {
    jsonErro('Artista não encontrado', 404);
}

$segue = consultarUm($conexao, "SELECT 1 FROM seguidores WHERE usuario_id = ? AND artista_id = ?", "ii", [$usuarioId, $artistaId]);
if ($segue) {
    executar($conexao, "DELETE FROM seguidores WHERE usuario_id = ? AND artista_id = ?", "ii", [$usuarioId, $artistaId]);
} else {
    executar($conexao, "INSERT INTO seguidores (usuario_id, artista_id) VALUES (?, ?)", "ii", [$usuarioId, $artistaId]);
}
$total = consultarUm($conexao, "SELECT COUNT(*) AS total FROM seguidores WHERE artista_id = ?", "i", [$artistaId]);

jsonResposta(['success' => true, 'seguindo' => !$segue, 'seguidores' => (int) $total['total']]);
