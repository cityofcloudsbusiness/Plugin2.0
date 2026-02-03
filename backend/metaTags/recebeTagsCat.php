<?php
// Server-Sent Events endpoint para atualizar meta-tags de categorias
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// Desativa buffers do PHP
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);
ini_set('zlib.output_compression', 'Off');
set_time_limit(0);

include "../conexao.php";

// lê parâmetros via GET
$num1      = filter_input(INPUT_GET,  'num1',      FILTER_VALIDATE_INT);
$num2      = filter_input(INPUT_GET,  'num2',      FILTER_VALIDATE_INT);
$cidades   = json_decode($_GET['cidades']   ?? '[]', true);
$telefones = json_decode($_GET['telefones'] ?? '[]', true);

if ($num1 === false || $num2 === false) {
    echo "event: error\n";
    echo "data: Parâmetros inválidos\n\n";
    exit;
}

// carrega todas as categorias em memória
$cats = [];
$res = $con->query("SELECT categoryid, catparentid, catmetadesc FROM isc_categories");
while ($r = $res->fetch_assoc()) {
    $cats[(int)$r['categoryid']] = [
        'parent'      => (int)$r['catparentid'],
        'description' => $r['catmetadesc']
    ];
}
function getMetaDesc(int $cid, array $cats): string {
    if (!isset($cats[$cid])) return '';
    while (isset($cats[$cid]) && $cats[$cid]['parent'] !== 0) {
        $cid = $cats[$cid]['parent'];
    }
    return $cats[$cid]['description'] ?? '';
}

// busca todas as categorias
$qr = $con->query("SELECT categoryid, catname, catparentid FROM isc_categories");
$total = $qr->num_rows;
$atual = 0;

while ($row = $qr->fetch_assoc()) {
    $atual++;
    $percent = intval($atual / $total * 100);

    $idcat    = (int)$row['categoryid'];
    $nome     = $row['catname'];
    $metaDesc = getMetaDesc((int)$row['catparentid'], $cats);

    // títulos e keywords
    $titulo   = $nome . ' ' . implode(' ', $telefones) . ' ' . implode(' ', $cidades);
    $keywords = $nome . ', ' . implode(', ', array_map(fn($c)=>"$nome $c", $cidades));

    // único UPDATE
    $upd = $con->prepare("
      UPDATE isc_categories
         SET catpagetitle      = ?,
             catmetakeywords   = ?,
             catsearchkeywords = ?,
             catmetadesc       = ?
       WHERE categoryid       = ?
    ");
    $upd->bind_param('ssssi', $titulo, $keywords, $keywords, $metaDesc, $idcat);
    $upd->execute();

    // envia progresso
    echo "event: progress\n";
    echo "data: {$percent}\n\n";
    @flush();
}

// evento complete
echo "event: complete\n";
echo "data: 100\n\n";
exit;
