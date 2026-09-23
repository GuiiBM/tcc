<?php
// Letra de uma música (texto simples ou formato LRC sincronizado).
include __DIR__ . '/../Componentes/paginas/php/app.php';

$linha = consultarUm($conexao, "SELECT musica_letra FROM musica WHERE musica_id = ?", "i", [(int) ($_GET['id'] ?? 0)]);
if (!$linha) {
    jsonErro('Música não encontrada', 404);
}
jsonResposta(['success' => true, 'letra' => $linha['musica_letra'] ?? '']);
