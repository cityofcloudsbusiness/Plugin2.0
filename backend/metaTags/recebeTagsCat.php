<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
set_time_limit(0);

include "../conexao.php";
$con->set_charset("utf8mb4");

$cidades   = json_decode($_GET['cidades']   ?? '[]', true);
$telefones = json_decode($_GET['telefones'] ?? '[]', true);
$id_cats   = json_decode($_GET['id_categorias'] ?? '[]', true);

// CONSTRUÇÃO DO FILTRO SQL (CORRIGIDO)
$where = "";
if (!empty($id_cats) && is_array($id_cats)) {
    $clean_ids = array_filter(array_map('intval', $id_cats));
    if (!empty($clean_ids)) {
        $where = " WHERE categoryid IN (" . implode(',', $clean_ids) . ")";
    }
}

$updCat = $con->prepare("UPDATE isc_categories SET catpagetitle=?, catmetakeywords=?, catsearchkeywords=? WHERE categoryid=?");
$updTag = $con->prepare("INSERT INTO isc_product_tags (tagname, tagfriendlyname, tagcount) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE tagid=LAST_INSERT_ID(tagid)");

$qr = $con->query("SELECT categoryid, catname FROM isc_categories" . $where);
$total = $qr->num_rows;
$atual = 0;

function normalizarUrlCat($str) {
    $a = array('À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý','à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ');
    $b = array('A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','Y','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y');
    $str = str_replace($a, $b, $str);
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(' ', '-', $str);
    return preg_replace('/[^a-z0-9\-]/', '', $str);
}

while ($row = $qr->fetch_assoc()) {
    $atual++;
    $idcat = (int)$row['categoryid'];
    $nome  = $row['catname'];
    
    $titulo   = $nome . ' ' . implode(' ', $telefones) . ' ' . implode(' ', $cidades);
    $keywords = $nome . ', ' . implode(', ', array_map(fn($c)=>"$nome $c", $cidades));
    
    // CORREÇÃO DE ACENTOS
    $friendly = normalizarUrlCat($keywords);

    $updCat->bind_param('sssi', $titulo, $keywords, $keywords, $idcat);
    $updCat->execute();

    $updTag->bind_param('ss', $keywords, $friendly);
    $updTag->execute();

    if ($atual % 5 == 0 || $atual == $total) {
        $percent = intval($atual / $total * 100);
        echo "event: progress\ndata: {$percent}\n\n";
        @flush();
    }
}

echo "event: complete\ndata: 100\n\n";
$updCat->close();
$updTag->close();
$con->close();