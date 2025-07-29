<?php
include '../conexao.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 300;
$offset = ($page - 1) * $perPage;

// Conta total
$totalRes = $con->query("SELECT COUNT(*) as total FROM isc_categories");
$totalRow = $totalRes->fetch_assoc();
$total = intval($totalRow['total']);
$pages = ceil($total / $perPage);

// Carrega categorias paginadas
$res = $con->query("SELECT * FROM isc_categories ORDER BY catparentid, catname LIMIT $offset, $perPage");

$categorias = [];
while ($cat = $res->fetch_assoc()) {
    $categorias[$cat['categoryid']] = $cat;
}

function exibirCategoria($id, $categorias, $nivel = 0) {
    if (!isset($categorias[$id])) return '';

    $espaco = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $nivel);
    $html = "<label class='checkbox-cat' data-nivel='$nivel'>
                <input type='checkbox' name='categoria[]' value='{$id}'>
                <span>{$espaco}{$categorias[$id]['catname']}</span>
             </label>";

    // Render filhos
    foreach ($categorias as $cat) {
        if ($cat['catparentid'] == $id) {
            $html .= exibirCategoria($cat['categoryid'], $categorias, $nivel + 1);
        }
    }

    return $html;
}

// Primeiro renderiza apenas categorias "raiz" do lote atual
$html = '';
foreach ($categorias as $cat) {
    if ($cat['catparentid'] == 0) {
        $html .= exibirCategoria($cat['categoryid'], $categorias);
    }
}

$html .= "<div class='paginacao'>";
if ($page > 1) {
    $html .= "<button class='pag-link' data-page='" . ($page - 1) . "'>Anterior</button>";
}
for ($i = 1; $i <= $pages; $i++) {
    $active = ($i == $page) ? 'active' : '';
    $html .= "<button class='pag-link $active' data-page='$i'>$i</button>";
}
if ($page < $pages) {
    $html .= "<button class='pag-link' data-page='" . ($page + 1) . "'>Próximo</button>";
}
$html .= "</div>";

echo $html;
