<?php
// backend/metaTags/prepararImportacao.php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');
error_log("prepararImportacao: início");

// inclui conexão
$pathConn = __DIR__ . '/../conexao.php';
if (!file_exists($pathConn)) {
    error_log("prepararImportacao: conexao.php não encontrado em $pathConn");
    http_response_code(500);
    echo json_encode(['erro' => 'Arquivo de conexão não encontrado.']);
    exit;
}
require_once $pathConn;

// valida arquivo enviado
if (empty($_FILES['arquivo']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhum arquivo enviado.']);
    exit;
}

// decodifica JSON
$dados = json_decode(file_get_contents($_FILES['arquivo']['tmp_name']), true);
if (!isset($dados['Dados']) || !is_array($dados['Dados'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'JSON inválido.']);
    exit;
}

$etapas = [];
foreach ($dados['Dados'] as $i => $bloco) {
    $fn   = uniqid('etapa_', true) . '.json';
    $dest = __DIR__ . '/dados/' . $fn;
    if (file_put_contents($dest, json_encode($bloco, JSON_UNESCAPED_UNICODE))) {
        error_log("prepararImportacao: bloco $i salvo em $fn");
        $etapas[] = $fn;
    } else {
        error_log("prepararImportacao: erro ao salvar bloco $i");
    }
}

file_put_contents(__DIR__ . '/dados/etapas.txt', json_encode($etapas));
error_log("prepararImportacao: total de blocos = " . count($etapas));

echo json_encode([
    'total'  => count($etapas),
    'etapas' => $etapas
]);
