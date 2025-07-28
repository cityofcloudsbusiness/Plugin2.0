<?php
header('Content-Type: application/json');

$basePath = realpath(__DIR__ . "/../../../../product_images");

if (!$basePath || !is_dir($basePath)) {
    echo json_encode(["status" => "erro", "mensagem" => "Pasta de imagens não encontrada."]);
    exit;
}

function listarImagens($diretorioBase) {
    $imagens = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($diretorioBase));

    foreach ($iterator as $arquivo) {
        if ($arquivo->isFile() && preg_match('/\.(jpg|jpeg|png)$/i', $arquivo->getFilename())) {
            $caminhoRelativo = str_replace($diretorioBase . DIRECTORY_SEPARATOR, '', $arquivo->getPathname());
            $caminhoRelativo = str_replace("\\", "/", $caminhoRelativo); // compatível Windows
            $imagens[] = $caminhoRelativo;
        }
    }

    return $imagens;
}

$imagens = listarImagens($basePath);
echo json_encode(["status" => "ok", "imagens" => $imagens]);
