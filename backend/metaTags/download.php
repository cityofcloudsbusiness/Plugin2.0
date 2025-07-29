<?php
// Só GET, isolado; não interfere em nada existente
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Método não permitido');
}

$file = __DIR__ . '/../../dados/dados.json';
if (!file_exists($file)) {
    http_response_code(404);
    exit('Arquivo não encontrado');
}

// Força o download do JSON gerado pelo exportar.php
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="dados.json"');
readfile($file);
exit;
