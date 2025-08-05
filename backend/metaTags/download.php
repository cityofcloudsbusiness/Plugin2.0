<?php
// download.php
$ultimoArquivo = __DIR__ . '/dados/ultimo.txt';

if (!file_exists($ultimoArquivo)) {
    http_response_code(404);
    exit('Arquivo de referência não encontrado.');
}

$nomeArquivo = trim(file_get_contents($ultimoArquivo));
$caminho = __DIR__ . '/dados/' . $nomeArquivo;

if (!file_exists($caminho)) {
    http_response_code(404);
    exit('Arquivo JSON não encontrado.');
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
readfile($caminho);
exit;
