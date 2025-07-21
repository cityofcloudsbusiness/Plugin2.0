<?php
session_start();

// 1) cabeçalhos SSE — nenhum output antes disto!
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);
set_time_limit(0);

// 2) carrega config e delay
$config    = include __DIR__ . '/config.php';
$delay     = max(0, intval($config['siteTimeout']));
$headerRows= max(0, intval($config['headerRows']));

// 3) valida arquivo em sessão
if (empty($_SESSION['import_file']) || ! file_exists($_SESSION['import_file'])) {
    echo "event: error\n";
    echo "data: Arquivo não encontrado.\n\n";
    exit;
}

// 4) prepara XML
include __DIR__ . '/../conexao.php';
$dom  = new DOMDocument();
$dom->load($_SESSION['import_file']);

$rows = $dom->getElementsByTagName('Row');
// DEBUG: confirme no error.log quantas linhas o XML trouxe
error_log("Total de Rows no XML: " . $rows->length);

$total = max(0, $rows->length - $headerRows);
if ($total <= 0) {
    echo "event: error\n";
    echo "data: Sem linhas para processar.\n\n";
    exit;
}

// 5) prepare statements e inicie transação
$con->begin_transaction();

$stmtSelectCat = $con->prepare("
  SELECT categoryid, catparentid
    FROM isc_categories
   WHERE catname = ?
");

$stmtInsertCat = $con->prepare("
  INSERT INTO isc_categories (
    catparentid, catname, catpagetitle,
    catmetakeywords, catmetadesc,
    catlayoutfile, catvisible, catsearchkeywords
  ) VALUES (?, ?, ?, ?, ?, 'category.html', 1, ?)
");

$stmtInsertSearch = $con->prepare("
  INSERT INTO isc_category_search (
    categoryid, catname, catsearchkeywords
  ) VALUES (?, ?, ?)
");

$stmtInsertWord = $con->prepare("
  INSERT INTO isc_category_words (
    word, categoryid
  ) VALUES (?, ?)
");

$cacheCat = [];

// 6) envie 0%
echo "event: progress\n";
echo "data: 0\n\n";
flush();

// 7) itere sobre cada Row de dados
for ($i = $headerRows; $i < $rows->length; $i++) {
    $row = $rows->item($i);

    // leia até 7 colunas, cai em '' se não existir
    $cells = $row->getElementsByTagName('Data');
    $data = [];
    for ($c = 0; $c < 7; $c++) {
        $node = $cells->item($c);
        $data[$c] = $node ? $node->nodeValue : '';
    }
    list($_id, $catName, $_fone, $metaDesc, $pageTitle, $keywords, $parentName) = $data;
    $catName    = trim($catName);
    $parentName = trim($parentName);

    // 7.1) busca ou insere categoria
    if (! isset($cacheCat[$catName])) {
        $stmtSelectCat->bind_param('s', $catName);
        $stmtSelectCat->execute();
        $res = $stmtSelectCat->get_result();

        if ($res->num_rows) {
            $r = $res->fetch_assoc();
            $cacheCat[$catName] = [(int)$r['categoryid'], (int)$r['catparentid']];
        } else {
            // determina parentId
            $parentId = 0;
            if ($parentName !== '0') {
                if (! isset($cacheCat[$parentName])) {
                    $stmtSelectCat->bind_param('s', $parentName);
                    $stmtSelectCat->execute();
                    $row2 = $stmtSelectCat->get_result()->fetch_assoc();
                    $cacheCat[$parentName] = $row2
                      ? [(int)$row2['categoryid'], (int)$row2['catparentid']]
                      : [0, 0];
                }
                $parentId = $cacheCat[$parentName][0];
            }
            // insere
            $stmtInsertCat->bind_param(
              'isssss',
              $parentId,    // i
              $catName,     // s
              $pageTitle,   // s
              $keywords,    // s
              $metaDesc,    // s
              $keywords     // s → catsearchkeywords
            );
            $stmtInsertCat->execute();
            $newId = $con->insert_id;
            $cacheCat[$catName] = [$newId, $parentId];
        }
    }

    // 7.2) recupere ids
    list($catId, $catParentId) = $cacheCat[$catName];

    // 7.3) insira em category_search
    $stmtInsertSearch->bind_param('iss', $catId, $catName, $keywords);
    $stmtInsertSearch->execute();

    // 7.4) insira em words, se tiver parent
    if ($catParentId > 0) {
        $stmtInsertWord->bind_param('si', $parentName, $catId);
        $stmtInsertWord->execute();
    }

    // 7.5) envie progresso
    $done = $i - $headerRows + 1;
    $pct  = intval($done / $total * 100);
    echo "event: progress\n";
    echo "data: {$pct}\n\n";
    flush();

    // 7.6) delay opcional
    if ($delay > 0) {
        usleep($delay * 1000);
    }
}

// 8) commit e complete
$con->commit();
echo "event: complete\n";
echo "data: 100\n\n";
exit;
