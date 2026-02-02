<?php
header('Content-Type: application/json');
set_time_limit(0); 
include __DIR__ . '/../conexao.php';

$pastaAlvo = $_POST['pasta'] ?? '';
$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$caminhoCompleto = $root . DIRECTORY_SEPARATOR . 'product_images';

// Se não for a raiz, adiciona a subpasta
if ($pastaAlvo !== "") {
    $caminhoCompleto .= DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pastaAlvo);
}

if (!is_dir($caminhoCompleto)) {
    echo json_encode(["status" => "vazio", "msg" => "Caminho inexistente"]); exit;
}

// scandir pega apenas os arquivos DESTA pasta específica (as subpastas já estão na fila do JS)
$itens = array_diff(scandir($caminhoCompleto), array('.', '..'));
$apagados = 0; $mantidos = 0;

foreach ($itens as $item) {
    $caminhoItem = $caminhoCompleto . DIRECTORY_SEPARATOR . $item;
    
    // Ignora se for pasta (porque as pastas já serão processadas individualmente pelo JS)
    if (is_dir($caminhoItem)) continue;
    
    // Filtra apenas imagens
    if (!preg_match('/\.(jpg|jpeg|png|webp)$/i', $item)) continue;

    // Busca no banco pelo nome do arquivo
    $termo = "%" . $item . "%";
    $sql = "SELECT COUNT(*) AS total FROM isc_product_images WHERE imagefile LIKE ? OR imagefiletiny LIKE ? OR imagefilethumb LIKE ? OR imagefilestd LIKE ? OR imagefilezoom LIKE ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sssss", $termo, $termo, $termo, $termo, $termo);
    $stmt->execute();
    $totalBanco = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    if ($totalBanco == 0) {
        if (@unlink($caminhoItem)) { $apagados++; }
    } else { $mantidos++; }
}

echo json_encode([
    "status" => "ok",
    "pasta" => $pastaAlvo,
    "apagados" => $apagados,
    "mantidos" => $mantidos
]);