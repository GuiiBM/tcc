<?php
// Registra uma reprodução iniciada (usada nos rankings "Em alta").
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();

$musicaId = (int) (dadosRequisicao()['musica_id'] ?? 0);
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip = substr($ip, 0, 45);

if (!consultarUm($conexao, "SELECT musica_id FROM musica WHERE musica_id = ?", "i", [$musicaId])) {
    jsonErro('Música não encontrada', 404);
}

// Ignora repetições do mesmo IP em 30 segundos (evita inflar a contagem).
$recente = consultarUm($conexao, "SELECT visualizacao_id FROM visualizacoes WHERE musica_id = ? AND ip_usuario = ? AND data_visualizacao > DATE_SUB(NOW(), INTERVAL 30 SECOND)", "is", [$musicaId, $ip]);
if (!$recente) {
    executar($conexao, "INSERT INTO visualizacoes (musica_id, ip_usuario) VALUES (?, ?)", "is", [$musicaId, $ip]);
}
jsonResposta(['success' => true]);
