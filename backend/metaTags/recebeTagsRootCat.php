<?php
/**
 * SSE endpoint: atualiza catmetadesc nas categorias e propaga para prodmetadesc nos produtos filhos
 */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);
ini_set('zlib.output_compression', 'Off');
set_time_limit(0);

include "../conexao.php";
$con->set_charset("utf8mb4");

// 1. Coleta de parâmetros
$desc = trim($_GET['desc'] ?? '');
$id_cats = json_decode($_GET['id_categorias'] ?? '[]', true);

if ($desc === '' || empty($id_cats)) {
    echo "event: error\ndata: Parâmetros inválidos\n\n";
    exit;
}

$ids_limpos = array_filter(array_map('intval', $id_cats));
$total = count($ids_limpos);

if ($total === 0) {
    echo "event: complete\ndata: 100\n\n";
    exit;
}

// 2. Preparação dos Statements para performance e segurança
$updCat = $con->prepare("UPDATE isc_categories SET catmetadesc = ? WHERE categoryid = ?");
$updProd = $con->prepare("UPDATE isc_products SET prodmetadesc = ? WHERE FIND_IN_SET(?, prodcatids)");

$atual = 0;

foreach ($ids_limpos as $id) {
    $atual++;
    $pct = intval($atual / $total * 100);

    // A) Atualiza a Categoria em si
    $updCat->bind_param('si', $desc, $id);
    $updCat->execute();

    // B) Atualiza todos os PRODUTOS FILHOS desta categoria
    // O FIND_IN_SET garante que produtos associados a múltiplas categorias também sejam atualizados
    $updProd->bind_param('si', $desc, $id);
    $updProd->execute();

    // Envio de progresso
    echo "event: progress\ndata: {$pct}\n\n";
    
    if (ob_get_level() > 0) ob_flush();
    flush();
}

echo "event: complete\ndata: 100\n\n";

$updCat->close();
$updProd->close();
$con->close();
exit;