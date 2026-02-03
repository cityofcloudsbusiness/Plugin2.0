<?php
header('Content-Type: application/json');
set_time_limit(120); // Tempo suficiente para uma subpasta
include __DIR__ . '/../conexao.php';

$pastaAlvo = $_POST['pasta'] ?? '';
$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$caminhoPasta = $root . DIRECTORY_SEPARATOR . 'product_images' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pastaAlvo);

if (!is_dir($caminhoPasta)) {
    echo json_encode(["status" => "vazio", "msg" => "Pasta nao existe"]); exit;
}

$arquivos = scandir($caminhoPasta);
$apagados = 0;
$mantidos = 0;

foreach ($arquivos as $arquivo) {
    if ($arquivo === '.' || $arquivo === '..' || is_dir($caminhoPasta . DIRECTORY_SEPARATOR . $arquivo)) continue;

    // Lógica de verificação no banco (usando o nome do arquivo)
    $termo = "%" . $arquivo . "%";
    $sql = "SELECT COUNT(*) AS total FROM isc_product_images WHERE imagefile LIKE ? OR imagefiletiny LIKE ? OR imagefilethumb LIKE ? OR imagefilestd LIKE ? OR imagefilezoom LIKE ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sssss", $termo, $termo, $termo, $termo, $termo);
    $stmt->execute();
    $totalBanco = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($totalBanco == 0) {
        if (@unlink($caminhoPasta . DIRECTORY_SEPARATOR . $arquivo)) $apagados++;
    } else {
        $mantidos++;
    }
}

echo json_encode([
    "status" => "ok", 
    "apagados" => $apagados, 
    "mantidos" => $mantidos,
    "pasta" => $pastaAlvo
]);