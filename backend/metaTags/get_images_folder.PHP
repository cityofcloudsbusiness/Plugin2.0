<?php
header('Content-Type: application/json');
$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$basePath = $root . DIRECTORY_SEPARATOR . 'product_images';

if (!is_dir($basePath)) {
    echo json_encode(["status" => "erro", "mensagem" => "Pasta product_images nao encontrada."]);
    exit;
}

$pastas = [];
// Criamos um iterador que varre subpastas de subpastas infinitamente
$diretorios = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($diretorios as $nome => $objeto) {
    if ($objeto->isDir()) {
        // Pega o caminho relativo (ex: a/309)
        $relativo = str_replace($basePath . DIRECTORY_SEPARATOR, '', $objeto->getPathname());
        $pastas[] = $relativo;
    }
}

// Adiciona a raiz e remove duplicatas
array_unshift($pastas, "");
$pastas = array_unique($pastas);
sort($pastas); // Organiza em ordem alfabetica

echo json_encode(["status" => "ok", "pastas" => array_values($pastas)]);