<?php
include "../conexao.php";

// Força UTF-8 para os nomes aparecerem corretamente
mysqli_set_charset($con, 'utf8mb4');

// Busca todas as categorias
$sql = "SELECT categoryid, catparentid, catname FROM isc_categories ORDER BY catparentid ASC, catname ASC";
$res = $con->query($sql);

$categorias = [];
while ($row = $res->fetch_assoc()) {
    $categorias[$row['catparentid']][] = $row;
}

/**
 * Função para renderizar categorias com recuo (indetação)
 */
function renderizarOpcoes($parentId, $nivel, $tree) {
    if (!isset($tree[$parentId])) return;

    foreach ($tree[$parentId] as $cat) {
        $id = $cat['categoryid'];
        $nome = htmlspecialchars(trim($cat['catname']), ENT_QUOTES, 'UTF-8');
        
        // Se o nome sumiu por encoding, garantimos que apareça algo
        if (empty($nome)) $nome = "ID: $id (Sem nome)";

        // Calcula o recuo: 20px por nível de profundidade
        $recuo = $nivel * 20;
        
        echo "<div class='cat-item' style='padding: 5px 0; border-bottom: 1px solid #f9f9f9; margin-left: {$recuo}px;'>
                <label style='cursor: pointer; display: flex; align-items: center; gap: 8px;'>
                    <input type='checkbox' name='categoria[]' value='{$id}'> 
                    <span style='" . ($nivel == 0 ? "font-weight: bold;" : "") . "'>
                        " . ($nivel > 0 ? "↳ " : "") . "{$nome}
                    </span>
                    <small style='color:#ccc; margin-left: auto;'>#{$id}</small>
                </label>
              </div>";

        // Chama a função novamente para as filhas desta categoria
        renderizarOpcoes($id, $nivel + 1, $tree);
    }
}

// Cabeçalho do seletor
echo '<div style="margin-bottom: 10px; border-bottom: 2px solid #4CAF50; padding-bottom: 8px;">
        <label style="font-weight: bold; cursor: pointer; color: #4CAF50;">
            <input type="checkbox" id="selectAllCats"> [ SELECIONAR TODAS ]
        </label>
      </div>';

// Inicia a renderização a partir das categorias raiz (parent = 0)
renderizarOpcoes(0, 0, $categorias);

$con->close();