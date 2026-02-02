<?php
header('Content-Type: application/json');
include "../conexao.php";

// Busca apenas o intervalo de IDs para n«ªo estourar a mem«Ñria
$sql = "SELECT MIN(imageid) as min_id, MAX(imageid) as max_id FROM isc_product_images";
$res = $con->query($sql);
$dados = $res->fetch_assoc();

echo json_encode([
    "status" => "ok",
    "min_id" => (int)$dados['min_id'],
    "max_id" => (int)$dados['max_id']
]);
$con->close();