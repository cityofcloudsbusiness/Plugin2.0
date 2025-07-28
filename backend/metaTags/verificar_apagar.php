
<?php
header('Content-Type: application/json');

include __DIR__ . '/../conexao.php';

if ($con->connect_error) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro na conexão: " . $con->connect_error]);
    exit;
}

$imagem = $_POST['imagem'] ?? '';
// error_log("local: ".$imagem);


if (!$imagem) {
    echo json_encode(["status" => "erro", "mensagem" => "Imagem não especificada."]);
    exit;
}

// Extrair apenas o nome do arquivo
$nomeArquivo = basename($imagem);
// error_log("basename: ".$nomeArquivo);
// Consultar no banco de dados
$sql = "SELECT COUNT(*) AS total FROM isc_product_images WHERE imagefile LIKE ?";
$stmt = $con->prepare($sql);
$searchPattern = "%$nomeArquivo";
$stmt->bind_param("s", $searchPattern);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total = $row['total'] ?? 0;
$stmt->close();

$imagem = preg_replace('/^.*product_images\//', '', $imagem);
// Se não encontrar no banco, apagar o arquivo
$basePath = realpath(__DIR__ . "/../../../../product_images") . '/';
$caminhoCompleto = $basePath . $imagem;

if ($total == 0 && file_exists($caminhoCompleto)) {
    unlink($caminhoCompleto);
    echo json_encode(["status" => "apagada", "imagem" => $imagem]);
} else {
    echo json_encode(["status" => "mantida", "imagem" => $imagem]);
}

$con->close();