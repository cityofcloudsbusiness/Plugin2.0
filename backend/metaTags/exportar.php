<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

include '../conexao.php';

$id = (int)($_POST['id_categoria'] ?? 0);
if ($id <= 0) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

$produtos = [];
$res = $con->query("SELECT * FROM isc_products WHERE prodcatids = $id");
while ($prod = $res->fetch_assoc()) {
    $idProd = $prod['productid'];

    $imgs = [];
    $resImgs = $con->query("SELECT * FROM isc_product_images WHERE imageprodid = $idProd");
    while ($img = $resImgs->fetch_assoc()) {
        $imgs[] = $img;
    }

    $prod['imagens'] = $imgs;
    $produtos[] = $prod;
}

$bloco = ['Tipo' => 2, 'id_categoria' => $id, 'produtos' => $produtos];

$file = '../../dados/dados.json';
if (!file_exists($file)) {
    file_put_contents($file, '{"Dados": [');
} else {
    file_put_contents($file, ',', FILE_APPEND);
}
file_put_contents($file, json_encode($bloco, JSON_UNESCAPED_UNICODE), FILE_APPEND);

echo json_encode(['status' => 'ok']);
