<?php
// backend/metaTags/tags.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../conexao.php';

$action = $_GET['action'] ?? '';
$batch = 1000; // Lote de limpeza

if ($action === 'count') {
    $totalP = $con->query("SELECT COUNT(*) FROM isc_products")->fetch_row()[0];
    $totalC = $con->query("SELECT COUNT(*) FROM isc_categories")->fetch_row()[0];
    $totalT = $con->query("SELECT COUNT(*) FROM isc_product_tags")->fetch_row()[0];
    echo json_encode(['total' => ($totalP + $totalC + $totalT)]);
    exit;
}

if ($action === 'purge_fraction') {
    $tipo = $_POST['tipo'] ?? 'produtos'; // 'produtos', 'categorias' ou 'tags'
    $offset = (int)($_POST['offset'] ?? 0);
    
    $con->set_charset("utf8mb4");

    if ($tipo === 'produtos') {
        // Limpa campos de SEO em lotes de IDs
        $sql = "UPDATE isc_products SET prodsearchkeywords='', prodpagetitle='', prodmetakeywords='', prodmetadesc='' 
                WHERE productid IN (SELECT productid FROM (SELECT productid FROM isc_products LIMIT $offset, $batch) as tmp)";
        $con->query($sql);
        $result = $con->affected_rows;
        echo json_encode(['status' => 'ok', 'processed' => $batch, 'finished' => ($result == 0)]);
    } 
    elseif ($tipo === 'categorias') {
        $sql = "UPDATE isc_categories SET catpagetitle='', catmetakeywords='', catmetadesc='', catsearchkeywords='' 
                WHERE categoryid IN (SELECT categoryid FROM (SELECT categoryid FROM isc_categories LIMIT $offset, $batch) as tmp)";
        $con->query($sql);
        $result = $con->affected_rows;
        echo json_encode(['status' => 'ok', 'processed' => $batch, 'finished' => ($result == 0)]);
    }
    elseif ($tipo === 'tags') {
        // Deleta as tags em lotes
        $rs = $con->query("SELECT tagid FROM isc_product_tags LIMIT $batch");
        $ids = [];
        while($r = $rs->fetch_assoc()) $ids[] = $r['tagid'];
        
        if($ids) {
            $list = implode(',', $ids);
            $con->query("DELETE FROM isc_product_tagassociations WHERE tagid IN ($list)");
            $con->query("DELETE FROM isc_product_tags WHERE tagid IN ($list)");
            echo json_encode(['status' => 'ok', 'processed' => count($ids), 'finished' => false]);
        } else {
            echo json_encode(['status' => 'ok', 'processed' => 0, 'finished' => true]);
        }
    }
    exit;
}