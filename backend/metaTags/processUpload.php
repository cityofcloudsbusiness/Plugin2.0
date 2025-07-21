<?php
session_start();

// Recebe o upload via AJAX e salva em tmp/{session_id}.xml
if (!empty($_FILES['arquivo']['tmp_name'])) {
    $dir = __DIR__ . '/tmp';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $dest = "$dir/" . session_id() . ".xml";
    if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
        $_SESSION['import_file'] = $dest;
        http_response_code(200);
        echo "Arquivo carregado com sucesso.";
    } else {
        http_response_code(500);
        echo "Falha ao mover o arquivo.";
    }
} else {
    http_response_code(400);
    echo "Nenhum arquivo recebido.";
}
