<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../conexao.php';

function erro($msg) {
    echo json_encode(['erro' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') erro("Requisição inválida.");
if (!isset($_FILES['arquivo'])) erro("Arquivo JSON não enviado.");

$dados = json_decode(file_get_contents($_FILES['arquivo']['tmp_name']), true);
if (!$dados || !isset($dados['Dados'])) erro("JSON inválido.");

$mapCatNameToId = [];

// Busca ID de categoria pelo nome
function buscarCategoriaIdPorNome($con, $nome) {
    global $mapCatNameToId;
    if (isset($mapCatNameToId[$nome])) return $mapCatNameToId[$nome];

    $stmt = $con->prepare("SELECT categoryid FROM isc_categories WHERE catname = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res ? $res['categoryid'] : null;
}

// Cria categoria se não existir
function criarCategoria($con, $categoria) {
    global $mapCatNameToId;
    $nome = $categoria['catname'];

    $existeId = buscarCategoriaIdPorNome($con, $nome);
    if ($existeId) return $existeId;

    $catparentid = $categoria['catparentid'] ?? 0;

    // Tenta encontrar o pai pelo nome (se o ID não existir ainda)
    if ($catparentid && !in_array($catparentid, $mapCatNameToId)) {
        foreach ($mapCatNameToId as $n => $id) {
            if ($n === $categoria['catparentname']) {
                $catparentid = $id;
                break;
            }
        }
    }

    $stmt = $con->prepare("INSERT INTO isc_categories (catparentid, catname) VALUES (?, ?)");
    $stmt->bind_param("is", $catparentid, $nome);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $mapCatNameToId[$nome] = $newId;
    return $newId;
}

// Associa produto a categoria
function associarProduto($con, $idCategoria, $produto) {
    $productid = $produto['productid'];

    $stmt = $con->prepare("SELECT 1 FROM isc_products WHERE productid = ?");
    $stmt->bind_param("i", $productid);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $colunas = array_keys($produto);
        $valores = array_values($produto);
        $placeholders = implode(',', array_fill(0, count($valores), '?'));
        $tipos = str_repeat('s', count($valores));

        $stmt = $con->prepare("INSERT INTO isc_products (`" . implode('`,`', $colunas) . "`) VALUES ($placeholders)");
        $stmt->bind_param($tipos, ...$valores);
        $stmt->execute();
    }

    $stmt = $con->prepare("INSERT IGNORE INTO isc_categoryassociations (categoryid, productid) VALUES (?, ?)");
    $stmt->bind_param("ii", $idCategoria, $productid);
    $stmt->execute();
}

// Executa importação
foreach ($dados['Dados'] as $item) {
    if ($item['Tipo'] === 1) {
        $categoria = $item['categoria'];
        $categoria['catparentid'] = buscarCategoriaIdPorNome($con, $categoria['catparentname'] ?? '') ?? 0;
        $catid = criarCategoria($con, $categoria);
    }

    if ($item['Tipo'] === 2 && !empty($item['produtos'])) {
        $catid = buscarCategoriaIdPorNome($con, $item['categoria']['catname'] ?? '') ?? null;
        if ($catid) {
            foreach ($item['produtos'] as $produto) {
                associarProduto($con, $catid, $produto);
            }
        }
    }
}

echo json_encode(['status' => 'ok']);
