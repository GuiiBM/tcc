<?php
// CRUD de categorias (gêneros e humores) - somente admin.
// POST acao=salvar (categoria_id opcional, nome, tipo, cor) | acao=excluir (categoria_id)
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();
if (!ehAdmin()) {
    jsonErro('Acesso restrito ao administrador', 403);
}

$dados = dadosRequisicao();

if (($dados['acao'] ?? '') === 'excluir') {
    executar($conexao, "DELETE FROM categoria WHERE categoria_id = ?", "i", [(int) ($dados['categoria_id'] ?? 0)]);
    jsonResposta(['success' => true, 'message' => 'Categoria excluída']);
}

if (($dados['acao'] ?? '') !== 'salvar') {
    jsonErro('Ação inválida');
}

$nome = trim($dados['nome'] ?? '');
$tipo = ($dados['tipo'] ?? '') === 'humor' ? 'humor' : 'genero';
$cor = preg_match('/^#[0-9a-fA-F]{6}$/', $dados['cor'] ?? '') ? $dados['cor'] : '#d4af37';
if ($nome === '' || mb_strlen($nome) > 60) {
    jsonErro('Informe um nome com até 60 caracteres');
}

$id = (int) ($dados['categoria_id'] ?? 0);
$repetida = consultarUm($conexao, "SELECT categoria_id FROM categoria WHERE categoria_nome = ? AND categoria_id <> ?", "si", [$nome, $id]);
if ($repetida) {
    jsonErro('Já existe uma categoria com esse nome');
}

if ($id) {
    executar($conexao, "UPDATE categoria SET categoria_nome = ?, categoria_tipo = ?, categoria_cor = ? WHERE categoria_id = ?", "sssi", [$nome, $tipo, $cor, $id]);
    jsonResposta(['success' => true, 'message' => 'Categoria atualizada']);
}
$ordem = consultarUm($conexao, "SELECT COALESCE(MAX(categoria_ordem), 0) + 1 AS proxima FROM categoria");
executar($conexao, "INSERT INTO categoria (categoria_nome, categoria_tipo, categoria_cor, categoria_ordem) VALUES (?, ?, ?, ?)", "sssi", [$nome, $tipo, $cor, $ordem['proxima']]);
jsonResposta(['success' => true, 'message' => 'Categoria criada']);
