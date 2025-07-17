<?php
// transforma em Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

include "../conexao.php";

// 1) valida parâmetros via GET (SSE só trabalha com GET)
$num1      = filter_input(INPUT_GET,  'num1',      FILTER_VALIDATE_INT);
$num2      = filter_input(INPUT_GET,  'num2',      FILTER_VALIDATE_INT);
$cidades   = json_decode($_GET['cidades']   ?? '[]', true);
$telefones = json_decode($_GET['telefones'] ?? '[]', true);

if ($num1 === false || $num2 === false) {
    echo "event: error\n";
    echo "data: Parâmetros inválidos\n\n";
    exit;
}

// 2) carrega todas as categorias numa única query
$cats = [];
$res = $con->query("
    SELECT categoryid, catparentid, catmetadesc
      FROM isc_categories
");
while ($r = $res->fetch_assoc()) {
    $cats[(int)$r['categoryid']] = [
      'parent'      => (int)$r['catparentid'],
      'description' => $r['catmetadesc']
    ];
}
function getMetaDesc(int $cid, array $cats): string {
    if (! isset($cats[$cid])) {
        return '';
    }
    while (isset($cats[$cid]) && $cats[$cid]['parent'] !== 0) {
        $cid = $cats[$cid]['parent'];
    }
    return $cats[$cid]['description'] ?? '';
}

// 3) busca todos os produtos de uma vez
$prods = $con->query("SELECT productid, prodname, prodcatids FROM isc_products");
$total = $prods->num_rows;
$atual = 0;

// 4) loop de processamento + envio de evento SSE
while ($prod = $prods->fetch_assoc()) {
    $atual++;
    $percent = intval($atual / $total * 100);

    // extrai dados
    $productId = (int)$prod['productid'];
    $nome      = $prod['prodname'];
    $ids       = array_filter(explode(',', $prod['prodcatids']), fn($v)=>ctype_digit($v));
    $categoria = $ids ? (int)reset($ids) : 0;
    $metaDesc  = getMetaDesc($categoria, $cats);

    // monta strings
    $titulo   = $nome . ' ' . implode(' ', $telefones) . ' ' . implode(' ', $cidades);
    $keywords = $nome . ', ' . implode(', ', array_map(fn($c)=>"$nome $c", $cidades));
    $friendly = mb_strtolower(str_replace(' ', '-', $keywords), 'UTF-8');

    // UPDATE no produto
    $upd = $con->prepare("
      UPDATE isc_products
         SET prodmetadesc       = ?,
             prodpagetitle      = ?,
             prodsearchkeywords = ?,
             prodmetakeywords   = ?
       WHERE productid         = ?
    ");
    $upd->bind_param("ssssi",
        $metaDesc,
        $titulo,
        $keywords,
        $keywords,
        $productId
    );
    $upd->execute();

    // upsert na tabela de busca
    $srch = $con->prepare("
      INSERT INTO isc_product_search (productid, prodname, prodsearchkeywords)
      VALUES (?, ?, ?)
      ON DUPLICATE KEY UPDATE
        prodsearchkeywords = VALUES(prodsearchkeywords)
    ");
    $srch->bind_param("iss", $productId, $nome, $keywords);
    $srch->execute();

    // upsert de tags
    $tag = $con->prepare("
      INSERT INTO isc_product_tags (tagname, tagfriendlyname, tagcount)
      VALUES (?, ?, 0)
      ON DUPLICATE KEY UPDATE
        tagname         = VALUES(tagname),
        tagfriendlyname = VALUES(tagfriendlyname)
    ");
    $tag->bind_param("ss", $keywords, $friendly);
    $tag->execute();
    $tagId = $con->insert_id ?: $tagId;

    // associação tag↔produto
    $assoc = $con->prepare("
      INSERT INTO isc_product_tagassociations (tagid, productid)
      VALUES (?, ?)
      ON DUPLICATE KEY UPDATE
        tagid = tagid
    ");
    $assoc->bind_param("ii", $tagId, $productId);
    $assoc->execute();

    // envia progresso ao cliente
    echo "event: progress\n";
    echo "data: {$percent}\n\n";
    @ob_flush(); flush();
}

// sinaliza conclusão
echo "event: complete\n";
echo "data: 100\n\n";
exit;
