<?php
header('Content-Type: application/json');
// Tenta incluir a conex«ªo e silencia erros fatais para retornar JSON
try {
    include "../conexao.php";
    if ($con->connect_error) { throw new Exception("Erro de conexao"); }

    $sql = "SELECT MIN(imageid) as min_id, MAX(imageid) as max_id, COUNT(*) as total FROM isc_product_images";
    $res = $con->query($sql);
    $dados = $res->fetch_assoc();

    echo json_encode([
        "status" => "ok", 
        "min_id" => (int)$dados['min_id'],
        "max_id" => (int)$dados['max_id'],
        "total" => (int)$dados['total']
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
if (isset($con)) { $con->close(); }