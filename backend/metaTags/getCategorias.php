<?php
// continua sendo getCategorias.php
include __DIR__ . '/../conexao.php';

// 1) busca todas as categorias e monta array por parent
$res = $con->query("
  SELECT categoryid, catname, catparentid
  FROM isc_categories
  ORDER BY catparentid ASC, catname ASC
");
$cats = [];
while ($r = $res->fetch_assoc()) {
    $cats[$r['catparentid']][] = $r;
}

// 2) função recursiva para imprimir a árvore inteira
function renderTree($cats, $parent = 0, $nivel = 0) {
    if (!isset($cats[$parent])) return;
    foreach ($cats[$parent] as $c) {
        $indent = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $nivel);
        echo "<label class='cat-label nivel-{$nivel}'>";
        echo "  <input type='checkbox' name='categoria[]' value='{$c['categoryid']}'>";
        echo "  <span>{$indent}" . htmlspecialchars($c['catname']) . "</span>";
        echo "</label>";
        renderTree($cats, $c['categoryid'], $nivel + 1);
    }
}

// 3) imprime tudo dentro do contêiner
echo "<div id='categoriaContainer' class='categoria-wrapper'>";
renderTree($cats);
echo "</div>";
