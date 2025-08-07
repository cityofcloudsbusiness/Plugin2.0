<?php
// backend/metaTags/importar.php

session_start();

// mostrar apenas erros fatais
error_reporting(E_ERROR);
header('Content-Type: application/json; charset=utf-8');

// 0) aplicar timeout do config.php
$config = require __DIR__ . '/config.php';
if (! empty($config['siteTimeout'])) {
    set_time_limit((int)$config['siteTimeout']);
}

// 1) incluir conexão
$pathConn = __DIR__ . '/../conexao.php';
if (! file_exists($pathConn)) {
    echo json_encode(['status'=>'error','msg'=>'Arquivo de conexão não encontrado.']);
    exit;
}
require_once $pathConn;
$conn = $con; // seu conexao.php fornece $con
if (! ($conn instanceof mysqli)) {
    echo json_encode(['status'=>'error','msg'=>'Conexão inválida.']);
    exit;
}

// 1.1) verifica se existe a tabela de associação produto↔categoria
$res = $conn->query("SHOW TABLES LIKE 'isc_categoryassociations'");
$temAssociacoes = ($res && $res->num_rows > 0);

// 2) parâmetro etapa
if (empty($_POST['etapa'])) {
    echo json_encode(['status'=>'error','msg'=>'Parâmetro etapa não informado']);
    exit;
}
$etapaFile = basename($_POST['etapa']);
$etapaPath = __DIR__ . '/dados/' . $etapaFile;
if (! file_exists($etapaPath)) {
    echo json_encode(['status'=>'error','msg'=>"Arquivo {$etapaFile} não encontrado em dados/"]);
    exit;
}

// 3) lê JSON
$bloco = json_decode(file_get_contents($etapaPath), true);
if (! is_array($bloco) || ! isset($bloco['Tipo'])) {
    unlink($etapaPath);
    echo json_encode(['status'=>'error','msg'=>"JSON inválido em {$etapaFile}"]);
    exit;
}

// inicializa mapas
if (! isset($_SESSION['map_cat']))  $_SESSION['map_cat']  = [];
if (! isset($_SESSION['map_prod'])) $_SESSION['map_prod'] = [];

function esc($v) {
    global $conn;
    return "'" . $conn->real_escape_string($v) . "'";
}

// pasta de imagens
$siteRoot  = realpath(__DIR__ . '/../../../../');
$uploadDir = $siteRoot . '/product_images';
if (! is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 4) processa por tipo
$type = (int)$bloco['Tipo'];

if ($type === 1) {
    // CATEGORIA
    $c         = $bloco['categoria'];
    $oldCat    = (int)$c['categoryid'];
    $oldParent = (int)$c['catparentid'];
    $newParent = $oldParent
        ? ($_SESSION['map_cat'][$oldParent] ?? 0)
        : 0;

    $sql = "
        INSERT INTO isc_categories
            (catparentid, catname, catdesc, catlayoutfile, catvisible)
        VALUES
            (
             $newParent,
             " . esc($c['catname']) . ",
             " . esc($c['catdesc']) . ",
             " . esc($c['catlayoutfile']) . ",
             " . ((int)$c['catvisible']) . "
            )";
    $conn->query($sql);
    $_SESSION['map_cat'][$oldCat] = $conn->insert_id;
}
elseif ($type === 2) {
    // PRODUTOS + IMAGENS EMBUTIDAS
    foreach ($bloco['produtos'] as $item) {
        $p = $item['produto'] ?? null;
        if (! $p) continue;

        // nome do produto
        $rawName = trim($p['prodname'] ?? '');
        if ($rawName === '') {
            $rawName = uniqid('produto_');
        }

        // INSERT IGNORE evita duplicar
        $sqlP = "
          INSERT IGNORE INTO isc_products
            (prodname, proddesc, prodprice, prodcostprice, prodvisible)
          VALUES
            (
              " . esc($rawName) . ",
              " . esc($p['proddesc']      ?? '') . ",
              " . esc($p['prodprice']     ?? 0)  . ",
              " . esc($p['prodcostprice'] ?? 0)  . ",
              " . ((int)$p['prodvisible'] ?? 0)  . "
            )";
        $conn->query($sqlP);

        if ($conn->affected_rows > 0) {
            $newProd = $conn->insert_id;
        } else {
            // já existia → buscar ID
            $stmt = $conn->prepare(
              "SELECT productid FROM isc_products WHERE prodname = ?"
            );
            $stmt->bind_param('s', $rawName);
            $stmt->execute();
            $stmt->bind_result($newProd);
            $stmt->fetch();
            $stmt->close();
        }

        $_SESSION['map_prod'][(int)($p['productid'] ?? 0)] = $newProd;

        // sempre (re)associa categorias
        if ($temAssociacoes) {
            foreach (explode(',', $p['prodcatids'] ?? '') as $ocat) {
                $ocat = (int)$ocat;
                if (isset($_SESSION['map_cat'][$ocat])) {
                    $nc = $_SESSION['map_cat'][$ocat];
                    $conn->query("
                      INSERT IGNORE INTO isc_categoryassociations
                        (productid, categoryid)
                      VALUES ($newProd, $nc)
                    ");
                }
            }
        }

        // imagens embutidas
        foreach ($item['imagens'] ?? [] as $img) {
            $url = $img['imagefile'] ?? '';
            if (! $url) continue;
            $tmp = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tmp, file_get_contents($url));

            $fname     = basename(parse_url($url, PHP_URL_PATH));
            $firstChar = substr($fname, 0, 1);
            $chunk     = substr($fname, 1, 3);
            $dstDir    = "$uploadDir/all/$firstChar/$chunk";
            if (! is_dir($dstDir)) mkdir($dstDir, 0755, true);

            $dst = "$dstDir/$fname";
            rename($tmp, $dst);

            $dbPath  = str_replace('\\','/', substr($dst, strlen($siteRoot)));
            $isThumb = (int)($img['imageisthumb'] ?? 0);
            $conn->query("
              INSERT INTO isc_product_images
                (imageprodid, imagefile, imageisthumb)
              VALUES
                ($newProd, " . esc($dbPath) . ", $isThumb)
            ");
        }
    }
}

// 5) apaga o JSON de etapa
unlink($etapaPath);

// 6) responde OK
echo json_encode(['status'=>'ok']);
