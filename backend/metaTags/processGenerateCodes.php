<?php
session_start();
// 1) Headers SSE — nenhum output antes disto!
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
while(ob_get_level()) ob_end_flush();
ob_implicit_flush(true);
set_time_limit(0);

// 2) Carrega config
$config = include __DIR__ . '/config.php';
$delay  = max(0, intval($config['siteTimeout']));

// 3) Conecta
include __DIR__ . '/../conexao.php';

// 4) Carrega tudo em memória para cache:
//   4.1) Produtos
$prodIds = [];
$resP = $con->query("SELECT productid FROM isc_products");
while($r = $resP->fetch_assoc()) {
  $prodIds[] = (int)$r['productid'];
}
$total = count($prodIds);
if ($total === 0) {
  echo "event: error\n";
  echo "data: Nenhum produto encontrado.\n\n";
  exit;
}

//   4.2) Associação categoria→produto
$assoc = [];
$resA = $con->query("SELECT productid, categoryid FROM isc_categoryassociations");
while($r = $resA->fetch_assoc()) {
  $assoc[(int)$r['productid']][] = (int)$r['categoryid'];
}

//   4.3) Dados de categorias
$cats = [];
$resC = $con->query("SELECT categoryid, catparentid, catsort FROM isc_categories");
while($r = $resC->fetch_assoc()) {
  $cats[(int)$r['categoryid']] = [
    'parent' => (int)$r['catparentid'],
    'sort'   => (int)$r['catsort'],
  ];
}

// 5) Prepared+transação
$con->begin_transaction();
$updProd   = $con->prepare("UPDATE isc_products       SET prodcode = ? WHERE productid = ?");
$updSearch = $con->prepare("UPDATE isc_product_search SET prodcode = ? WHERE productid = ?");

// Recursão em PHP puro para extrair “pontuação” de uma categoria
function computeScore(int $cid, array $cats): string {
  $score = '';
  while(isset($cats[$cid]) && $cats[$cid]['parent'] !== 0) {
    $s = $cats[$cid]['sort'];
    if ($s > 999) {
      // remove o dígito mais significativo
      $t = intval(substr((string)$s,0,1) . str_repeat('0', strlen((string)$s)-1));
      $s = $s - $t;
      if ($s > 9) $s = intval(strrev((string)$s));
    } elseif ($s > 9) {
      $s = intval(strrev((string)$s));
    }
    $score = $s . $score;
    $cid   = $cats[$cid]['parent'];
  }
  return $score;
}

// 6) envia 0%
echo "event: progress\n";
echo "data: 0\n\n";
flush();

// 7) Loop SSE
for($i = 0; $i < $total; $i++) {
  $pid = $prodIds[$i];

  // 7.1) calcula “melhor” pontuação entre todas as categorias associadas
  $best = 0;
  if (!empty($assoc[$pid])) {
    foreach($assoc[$pid] as $cid) {
      $sc = (int) computeScore($cid, $cats);
      $best = max($best, $sc);
    }
  }

  // 7.2) monta código final
  $code = "{$best}0{$pid}";

  // 7.3) grava no banco
  $updProd  ->bind_param('si', $code, $pid);
  $updProd  ->execute();
  $updSearch->bind_param('si', $code, $pid);
  $updSearch->execute();

  // 7.4) envia progresso
  $pct = intval((($i+1)/$total)*100);
  echo "event: progress\n";
  echo "data: {$pct}\n\n";
  flush();

  // 7.5) atraso configurável
  if ($delay > 0) {
    usleep($delay * 1000);
  }
}

// 8) FINAL
$con->commit();
echo "event: complete\n";
echo "data: 100\n\n";
exit;
