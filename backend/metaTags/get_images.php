<?php
header('Content-Type: application/json');
include "../conexao.php";

if ($con->connect_error) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro na conexão: " . $con->connect_error]);
    exit;
}

$sql = "SELECT imagefile, imagefiletiny, imagefilethumb, imagefilestd, imagefilezoom FROM isc_product_images";
$result = $con->query($sql);
$imagens = [];

while ($row = $result->fetch_assoc()) {
    foreach ($row as $campo => $valor) {
        if (!empty($valor)) {
            $imagens[] = ['campo' => $campo, 'caminho' => $valor];
        }
    }
}

echo json_encode(["status" => "ok", "imagens" => $imagens]);
$con->close();
