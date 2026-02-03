<?php
header('Content-Type: application/json');
set_time_limit(30); // Cada fração tem 30 segundos para rodar
include __DIR__ . '/../conexao.php';

$idInicial = isset($_POST['p1']) ? (int)$_POST['p1'] : 1;
$cidade = $_POST['p2'] ?? '';
$telefone = $_POST['p3'] ?? '';
$tamanhoFração = 10; // Processamos apenas 10 por vez para o log ser rápido

$logs = [];
$encontrouAlgo = false;
$proximoIdSugerido = $idInicial + $tamanhoFração;

// SQL Rápido: Busca o próximo bloco de IDs existentes
$sql = "SELECT i.imageid, i.imagefile, p.prodname FROM isc_product_images i 
        LEFT JOIN isc_products p ON i.imageprodid = p.productid 
        WHERE i.imageid >= ? ORDER BY i.imageid ASC LIMIT ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $idInicial, $tamanhoFração);
$stmt->execute();
$res = $stmt->get_result();

$basePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . 'product_images' . DIRECTORY_SEPARATOR;

while ($row = $res->fetch_assoc()) {
    $encontrouAlgo = true;
    $id = $row['imageid'];
    $logs[] = "-> Fração ID $id: Analisando...";
    
    // Lógica de renomeação simplificada para não travar
    $slug = preg_replace('/[^a-z0-9]/', '-', strtolower($row['prodname'] ?? 'produto'));
    $caminhoRelativo = $row['imagefile'];

    if (!empty($caminhoRelativo)) {
        $fisico = $basePath . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoRelativo);
        if (file_exists($fisico)) {
            $logs[] = "   [LOG] Arquivo localizado: $caminhoRelativo";
            // ... (Aqui entra o rename e o UPDATE que já temos)
        } else {
            $logs[] = "   [LOG] Arquivo físico ausente: $caminhoRelativo";
        }
    }
    $proximoIdSugerido = $id + 1; // Garante que o próximo lote comece após o último ID achado
}

echo json_encode([
    "status" => "ok",
    "encontrou_algo" => $encontrouAlgo,
    "proximo_id" => $proximoIdSugerido,
    "logs" => $logs
]);