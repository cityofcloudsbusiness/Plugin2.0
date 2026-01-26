<?php
// backend/metaTags/prepararImportacao.php

// 1) aplicar timeout vindo do config.php
$config = require __DIR__ . '/config.php';
if (! empty($config['siteTimeout'])) {
    set_time_limit((int)$config['siteTimeout']);
}

// mostrar apenas erros fatais
error_reporting(E_ERROR);
header('Content-Type: application/json; charset=utf-8');

// inclui conexão
$pathConn = __DIR__ . '/../conexao.php';
if (! file_exists($pathConn)) {
    http_response_code(500);
    echo json_encode(['status'=>'error','msg'=>'Arquivo de conexão não encontrado.']);
    exit;
}
require_once $pathConn;

// 2) valida upload do JSON
if (empty($_FILES['arquivo']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','msg'=>'Nenhum arquivo enviado.']);
    exit;
}

$dados = json_decode(file_get_contents($_FILES['arquivo']['tmp_name']), true);
if (! isset($dados['Dados']) || ! is_array($dados['Dados'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','msg'=>'JSON inválido.']);
    exit;
}

// 3) grava cada bloco em um arquivo de etapa
$etapas = [];
foreach ($dados['Dados'] as $i => $bloco) {
    $fn   = uniqid('etapa_', true) . '.json';
    $dest = __DIR__ . '/dados/' . $fn;
    if (file_put_contents($dest, json_encode($bloco, JSON_UNESCAPED_UNICODE))) {
        $etapas[] = $fn;
    }
}

// opcional: lista de etapas
file_put_contents(__DIR__ . '/dados/etapas.txt', json_encode($etapas));

// resposta
echo json_encode([
    'status' => 'ok',
    'total'  => count($etapas),
    'etapas' => $etapas
]);
