<?php
header('Content-Type: application/json');
set_time_limit(0); 
ini_set('display_errors', 0);
include __DIR__ . '/../conexao.php';

$idInicial = isset($_POST['p1']) ? (int)$_POST['p1'] : 0;
$cidade = $_POST['p2'] ?? '';
$telefone = $_POST['p3'] ?? '';
$loteTamanho = 50; 
$fimId = $idInicial + $loteTamanho;

$logs = [];

// Busca as imagens e o nome do produto vinculado
$sql = "SELECT i.imageid, i.imagefile, i.imagefiletiny, i.imagefilethumb, i.imagefilestd, i.imagefilezoom, p.prodname 
        FROM isc_product_images i
        LEFT JOIN isc_products p ON i.imageprodid = p.productid
        WHERE i.imageid >= ? AND i.imageid < ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $idInicial, $fimId);
$stmt->execute();
$resultados = $stmt->get_result();

$basePath = realpath(__DIR__ . "/../../../../product_images");

while ($row = $resultados->fetch_assoc()) {
    $prodSlug = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($row['prodname'] ?? 'produto'));
    $colunas = ['imagefile', 'imagefiletiny', 'imagefilethumb', 'imagefilestd', 'imagefilezoom'];

    foreach ($colunas as $col) {
        $caminhoOriginal = $row[$col];
        if (empty($caminhoOriginal)) continue;

        $ext = pathinfo($caminhoOriginal, PATHINFO_EXTENSION);
        $diretorioInterno = dirname($caminhoOriginal);
        $novoNome = "{$prodSlug}-{$cidade}-{$telefone}-" . uniqid() . ".{$ext}";
        $caminhoNovoRelativo = ($diretorioInterno == '.' ? '' : $diretorioInterno . '/') . $novoNome;

        $fisicoAntigo = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoOriginal);
        $fisicoNovo = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoNovoRelativo);

        if (file_exists($fisicoAntigo) && @rename($fisicoAntigo, $fisicoNovo)) {
            $con->query("UPDATE isc_product_images SET $col = '$caminhoNovoRelativo' WHERE imageid = " . $row['imageid']);
            $logs[] = "✅ ID {$row['imageid']} ($col): Renomeado.";
        }
    }
}

echo json_encode(["status" => "ok", "proximo_id" => $fimId, "logs" => $logs]);