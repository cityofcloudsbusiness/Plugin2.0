<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
set_time_limit(0);

include "../conexao.php";

$cidades   = json_decode($_GET['cidades']   ?? '[]', true);
$telefones = json_decode($_GET['telefones'] ?? '[]', true);
$id_cats   = json_decode($_GET['id_categorias'] ?? '[]', true);

$where = "";
if (!empty($id_cats)) {
    $ids_string = implode(',', array_map('intval', $id_cats));
    $where = " WHERE categoryid IN ($ids_string)";
}

// 1. Prepara Statements (Melhora performance e evita erros de SQL)
$updCat = $con->prepare("UPDATE isc_categories SET catpagetitle=?, catmetakeywords=?, catsearchkeywords=? WHERE categoryid=?");
$updTag = $con->prepare("INSERT INTO isc_product_tags (tagname, tagfriendlyname, tagcount) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE tagid=LAST_INSERT_ID(tagid)");
// Nota: Categorias geralmente não têm associação direta de tag na 'isc_product_tagassociations' (que é focada em produtos), 
// mas se o seu template exigir, a lógica seria similar à de produtos.

$qr = $con->query("SELECT categoryid, catname FROM isc_categories" . $where);
$total = $qr->num_rows;
$atual = 0;

while ($row = $qr->fetch_assoc()) {
    $atual++;
    $idcat = (int)$row['categoryid'];
    $nome  = $row['catname'];
    
    $titulo   = $nome . ' ' . implode(' ', $telefones) . ' ' . implode(' ', $cidades);
    $keywords = $nome . ', ' . implode(', ', array_map(fn($c)=>"$nome $c", $cidades));
    $friendly = mb_strtolower(str_replace(' ', '-', $keywords), 'UTF-8');

    // Executa Update da Categoria
    $updCat->bind_param('sssi', $titulo, $keywords, $keywords, $idcat);
    $updCat->execute();

    // Cria/Atualiza a Tag Global para esta categoria
    $updTag->bind_param('ss', $keywords, $friendly);
    $updTag->execute();

    if ($atual % 5 == 0 || $atual == $total) {
        $percent = intval($atual / $total * 100);
        echo "event: progress\ndata: {$percent}\n\n";
        @flush();
    }
}

echo "event: complete\ndata: 100\n\n";