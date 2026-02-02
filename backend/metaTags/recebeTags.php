<?php
/**
 * ARQUIVO: recebeTags.php
 * FUNÇÃO: Atualiza SEO de Produtos, cria Tags e herda a Meta Description da Categoria Pai
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

set_time_limit(0); 
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "../conexao.php";
$con->set_charset("utf8mb4");

// 2. COLETA E VALIDAÇÃO DE PARÂMETROS
$cidades   = json_decode($_GET['cidades']   ?? '[]', true);
$telefones = json_decode($_GET['telefones'] ?? '[]', true);
$id_cats   = json_decode($_GET['id_categorias'] ?? '[]', true);

// 3. CONSTRUÇÃO DO FILTRO SQL
$where = "";
if (!empty($id_cats) && is_array($id_cats)) {
    $filtros = [];
    foreach($id_cats as $id) {
        $clean_id = (int)$id;
        if ($clean_id > 0) {
            $filtros[] = "FIND_IN_SET($clean_id, prodcatids)";
        }
    }
    if (!empty($filtros)) {
        $where = " WHERE (" . implode(" OR ", $filtros) . ")";
    }
}

// 4. CACHE DE CATEGORIAS (Mapeia a hierarquia e as descrições)
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
 * Isso garante que pegamos a descrição da categoria "Pai" de todas.
 */
function getMetaDesc(int $cid, array $cats): string {
    if (!isset($cats[$cid])) return '';
    
    $temp_cid = $cid;
    // Sobe na hierarquia até encontrar a categoria que tem parent 0
    while (isset($cats[$temp_cid]) && $cats[$temp_cid]['parent'] !== 0) {
        $temp_cid = $cats[$temp_cid]['parent'];
    }
    
    // Retorna a descrição encontrada na categoria raiz
    return $cats[$temp_cid]['description'] ?? '';
}

// 5. PREPARAÇÃO DOS MOTORES SQL
$updProd = $con->prepare("UPDATE isc_products SET prodmetadesc=?, prodpagetitle=?, prodsearchkeywords=?, prodmetakeywords=? WHERE productid=?");
$updSrch = $con->prepare("INSERT INTO isc_product_search (productid, prodname, prodsearchkeywords) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE prodsearchkeywords=VALUES(prodsearchkeywords)");
$updTag  = $con->prepare("INSERT INTO isc_product_tags (tagname, tagfriendlyname, tagcount) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE tagid=LAST_INSERT_ID(tagid)");
$updAssoc = $con->prepare("INSERT IGNORE INTO isc_product_tagassociations (tagid, productid) VALUES (?, ?)");

// 6. BUSCA DE PRODUTOS
$prods = $con->query("SELECT productid, prodname, prodcatids FROM isc_products" . $where);
$total = $prods->num_rows;
$atual = 0;

if ($total == 0) {
    echo "event: complete\ndata: 100\n\n";
    exit;
}

/**
 * Normaliza a URL removendo acentos e caracteres especiais
 */
function normalizarUrl($str) {
    $a = array('À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý','à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ');
    $b = array('A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','Y','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y');
    $str = str_replace($a, $b, $str);
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(' ', '-', $str);
    return preg_replace('/[^a-z0-9\-]/', '', $str);
}

while ($prod = $prods->fetch_assoc()) {
    $atual++;
    $productId = (int)$prod['productid'];
    $nome      = $prod['prodname'];
    
    // 7. LÓGICA DE HERANÇA DA META DESCRIPTION
    // Pega a lista de categorias do produto, extrai a primeira (principal)
    $catIdsArr = array_filter(explode(',', $prod['prodcatids']), fn($v)=>ctype_digit($v));
    $catPrincipal = $catIdsArr ? (int)reset($catIdsArr) : 0;
    
    // Busca a descrição da Categoria Raiz no cache
    $metaDescPai = getMetaDesc($catPrincipal, $cats);

    // MONTAGEM DOS CAMPOS SEO
    $titulo   = $nome . ' ' . implode(' ', $telefones) . ' ' . implode(' ', $cidades);
    $keywords = $nome . ', ' . implode(', ', array_map(fn($c)=>"$nome $c", $cidades));
    $friendly = normalizarUrl($keywords);

    // 8. EXECUÇÃO DAS ATUALIZAÇÕES
    // Note que $metaDescPai agora é o primeiro parâmetro do Update
    $updProd->bind_param("ssssi", $metaDescPai, $titulo, $keywords, $keywords, $productId);
    $updProd->execute();

    $updSrch->bind_param("iss", $productId, $nome, $keywords);
    $updSrch->execute();

    $updTag->bind_param("ss", $keywords, $friendly);
    $updTag->execute();
    $tagId = $con->insert_id;

    if ($tagId == 0) {
        $resTag = $con->query("SELECT tagid FROM isc_product_tags WHERE tagname = '" . $con->real_escape_string($keywords) . "'");
        $tagId = $resTag->fetch_assoc()['tagid'] ?? 0;
    }

    if ($tagId > 0) {
        $updAssoc->bind_param("ii", $tagId, $productId);
        $updAssoc->execute();
    }

    // ENVIO DE PROGRESSO
    if ($atual % 5 == 0 || $atual == $total) {
        $percent = intval(($atual / $total) * 100);
        echo "event: progress\ndata: {$percent}\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
}

echo "event: complete\ndata: 100\n\n";

$updProd->close();
$updSrch->close();
$updTag->close();
$updAssoc->close();
$con->close();