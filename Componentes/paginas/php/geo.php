<?php
// geo.php - Localização de artistas/ouvintes e recomendações locais.
//
// A cidade digitada (ex: "Ubatuba", "Ubatuba - SP", "Rio Grande do Sul") é
// convertida em latitude/longitude usando a tabela `cidade` (todos os
// municípios e estados do Brasil, importados de Componentes/dados/).
//
// Lógica de recomendação do Ressonance: divulgar quem ainda não é ouvido.
// Quanto MAIS PERTO do ouvinte e MENOS reproduções a música tiver, mais ela
// aparece. Pontuação = proximidade × novidade × pequena variação aleatória
// (para revezar as músicas com pontuação parecida):
//   proximidade = 1 / (1 + distância_km / 50)   -> 0 km = 1; 50 km = 0,5; 500 km = 0,09
//   novidade    = 1 / √(1 + reproduções)        -> 0 plays = 1; 3 plays = 0,5; 15 plays = 0,25
// (a raiz evita que poucas reproduções a mais anulem a proximidade: uma
// música a 200 km com 12 plays ainda fica à frente de outra a 950 km com 1)
// Sem a localização do ouvinte, vale só a novidade.

function normalizarTexto($texto) {
    $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
    $texto = strtr($texto, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', "'" => ' ', '-' => ' ',
    ]);
    $texto = preg_replace('/[^a-z0-9 ]+/', ' ', $texto);
    return trim(preg_replace('/\s+/', ' ', $texto));
}

// Encontra a cidade (ou estado) de um texto livre. Retorna a linha ou null.
function localizarCidade($conexao, $texto) {
    $texto = trim((string) $texto);
    if ($texto === '') {
        return null;
    }
    $uf = null;
    // "Ubatuba - SP", "Ubatuba/SP", "Ubatuba, SP"
    if (preg_match('/^(.+?)\s*[-\/,]\s*([A-Za-z]{2})$/u', $texto, $m)) {
        $texto = $m[1];
        $uf = strtoupper($m[2]);
    }
    $busca = normalizarTexto($texto);
    if ($busca === '') {
        return null;
    }
    $sql = "SELECT * FROM cidade WHERE cidade_busca = ?" . ($uf ? " AND uf = ?" : "") . " ORDER BY cidade_tipo = 'cidade' DESC, capital DESC LIMIT 1";
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        return null;
    }
    if ($uf) {
        mysqli_stmt_bind_param($stmt, "ss", $busca, $uf);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $busca);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

// Atualiza as coordenadas de artistas e usuários cuja cidade mudou desde a
// última vez (vale para qualquer tela que altere a cidade, inclusive as
// antigas do admin). Roda antes de cada cálculo de recomendação; só
// processa quem mudou, então normalmente não faz nada.
function sincronizarGeo($conexao) {
    static $feito = false;
    if ($feito) {
        return;
    }
    $feito = true;
    $tabelas = [
        ['artista', 'artista_id', 'artista_cidade', 'artista_lat', 'artista_lon', 'artista_geo_cidade'],
        ['usuarios', 'usuario_id', 'usuario_cidade', 'usuario_lat', 'usuario_lon', 'usuario_geo_cidade'],
    ];
    foreach ($tabelas as [$tabela, $id, $cidade, $lat, $lon, $geo]) {
        $r = mysqli_query($conexao, "SELECT $id AS id, $cidade AS cidade FROM $tabela WHERE NOT ($geo <=> $cidade) LIMIT 200");
        if (!$r) {
            continue;
        }
        $stmt = mysqli_prepare($conexao, "UPDATE $tabela SET $lat = ?, $lon = ?, $geo = ? WHERE $id = ?");
        while ($linha = mysqli_fetch_assoc($r)) {
            $local = localizarCidade($conexao, $linha['cidade']);
            $la = $local ? (float) $local['latitude'] : null;
            $lo = $local ? (float) $local['longitude'] : null;
            $texto = $linha['cidade'];
            mysqli_stmt_bind_param($stmt, "ddsi", $la, $lo, $texto, $linha['id']);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Onde está quem está ouvindo:
// 1) localização do aparelho, se a pessoa permitiu (cookie rs_local, com
//    precisão reduzida a ~1 km e que nunca é gravada no banco);
// 2) cidade do perfil do usuário logado.
function localizacaoVisitante($conexao) {
    static $local = false;
    if ($local !== false) {
        return $local;
    }
    $local = null;
    if (preg_match('/^(-?\d{1,2}\.\d{1,4}),(-?\d{1,3}\.\d{1,4})$/', $_COOKIE['rs_local'] ?? '', $m)) {
        $lat = (float) $m[1];
        $lon = (float) $m[2];
        if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
            return $local = ['lat' => $lat, 'lon' => $lon, 'origem' => 'aparelho', 'rotulo' => 'sua localização atual'];
        }
    }
    if (!empty($_SESSION['usuario_id'])) {
        sincronizarGeo($conexao);
        $stmt = mysqli_prepare($conexao, "SELECT usuario_lat, usuario_lon, usuario_cidade FROM usuarios WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($u && $u['usuario_lat'] !== null) {
            return $local = ['lat' => (float) $u['usuario_lat'], 'lon' => (float) $u['usuario_lon'], 'origem' => 'perfil', 'rotulo' => $u['usuario_cidade']];
        }
    }
    return $local;
}

// Expressão SQL da distância (km, fórmula de Haversine) até o visitante.
function sqlDistancia($local, $latCol = 'a.artista_lat', $lonCol = 'a.artista_lon') {
    if (!$local) {
        return 'NULL';
    }
    $lat = (float) $local['lat'];
    $lon = (float) $local['lon'];
    return "(6371 * 2 * ASIN(SQRT(POW(SIN(RADIANS($latCol - $lat) / 2), 2) + COS(RADIANS($lat)) * COS(RADIANS($latCol)) * POW(SIN(RADIANS($lonCol - $lon) / 2), 2))))";
}

// Músicas recomendadas pela lógica do Ressonance (ver topo do arquivo).
// Opções: limite, categoria (id de gênero/humor), artista (id).
function consultarRecomendadas($conexao, $opcoes = []) {
    $o = array_merge(['limite' => 12, 'categoria' => null, 'artista' => null], $opcoes);
    sincronizarGeo($conexao);
    $local = localizacaoVisitante($conexao);
    $distancia = sqlDistancia($local);
    // Artista sem localização conhecida (cidade vazia ou fora do Brasil)
    // conta como distante (~2.500 km) quando o ouvinte tem localização.
    $proximidade = $local ? "COALESCE(1 / (1 + $distancia / 50), 0.02)" : '1';

    $filtros = [];
    $tipos = '';
    $parametros = [];
    $joinCategoria = '';
    if ($o['categoria']) {
        $joinCategoria = 'INNER JOIN musica_categoria mcr ON mcr.musica_id = m.musica_id';
        $filtros[] = 'mcr.categoria_id = ?';
        $tipos .= 'i';
        $parametros[] = (int) $o['categoria'];
    }
    if ($o['artista']) {
        $filtros[] = 'm.musica_artista = ?';
        $tipos .= 'i';
        $parametros[] = (int) $o['artista'];
    }
    $where = $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';
    $tipos .= 'i';
    $parametros[] = (int) $o['limite'];

    return consultarFaixas($conexao, "$joinCategoria
        LEFT JOIN (SELECT musica_id, COUNT(*) AS total FROM visualizacoes GROUP BY musica_id) vr ON vr.musica_id = m.musica_id
        $where
        ORDER BY ($proximidade / SQRT(1 + COALESCE(vr.total, 0))) * (0.9 + RAND() * 0.2) DESC
        LIMIT ?", $tipos, $parametros, "COALESCE(vr.total, 0) AS total_views, $distancia AS distancia_km");
}

// Texto curto explicando por que a música foi recomendada.
function motivoRecomendacao($linha) {
    $partes = [];
    if (isset($linha['distancia_km']) && $linha['distancia_km'] !== null) {
        $km = (float) $linha['distancia_km'];
        $partes[] = $km < 1 ? 'na sua cidade' : ($km < 10 ? 'a ' . number_format($km, 1, ',', '.') . ' km' : 'a ' . number_format($km, 0, ',', '.') . ' km');
    }
    $views = (int) ($linha['total_views'] ?? 0);
    $partes[] = $views === 0 ? 'nenhuma reprodução ainda' : pluralizar($views, 'reprodução', 'reproduções');
    return implode(' • ', $partes);
}

// Botão/aviso para usar a localização do aparelho nas recomendações.
function renderAvisoLocalizacao($conexao) {
    $local = localizacaoVisitante($conexao);
    $texto = $local
        ? 'Mostrando primeiro artistas perto de ' . e($local['rotulo']) . ' e com poucas reproduções.'
        : 'Mostrando primeiro as músicas com menos reproduções. Informe sua localização para ver artistas da sua região.';
    return '<div class="geo-note">' . icone('globe') . '<span>' . $texto . '</span>'
        . '<button type="button" class="btn-pill btn-ghost small" data-action="use-location">' . ($local && $local['origem'] === 'aparelho' ? 'Atualizar localização' : 'Usar minha localização') . '</button>'
        . ($local && $local['origem'] === 'aparelho' ? '<button type="button" class="btn-text" data-action="forget-location">Esquecer</button>' : '')
        . '</div>';
}
?>
