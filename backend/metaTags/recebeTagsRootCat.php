<?php
// SSE endpoint: atualiza catmetadesc para todas as categorias raiz
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// Desabilita buffers do PHP
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);
ini_set('zlib.output_compression', 'Off');
set_time_limit(0);

include "../conexao.php";

// lê descrição passada via GET
$desc = trim($_GET['desc'] ?? '');
if ($desc === '') {
    echo "event: error\n";
    echo "data: Descrição vazia\n\n";
    exit;
}

// seleciona todas as categorias sem parent
$res = $con->query("SELECT categoryid FROM isc_categories WHERE catparentid = 0");
$ids = [];
while ($row = $res->fetch_assoc()) {
    $ids[] = (int)$row['categoryid'];
}

$total = count($ids);
if ($total === 0) {
    echo "event: complete\n";
    echo "data: 100\n\n";
    exit;
}

// loop de atualização com progresso
$atual = 0;
foreach ($ids as $id) {
    $atual++;
    $pct = intval($atual / $total * 100);

    $upd = $con->prepare("UPDATE isc_categories SET catmetadesc = ? WHERE categoryid = ?");
    $upd->bind_param('si', $desc, $id);
    $upd->execute();

    echo "event: progress\n";
    echo "data: {$pct}\n\n";
    @flush();
}

// finaliza
echo "event: complete\n";
echo "data: 100\n\n";
exit;