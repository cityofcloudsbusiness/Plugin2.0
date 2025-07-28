<?php
header('Content-Type: application/json');

include __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "erro", "mensagem" => "Método inválido."]);
    exit;
}

$imagem = $_POST['imagem'] ?? '';

if (!$imagem) {
    echo json_encode(["status" => "erro", "mensagem" => "Imagem não especificada."]);
    exit;
}

$nomeArquivo = basename($imagem);

// Consulta exata (melhor que LIKE %nome)
$sql = "SELECT COUNT(*) AS total FROM isc_product_images WHERE imagefile = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $imagem);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total = $row['total'] ?? 0;
$stmt->close();

$basePath = realpath(__DIR__ . "/../../../../product_images");

$caminhoCompleto = $basePath . '/' . $imagem;

if ($total == 0 && file_exists($caminhoCompleto)) {
    unlink($caminhoCompleto);
    echo json_encode(["status" => "apagada", "imagem" => $imagem]);
} else {
    echo json_encode(["status" => "mantida", "imagem" => $imagem]);
}

$con->close();
