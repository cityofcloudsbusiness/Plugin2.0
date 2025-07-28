<?php
header('Content-Type: application/json');

// Caminho da pasta onde as imagens estão armazenadas
$basePath = realpath(__DIR__ . "/../../../../product_images") . '/';

function listarImagens($diretorio) {
    $imagens = [];
    $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($diretorio));

    foreach ($arquivos as $arquivo) {
        if ($arquivo->isFile() && preg_match('/\.(jpg|jpeg|png)$/i', $arquivo->getFilename())) {
            // Caminho relativo a partir de product_images/
            $caminhoRelativo = str_replace($diretorio, "", $arquivo->getPathname());
            $caminhoRelativo = ltrim(str_replace("\\", "/", $caminhoRelativo), "/");
            $imagens[] = $caminhoRelativo;
        }
    }
    return $imagens;
}

$imagens = listarImagens($basePath);
echo json_encode(["status" => "ok", "imagens" => $imagens]);
?>
