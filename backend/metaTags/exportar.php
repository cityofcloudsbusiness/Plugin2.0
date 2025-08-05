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

if (!isset($_POST['id_categorias']) || !is_array($_POST['id_categorias'])) {
    erroJson('IDs das categorias não fornecidos.');
}

$id_categorias = array_map('intval', $_POST['id_categorias']);

// Utilizado para rastrear categorias já exportadas
$jaExportadas = [];

function fetchCategoriaComPai($con, $id) {
    $stmt = $con->prepare("
        SELECT c.*, 
               p.catname as catparentname 
        FROM isc_categories c
        LEFT JOIN isc_categories p ON c.catparentid = p.categoryid
        WHERE c.categoryid = ?
    ");
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

function montarEstrutura($con, $id_categoria, &$estrutura, &$jaExportadas) {
    if (in_array($id_categoria, $jaExportadas)) return;
    $jaExportadas[] = $id_categoria;

    // CATEGORIA
    $categoria = fetchCategoriaComPai($con, $id_categoria);
    if ($categoria) {
        $estrutura[] = [
            'Tipo' => 1,
            'id_categoria' => $id_categoria,
            'categoria' => $categoria
        ];

        // PRODUTOS DESSA CATEGORIA
        $estrutura[] = [
            'Tipo' => 2,
            'id_categoria' => $id_categoria,
            'produtos' => fetchProdutos($con, $id_categoria)
        ];
    }

    // FILHOS
    $filhos = buscarFilhos($con, $id_categoria);
    foreach ($filhos as $filho) {
        montarEstrutura($con, $filho, $estrutura, $jaExportadas);
    }
}

// Executa exportação
$estrutura = [];
foreach ($id_categorias as $id) {
    montarEstrutura($con, $id, $estrutura, $jaExportadas);
}

// Salva em arquivo JSON
$arquivo = __DIR__ . '/dados/export_' . date('Ymd_His') . '.json';
file_put_contents($arquivo, json_encode(['Dados' => $estrutura], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/dados/ultimo.txt', basename($arquivo));

echo json_encode(['status' => 'ok']);
ob_end_flush();
