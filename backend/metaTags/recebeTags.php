<?php
/**
 * ARQUIVO: recebeTags.php
 * FUNÇÃO: Atualiza SEO de Produtos e cria/associa Tags (com filtro por categoria)
 */

// 1. CONFIGURAÇÕES DE FLUXO E SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Desativa limites de tempo para processamentos longos
set_time_limit(0); 
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "../conexao.php";

// 2. COLETA E VALIDAÇÃO DE PARÂMETROS
$cidades   = json_decode($_GET['cidades']   ?? '[]', true);
$telefones = json_decode($_GET['telefones'] ?? '[]', true);
$id_cats   = json_decode($_GET['id_categorias'] ?? '[]', true); // IDs vindos do Modal

// 3. CONSTRUÇÃO DO FILTRO SQL (WHERE)
$where = "";
if (!empty($id_cats)) {
    $filtros = [];
    foreach($id_cats as $id) {
        // FIND_IN_SET é ideal pois prodcatids armazena listas como "12,45,100"
        $filtros[] = "FIND_IN_SET(".(int)$id.", prodcatids)";
    }
    $where = " WHERE " . implode(" OR ", $filtros);
}

// 4. CACHE DE CATEGORIAS (Para buscar Descrição do "Pai")
$cats = [];
$res = $con->query("SELECT categoryid, catparentid, catmetadesc FROM isc_categories");
while ($r = $res->fetch_assoc()) {
    $cats[(int)$r['categoryid']] = [
      'parent'      => (int)$r['catparentid'],
      'description' => $r['catmetadesc']
    ];
}

/**
 * Busca recursiva da descrição da categoria raiz (parent = 0)
 */
function getMetaDesc(int $cid, array $cats): string {
    if (!isset($cats[$cid])) return '';
    while (isset($cats[$cid]) && $cats[$cid]['parent'] !== 0) {
        $cid = $cats[$cid]['parent'];
    }
    return $cats[$cid]['description'] ?? '';
}

// 5. PREPARAÇÃO DOS MOTORES SQL (Prepared Statements)
// Fora do loop para ganhar velocidade (até 10x mais rápido)
$updProd = $con->prepare("UPDATE isc_products SET prodmetadesc=?, prodpagetitle=?, prodsearchkeywords=?, prodmetakeywords=? WHERE productid=?");

$updSrch = $con->prepare("INSERT INTO isc_product_search (productid, prodname, prodsearchkeywords) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE prodsearchkeywords=VALUES(prodsearchkeywords)");

$updTag  = $con->prepare("INSERT INTO isc_product_tags (tagname, tagfriendlyname, tagcount) 
                          VALUES (?, ?, 0) 
                          ON DUPLICATE KEY UPDATE tagid=LAST_INSERT_ID(tagid)");

$updAssoc = $con->prepare("INSERT IGNORE INTO isc_product_tagassociations (tagid, productid) VALUES (?, ?)");

// 6. BUSCA DE PRODUTOS E INÍCIO DO LOOP SSE
$prods = $con->query("SELECT productid, prodname, prodcatids FROM isc_products" . $where);
$total = $prods->num_rows;
$atual = 0;

// Caso não haja produtos no filtro
if ($total == 0) {
    echo "event: complete\ndata: 100\n\n";
    exit;
}

while ($prod = $prods->fetch_assoc()) {
    $atual++;
    
    $productId = (int)$prod['productid'];
    $nome      = $prod['prodname'];
    
    // Identifica a categoria principal para buscar a Meta Description
    $catIdsArr = array_filter(explode(',', $prod['prodcatids']), fn($v)=>ctype_digit($v));
    $catPrincipal = $catIdsArr ? (int)reset($catIdsArr) : 0;
    $metaDesc = getMetaDesc($catPrincipal, $cats);

    // MONTAGEM DAS STRINGS SEO
    $titulo   = $nome . ' ' . implode(' ', $telefones) . ' ' . implode(' ', $cidades);
    $keywords = $nome . ', ' . implode(', ', array_map(fn($c)=>"$nome $c", $cidades));
    $friendly = mb_strtolower(str_replace(' ', '-', $keywords), 'UTF-8');

    // EXECUÇÃO DAS ATUALIZAÇÕES
    // A) Update no Produto
    $updProd->bind_param("ssssi", $metaDesc, $titulo, $keywords, $keywords, $productId);
    $updProd->execute();

    // B) Upsert na Busca
    $updSrch->bind_param("iss", $productId, $nome, $keywords);
    $updSrch->execute();

    // C) Upsert na Tag (Garante existência e captura ID)
    $updTag->bind_param("ss", $keywords, $friendly);
    $updTag->execute();
    $tagId = $con->insert_id;

    // Caso o insert_id falhe por já existir sem alteração, fazemos um fallback
    if ($tagId == 0) {
        $resTag = $con->query("SELECT tagid FROM isc_product_tags WHERE tagname = '" . $con->real_escape_string($keywords) . "'");
        $tagId = $resTag->fetch_assoc()['tagid'] ?? 0;
    }

    // D) Associação Tag <-> Produto
    if ($tagId > 0) {
        $updAssoc->bind_param("ii", $tagId, $productId);
        $updAssoc->execute();
    }

    // 7. ENVIO DE PROGRESSO AO FRONTEND (A cada 5 itens para fluidez)
    if ($atual % 5 == 0 || $atual == $total) {
        $percent = intval(($atual / $total) * 100);
        echo "event: progress\ndata: {$percent}\n\n";
        
        // Força a saída do buffer
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
}

// 8. FINALIZAÇÃO
echo "event: complete\ndata: 100\n\n";

// Fecha statements e conexão
$updProd->close();
$updSrch->close();
$updTag->close();
$updAssoc->close();
$con->close();