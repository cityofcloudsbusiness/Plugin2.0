<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método inválido.');
}

include '../conexao.php';
$config = include 'config.php';
$delay = intval($config['siteTimeout']);
$siteOrigem = rtrim($config['siteOrigem'], '/') . '/';

if (!isset($_FILES['arquivo']['tmp_name'])) {
    exit("Arquivo não enviado.");
}

$data = json_decode(file_get_contents($_FILES['arquivo']['tmp_name']), true);
if (!isset($data['Dados'])) {
    exit("JSON inválido.");
}

foreach ($data['Dados'] as $bloco) {
    foreach ($bloco['produtos'] as $prod) {
        $nome = $prod['prodname'];
        $url  = $prod['produrl'];
        $cat  = $prod['prodcatids'];

        $con->query("INSERT INTO isc_products (prodname, produrl, prodcatids) VALUES ('$nome', '$url', '$cat')");
        $idprod = $con->insert_id;

        foreach ($prod['imagens'] as $img) {
            $imgfile = $img['imagefile'];
            $urlImg = $siteOrigem . 'product_images/' . $imgfile;
            $path = realpath(__DIR__ . '/../../../product_images') . '/' . $imgfile;

            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, file_get_contents($urlImg));
            $con->query("INSERT INTO isc_product_images (imageprodid, imagefile) VALUES ($idprod, '$imgfile')");
        }

        usleep($delay * 1000); // respeita o config.php
    }
}

echo "Importação realizada com sucesso!";
