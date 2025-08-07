<?php
// backend/metaTags/importar.php

session_start();
// mostrar só erros graves
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

error_log("=== IMPORTAR.PHP INICIADO ===");

// 1) conexão
$pathConn = __DIR__ . '/../conexao.php';
error_log("1) Verificando conexão em $pathConn");
if (! file_exists($pathConn)) {
    error_log("ERRO: conexao.php não encontrado");
    echo json_encode(['status'=>'error','msg'=>'Arquivo de conexão não encontrado.']);
    exit;
}
require_once $pathConn;
$conn = $con;
if (! ($conn instanceof mysqli)) {
    error_log("ERRO: \$con não é instância de mysqli");
    echo json_encode(['status'=>'error','msg'=>'Conexão inválida.']);
    exit;
}
error_log("Conexão estabelecida com sucesso");

// 1.1) checa tabela de associação produto↔categoria
$res = $conn->query("SHOW TABLES LIKE 'isc_categoryassociations'");
$hasProdCatTable = ($res && $res->num_rows > 0);
error_log("Tabela isc_categoryassociations " . ($hasProdCatTable ? "EXISTE" : "NÃO existe"));

// 2) recebe parâmetro etapa
error_log("2) Validando parâmetro etapa");
if (empty($_POST['etapa'])) {
    error_log("ERRO: parâmetro etapa vazio");
    echo json_encode(['status'=>'error','msg'=>'Parâmetro etapa não informado']);
    exit;
}
$etapaFile = basename($_POST['etapa']);
$etapaPath = __DIR__ . '/dados/' . $etapaFile;
error_log("Parâmetro etapa: $etapaFile → $etapaPath");
if (! file_exists($etapaPath)) {
    error_log("ERRO: arquivo de etapa não encontrado");
    echo json_encode(['status'=>'error','msg'=>"Arquivo {$etapaFile} não encontrado em dados/"]);
    exit;
}

// 3) lê e decodifica JSON
error_log("3) Lendo JSON da etapa");
$bloco = json_decode(file_get_contents($etapaPath), true);
if (! is_array($bloco) || ! isset($bloco['Tipo'])) {
    error_log("ERRO: JSON inválido ou sem campo Tipo");
    unlink($etapaPath);
    echo json_encode(['status'=>'error','msg'=>"JSON inválido em {$etapaFile}"]);
    exit;
}
error_log("JSON decodificado: Tipo={$bloco['Tipo']}");

// inicializa mapas de ID
if (! isset($_SESSION['map_cat']))  $_SESSION['map_cat']  = [];
if (! isset($_SESSION['map_prod'])) $_SESSION['map_prod'] = [];

// helper de escape
function esc($v) {
    global $conn;
    return "'" . $conn->real_escape_string($v) . "'";
}

// prepara pasta de imagens
$siteRoot  = realpath(__DIR__ . '/../../../../');
$uploadDir = $siteRoot . '/product_images';
if (! is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    error_log("Criou pasta de imagens: $uploadDir");
} else {
    error_log("Pasta de imagens existe: $uploadDir");
}

// 4) processa por tipo
$type = (int)$bloco['Tipo'];
error_log("4) Iniciando processamento do bloco Tipo $type");

switch ($type) {

  case 1:
    // CATEGORIA
    error_log("  → Inserindo categoria");
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
        ($newParent,
         " . esc($c['catname']) . ",
         " . esc($c['catdesc']) . ",
         " . esc($c['catlayoutfile']) . ",
         " . ((int)$c['catvisible']) . ");
    ";
    if (! $conn->query($sql)) {
        error_log("SQL ERROR categoria: " . $conn->error);
    } else {
        $newId = $conn->insert_id;
        $_SESSION['map_cat'][$oldCat] = $newId;
        error_log("Categoria antiga $oldCat → nova $newId");
    }
    break;

  case 2:
    // PRODUTOS + IMAGENS
    error_log("  → Inserindo produtos e imagens embutidas");
    if (! empty($bloco['produtos']) && is_array($bloco['produtos'])) {
      foreach ($bloco['produtos'] as $idx => $item) {
        error_log("    → Produto #$idx");
        if (! isset($item['produto'])) {
          error_log("       ERRO: falta chave 'produto'");
          continue;
        }
        $p = $item['produto'];

        // --- INSERE OU RECUPERA O PRODUTO ---
        $rawName = trim($p['prodname'] ?? '');
        if ($rawName === '') {
          $rawName = uniqid('produto_');
          error_log("       Nome vazio → gerado '$rawName'");
        }

        // tenta inserir, mas ignora se já existir
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
            );
        ";
        $conn->query($sqlP);

        if ($conn->affected_rows > 0) {
          // foi um insert novo
          $newProd = $conn->insert_id;
          error_log("       Produto inserido id=$newProd");
        } else {
          // já existia: recupera o ID
          $stmt = $conn->prepare(
            "SELECT productid FROM isc_products WHERE prodname = ?"
          );
          $stmt->bind_param('s', $rawName);
          $stmt->execute();
          $stmt->bind_result($newProd);
          $stmt->fetch();
          $stmt->close();
          error_log("       Produto já existia, recuperado id=$newProd");
        }

        // guarda no mapa
        $_SESSION['map_prod'][(int)($p['productid'] ?? 0)] = $newProd;

        // --- ASSOCIA CATEGORIAS SEMPRE ---
        if ($hasProdCatTable) {
          foreach (explode(',', $p['prodcatids'] ?? '') as $ocat) {
            $ocat = (int)$ocat;
            if (isset($_SESSION['map_cat'][$ocat])) {
              $nc = $_SESSION['map_cat'][$ocat];
              $conn->query("
                INSERT IGNORE INTO isc_categoryassociations
                  (productid, categoryid)
                VALUES ($newProd, $nc)
              ");
              error_log("       Associou produto $newProd → categoria $nc");
            }
          }
        } else {
          error_log("       Pulando associações: tabela ausente");
        }

        // --- PROCESSA IMAGENS EMBUTIDAS ---
        foreach (($item['imagens'] ?? []) as $j => $img) {
          $urlFile = $img['imagefile'] ?? '';
          if (! $urlFile) {
            error_log("       Imagem #$j sem URL");
            continue;
          }
          error_log("       → Imagem #$j URL=$urlFile");

          // baixa temporário
          $tmp = tempnam(sys_get_temp_dir(), 'img');
          file_put_contents($tmp, file_get_contents($urlFile));

          // destina em disco
          $fname     = basename(parse_url($urlFile, PHP_URL_PATH));
          $firstChar = substr($fname, 0, 1);
          $chunk     = substr($fname, 1, 3);
          $dstDir    = "$uploadDir/all/$firstChar/$chunk";
          if (! is_dir($dstDir)) {
            mkdir($dstDir, 0755, true);
            error_log("          Criou pasta $dstDir");
          }
          $dst = "$dstDir/$fname";
          rename($tmp, $dst);
          error_log("          Gravou $dst");

          // grava no DB
          $dbPath  = str_replace('\\','/', substr($dst, strlen($siteRoot)));
          $isThumb = (int)($img['imageisthumb'] ?? 0);
          $conn->query("
            INSERT INTO isc_product_images
              (imageprodid, imagefile, imageisthumb)
            VALUES
              ($newProd, " . esc($dbPath) . ", $isThumb)
          ");
          error_log("          Imagem cadastrada");
        }
      }
    } else {
      error_log("    ERRO: bloco sem array 'produtos'");
    }
    break;

  default:
    error_log("Tipo $type não tratado");
    break;
}

// 5) remove o JSON de etapa processado
error_log("5) Apagando etapa $etapaPath");
unlink($etapaPath);

// 6) responde OK
echo json_encode(['status'=>'ok']);
error_log("=== IMPORTAR.PHP FINALIZADO ===\n");
