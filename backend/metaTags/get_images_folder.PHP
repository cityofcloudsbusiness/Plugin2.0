<?php
header('Content-Type: application/json');

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$basePath = $root . DIRECTORY_SEPARATOR . 'product_images';

if (!is_dir($basePath)) {
    echo json_encode(["status" => "erro", "mensagem" => "Diretorio raiz nao encontrado."]);
    exit;
}

// Lista apenas as pastas de primeiro e segundo nível (ex: product_images/a, product_images/a/1...)
$pastas = [];
$dir = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($dir as $item) {
    if ($item->isDir()) {
        $pastas[] = str_replace($basePath . DIRECTORY_SEPARATOR, '', $item->getPathname());
    }
}

// Adiciona a própria raiz caso existam imagens soltas nela
array_unshift($pastas, ""); 

echo json_encode(["status" => "ok", "pastas" => $pastas]);