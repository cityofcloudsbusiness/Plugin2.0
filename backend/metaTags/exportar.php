<?php
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../conexao.php';

function erroJson($mensagem) {
    echo json_encode(['erro' => $mensagem]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    erroJson('Método inválido.');
}

if (!isset($_POST['id_categoria'])) {
    erroJson('ID da categoria não fornecido.');
}

$id_categoria = (int) $_POST['id_categoria'];

function fetchCategoria($con, $id) {
    $stmt = $con->prepare("SELECT * FROM isc_categories WHERE categoryid = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function fetchProdutos($con, $catid) {
    $produtos = [];

    $stmt = $con->prepare("
        SELECT p.* FROM isc_products p
        INNER JOIN isc_categoryassociations ca ON p.productid = ca.productid
        WHERE ca.categoryid = ?
    ");
    $stmt->bind_param('i', $catid);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        // Inclui somente campos relevantes (ou todos se quiser)
        $produtos[] = $row;
    }

    return $produtos;
}

function buscarFilhos($con, $id) {
    $filhos = [];

    $stmt = $con->prepare("SELECT categoryid FROM isc_categories WHERE catparentid = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $filhos[] = $row['categoryid'];
    }

    return $filhos;
}

function montarEstrutura($con, $id_categoria, &$estrutura) {
    // CATEGORIA PRINCIPAL
    $categoria = fetchCategoria($con, $id_categoria);
    if ($categoria) {
        $estrutura[] = [
            'Tipo' => 1,
            'id_categoria' => $id_categoria,
            'categoria' => $categoria
        ];

        $estrutura[] = [
            'Tipo' => 2,
            'id_categoria' => $id_categoria,
            'produtos' => fetchProdutos($con, $id_categoria)
        ];
    }

    // RECURSIVO: filhos (subcategorias)
    $filhos = buscarFilhos($con, $id_categoria);
    foreach ($filhos as $filho) {
        montarEstrutura($con, $filho, $estrutura);
    }
}

// Executa exportação
$estrutura = [];
montarEstrutura($con, $id_categoria, $estrutura);

// Salva no arquivo temporário
$arquivo = __DIR__ . '/dados/export_' . date('Ymd_His') . '.json';
file_put_contents($arquivo, json_encode(['Dados' => $estrutura], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Atualiza caminho do último para download
file_put_contents(__DIR__ . '/dados/ultimo.txt', basename($arquivo));

echo json_encode(['status' => 'ok']);
ob_end_flush();
