<?php
// **NÃO** deixe nada antes dos headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);
set_time_limit(0);

include __DIR__ . '/../conexao.php';

// 1) Lê parâmetros
$type = $_GET['type'] ?? '';
$old  = $_GET['old']  ?? '';
$new  = $_GET['new']  ?? '';

if (!in_array($type, ['product','category']) || $old === '' || $new === '') {
    echo "event: error\n";
    echo "data: Parâmetros inválidos\n\n";
    exit;
}

$oldRaw = urldecode($old);
$newRaw = urldecode($new);

// 2) Conta registros e monta SQL
if ($type === 'product') {
    $like = $con->real_escape_string("%{$oldRaw}%");
    $tot  = (int)$con
      ->query("SELECT COUNT(*) FROM isc_products WHERE prodname LIKE '{$like}'")
      ->fetch_row()[0];
    $sql  = "SELECT productid AS id, prodname AS name
               FROM isc_products
              WHERE prodname LIKE '{$like}'";
} else {
    $like = $con->real_escape_string("%{$oldRaw}%");
    $tot  = (int)$con
      ->query("SELECT COUNT(*) FROM isc_categories WHERE catname LIKE '{$like}'")
      ->fetch_row()[0];
    $sql  = "SELECT categoryid AS id, catname AS name
               FROM isc_categories
              WHERE catname LIKE '{$like}'";
}

if ($tot === 0) {
    echo "event: error\n";
    echo "data: Nenhum registro encontrado para: {$oldRaw}\n\n";
    exit;
}

// 3) Envia um progress 0 logo no início
echo "event: progress\n";
echo "data: 0\n\n";
@flush();
usleep(20000);

// 4) Loop de UPDATE
$stmt = $con->prepare(
    $type==='product'
      ? "UPDATE isc_products   SET prodname = ? WHERE productid = ?"
      : "UPDATE isc_categories SET catname  = ? WHERE categoryid = ?"
);

$processed = 0;
$res = $con->query($sql);
while ($row = $res->fetch_assoc()) {
    $processed++;
    $newName = str_replace($oldRaw, $newRaw, $row['name']);
    $stmt->bind_param('si', $newName, $row['id']);
    $stmt->execute();

    $pct = intval($processed / $tot * 100);
    echo "event: progress\n";
    echo "data: {$pct}\n\n";
    @flush();
    usleep(20000);
}
$stmt->close();

// 5) Complete
echo "event: complete\n";
echo "data: 100\n\n";
exit;
