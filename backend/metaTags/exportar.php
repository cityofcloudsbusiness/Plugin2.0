<?php
// 1) Só POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// 2) Previne timeouts/memory_limit para este script
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

// 3) Conexão original (não altera esta linha)
include __DIR__ . '/../conexao.php';

// 4) Função helper que reconecta se der “gone away”
/**
 * Busca produtos de uma categoria.
 * Usa isc_categoryassociations para filtrar e isc_product_images para a imagem.
 */
function fetchProdutos(mysqli &$con, int $catId): array
{
    // 1) SQL com imagem
    $sqlJoin = "
      SELECT p.productid,
             p.prodname,
             p.prodprice,
             COALESCE(i.imagefile, '') AS imagefile
      FROM isc_categoryassociations ca
      JOIN isc_products p
        ON p.productid = ca.productid
      LEFT JOIN isc_product_images i
        ON i.imageprodid = p.productid
      WHERE ca.categoryid = ?
    ";

    // 2) SQL sem imagem (fallback)
    $sqlNoImg = "
      SELECT p.productid,
             p.prodname,
             p.prodprice,
             '' AS imagefile
      FROM isc_categoryassociations ca
      JOIN isc_products p
        ON p.productid = ca.productid
      WHERE ca.categoryid = ?
    ";

    try {
        $stmt = $con->prepare($sqlJoin);
        $stmt->bind_param('i', $catId);
        $stmt->execute();

    } catch (mysqli_sql_exception $e) {
        $msg = $e->getMessage();

        // 3.a) Reconecta se "MySQL server has gone away"
        if (stripos($msg, 'MySQL server has gone away') !== false) {
            $con->close();
            include __DIR__ . '/../conexao.php';
            $stmt = $con->prepare($sqlJoin);
            $stmt->bind_param('i', $catId);
            $stmt->execute();

        // 3.b) Se falhar por falta de coluna ou tabela de imagens, usa sem join de imagens
        } elseif (stripos($msg, "doesn't exist") !== false || $e->getCode() === 1146) {
            $stmt = $con->prepare($sqlNoImg);
            $stmt->bind_param('i', $catId);
            $stmt->execute();

        // 3.c) Outro erro: repassa
        } else {
            throw $e;
        }
    }

    $res = $stmt->get_result();
    $produtos = [];
    while ($row = $res->fetch_assoc()) {
        $produtos[] = $row;
    }
    $stmt->close();
    return $produtos;
}


// 5) Lê ID
$id = (int)($_POST['id_categoria'] ?? 0);
if ($id <= 0) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

// 6) Busca produtos (usa helper)
$produtos = fetchProdutos($con, $id);

// 7) Monta o bloco e grava em dados.json
$bloco = [
  'Tipo'         => 2,
  'id_categoria' => $id,
  'produtos'     => $produtos
];

$file = __DIR__ . '/../../dados/dados.json';
if (!file_exists($file)) {
    file_put_contents($file, '{"Dados": [');
} else {
    file_put_contents($file, ',', FILE_APPEND);
}
file_put_contents($file,
    json_encode($bloco, JSON_UNESCAPED_UNICODE),
    FILE_APPEND
);

// 8) Retorna status
echo json_encode(['status' => 'ok']);
