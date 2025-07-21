<?php
// **NÃO** deixe nada antes deste <?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);
set_time_limit(0);

include __DIR__ . '/../conexao.php';

// 1) carrega todas as categorias
$cats = $con
  ->query("SELECT categoryid, catname FROM isc_categories")
  ->fetch_all(MYSQLI_ASSOC);

// 2) busca produtos destacados
$prods = [];
$res = $con->query("SELECT productid FROM isc_products WHERE prodfeatured=1");
while ($row = $res->fetch_assoc()) {
    $prods[] = (int)$row['productid'];
}

// 3) se não existe nenhum, transforma cada categoria em produto destaque
if (count($prods) === 0) {
    $ins = $con->prepare("
      INSERT INTO isc_products (prodname, prodfeatured)
      VALUES (?, 1)
    ");
    foreach ($cats as $cat) {
        $ins->bind_param('s', $cat['catname']);
        $ins->execute();
        $newId = $con->insert_id;
        if ($newId) {
            $prods[] = $newId;
        }
    }
    $ins->close();
}

// 4) associa cada produto destaque a cada categoria
$totalCats  = count($cats);
$totalProds = count($prods);
$total      = $totalCats * $totalProds;
if ($total === 0) {
    echo "event: error\n";
    echo "data: Sem dados para processar\n\n";
    exit;
}

// envia progresso inicial 0%
echo "event: progress\n";
echo "data: 0\n\n";
flush();

// prepara statements
$chk = $con->prepare("
  SELECT 1 FROM isc_categoryassociations
   WHERE categoryid = ? AND productid = ?
");
$ins = $con->prepare("
  INSERT INTO isc_categoryassociations (productid, categoryid)
  VALUES (?, ?)
");

$processed = 0;
foreach ($cats as $cat) {
    $catId = (int)$cat['categoryid'];
    foreach ($prods as $prodId) {
        $chk->bind_param('ii', $catId, $prodId);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $ins->bind_param('ii', $prodId, $catId);
            $ins->execute();
        }

        $processed++;
        $pct = intval($processed / $total * 100);
        echo "event: progress\n";
        echo "data: {$pct}\n\n";
        flush();
    }
}

$chk->close();
$ins->close();

// sinaliza conclusão
echo "event: complete\n";
echo "data: 100\n\n";
exit;
