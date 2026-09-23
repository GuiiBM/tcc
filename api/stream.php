<?php
// Renova o link temporário de áudio de uma faixa (o player chama quando o
// link anterior expirou). GET ?tipo=musica|episodio&id=N
include __DIR__ . '/../Componentes/paginas/php/app.php';

$id = (int) ($_GET['id'] ?? 0);
if (($_GET['tipo'] ?? '') === 'episodio') {
    $linha = consultarUm($conexao, "SELECT e.*, p.podcast_titulo, p.podcast_capa FROM episodio e INNER JOIN podcast p ON p.podcast_id = e.podcast_id WHERE e.episodio_id = ?", "i", [$id]);
    $faixa = $linha ? episodioParaArray($linha) : null;
} else {
    $linha = consultarFaixas($conexao, "WHERE m.musica_id = ?", "i", [$id])[0] ?? null;
    $faixa = $linha ? faixaParaArray($linha) : null;
}
if (!$faixa) {
    jsonErro('Faixa não encontrada', 404);
}
jsonResposta(['success' => true, 'src' => $faixa['src'], 'srcLow' => $faixa['srcLow']]);
