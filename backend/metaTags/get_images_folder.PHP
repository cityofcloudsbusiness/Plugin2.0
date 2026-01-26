<?php
header('Content-Type: application/json');
set_time_limit(120); // Previne timeout em pastas grandes

// Caminho absoluto para a pasta de imagens
$basePath = realpath(__DIR__ . "/../../../../product_images");

if (!$basePath || !is_dir($basePath)) {
    echo json_encode(["status" => "erro", "mensagem" => "Pasta de imagens não encontrada."]);
    exit;
}

function listarImagens($diretorioBase) {
    $imagens = [];
    $dir = new RecursiveDirectoryIterator($diretorioBase, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir);

    foreach ($iterator as $arquivo) {
        // Regex atualizada para incluir .webp
        if ($arquivo->isFile() && preg_match('/\.(jpg|jpeg|png|webp)$/i', $arquivo->getFilename())) {
            $caminhoRelativo = str_replace($diretorioBase . DIRECTORY_SEPARATOR, '', $arquivo->getPathname());
            // Padroniza barras para o formato do banco de dados (/)
            $caminhoRelativo = str_replace("\\", "/", $caminhoRelativo); 
            $imagens[] = $caminhoRelativo;
        }
    }
    return $imagens;
}

$imagens = listarImagens($basePath);
echo json_encode(["status" => "ok", "imagens" => $imagens]);