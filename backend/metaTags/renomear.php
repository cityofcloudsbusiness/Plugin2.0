<?php

header('Content-Type: application/json');
include "../conexao.php";

if ($con->connect_error) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro na conexão: " . $con->connect_error]);
    exit;
}

$imagem = $_POST['imagem']['caminho'] ?? '';
$campo = $_POST['imagem']['campo'] ?? '';
$cidade = $_POST['cidade'] ?? '';
$telefone = $_POST['telefone'] ?? '';

if (!$imagem || !$campo || !$cidade || !$telefone) {
    echo json_encode(["status" => "erro", "mensagem" => "Parâmetros inválidos."]);
    exit;
}

$sql = "SELECT imageprodid FROM isc_product_images WHERE $campo = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $imagem);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$produtoId = $row['imageprodid'] ?? null;
$stmt->close();

if (!$produtoId) {
    echo json_encode(["status" => "erro", "mensagem" => "Produto não encontrado."]);
    exit;
}

$sql = "SELECT prodname FROM isc_products WHERE productid = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $produtoId);
$stmt->execute();
$result = $stmt->get_result();
$produto = $result->fetch_assoc()['prodname'] ?? 'produto';
$stmt->close();

$produto = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($produto));

$basePath = realpath(__DIR__ . "/../../../../product_images") . '/';
$diretorio = dirname($imagem);
$extensao = pathinfo($imagem, PATHINFO_EXTENSION) ?: 'jpg';
$novoNome = "$diretorio/$produto-$cidade-$telefone-" . uniqid() . ".$extensao";
$caminhoAntigo = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath . $imagem);
// Caminho absoluto para mover o arquivo fisicamente
$caminhoNovoFisico = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath . $novoNome);

// Caminho RELATIVO a ser salvo no banco (sem basePath)
$caminhoBanco = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $novoNome);



if (rename($caminhoAntigo, $caminhoNovoFisico)) {
    redimensionarImagem($caminhoNovoFisico, 800);

    $sql = "UPDATE isc_product_images SET $campo = ? WHERE $campo = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $caminhoBanco, $imagem);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["status" => "ok"]);
    exit;
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao renomear imagem existente."]);
    exit;
}
$con->close();

function redimensionarImagem($caminho, $larguraMax) {
    if (!function_exists('imagecreatefromjpeg')) return false;

    list($largura, $altura, $tipo) = getimagesize($caminho);
    if (!$largura || !$altura) return false;

    $proporcao = $larguraMax / $largura;
    $novaAltura = (int)($altura * $proporcao); // 👈 convertendo para inteiro

    $imagemNova = imagecreatetruecolor((int)$larguraMax, $novaAltura);

    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagemOrigem = imagecreatefromjpeg($caminho);
            break;
        case IMAGETYPE_PNG:
            $imagemOrigem = imagecreatefrompng($caminho);
            break;
        default:
            return false;
    }

    imagecopyresampled($imagemNova, $imagemOrigem, 0, 0, 0, 0, $larguraMax, $novaAltura, $largura, $altura);
    imagejpeg($imagemNova, $caminho, 80);
    imagedestroy($imagemOrigem);
    imagedestroy($imagemNova);
}
