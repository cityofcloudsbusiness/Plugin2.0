<?php
// backend/metaTags/precos.php
/**
 * Ações:
 *  - ?action=list_categories
 *  - ?action=update_prices&mode=all|category&percent=10[&id=<catid>]
 *  - ?action=reset_prices&mode=all|category[&id=<catid>]
 *
 * Regras:
 *  - Base de cálculo: prodprice
 *  - Novo preço: prodcalculatedprice
 *  - Backup: preco_original (se NULL, vira prodprice na 1ª execução do escopo)
 *  - Limite de velocidade: usa siteTimeout (ms) do config.php por padrão.
 *    Opcionalmente pode ser sobreposto por delay_ms e pause_every na URL.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
set_time_limit(0);

require_once __DIR__ . '/../conexao.php';

// ---- Config ----
$cfg = [];
$cfgFile = __DIR__ . '/config.php';
if (is_file($cfgFile)) {
    $tmp = require $cfgFile;
    if (is_array($tmp)) $cfg = $tmp;
}
$defaultDelayMs = isset($cfg['siteTimeout']) ? max(0, (int)$cfg['siteTimeout']) : 0;

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
error_log("[precos.php] action={$action}");

if (in_array($action, ['update_prices','reset_prices'], true)) {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    while (ob_get_level()) @ob_end_flush();
    ob_implicit_flush(1);
}

function sse_send(string $data): void { echo "data: {$data}\n\n"; @ob_flush(); @flush(); }
function table_exists(mysqli $con, string $name): bool {
    $name = $con->real_escape_string($name);
    $rs = $con->query("SHOW TABLES LIKE '{$name}'");
    return $rs && $rs->num_rows > 0;
}

switch ($action) {

    case 'list_categories':
        header('Content-Type: application/json; charset=utf-8');
        $stmt = $con->prepare("SELECT categoryid, catname FROM isc_categories ORDER BY catname ASC");
        if (!$stmt) { http_response_code(500); echo json_encode(['error'=>'prep cats']); exit; }
        $stmt->execute();
        $res = $stmt->get_result();
        $cats = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode($cats);
        if ($res) $res->free(); $stmt->close();
        error_log("[precos.php] list_categories -> ".count($cats));
    break;

    case 'update_prices':
    case 'reset_prices': {
        $mode    = $_GET['mode']    ?? 'all'; // 'all'|'category'
        $catId   = (int)($_GET['id'] ?? 0);
        $percent = (float)str_replace(',', '.', ($_GET['percent'] ?? 0)); // só em update
        $isReset = ($action === 'reset_prices');

        // Controle de velocidade (GET sobrescreve config; ambos opcionais)
        $delayMs    = isset($_GET['delay_ms']) ? max(0, (int)$_GET['delay_ms']) : $defaultDelayMs; // ms
        $pauseEvery = isset($_GET['pause_every']) ? max(1, (int)$_GET['pause_every']) : 1;         // 1 = dormir a cada item
        error_log("[precos.php] throttle delayMs={$delayMs} pauseEvery={$pauseEvery}");

        // Detecta tabela de associação (para categoria)
        $assoc = null;
        if ($mode === 'category') {
            foreach (['isc_categoryassociations','isc_product_categories','isc_categories_products'] as $t) {
                if (table_exists($con, $t)) { $assoc = $t; break; }
            }
            if (!$assoc) { sse_send("error:Sem tabela de associação."); exit; }
            if ($catId <= 0) { sse_send("error:categoryid inválido."); exit; }
        }

        // (1) Garantir backup: preco_original = prodprice onde estiver NULL (apenas no escopo)
        if ($mode === 'category') {
            $sqlInit = "UPDATE isc_products p
                          JOIN {$assoc} a ON a.productid = p.productid
                           SET p.preco_original = p.prodprice
                         WHERE a.categoryid = ? AND p.preco_original IS NULL";
            $init = $con->prepare($sqlInit);
            $init->bind_param('i', $catId);
        } else {
            $sqlInit = "UPDATE isc_products p
                           SET p.preco_original = p.prodprice
                         WHERE p.preco_original IS NULL";
            $init = $con->prepare($sqlInit);
        }
        if ($init && !$init->execute()) { error_log("[precos.php] WARN init preco_original: ".$init->error); }
        if ($init) $init->close();

        // (2) Buscar produtos do escopo, com prodprice e preco_original
        if ($mode === 'category') {
            $sql = "SELECT p.productid, p.prodprice, p.preco_original
                      FROM isc_products p
                      JOIN {$assoc} a ON a.productid = p.productid
                     WHERE a.categoryid = ?";
            $stmt = $con->prepare($sql);
            if (!$stmt) { sse_send("error:prep produtos (categoria)."); exit; }
            $stmt->bind_param('i', $catId);
        } else {
            $sql = "SELECT p.productid, p.prodprice, p.preco_original FROM isc_products p";
            $stmt = $con->prepare($sql);
            if (!$stmt) { sse_send("error:prep produtos (all)."); exit; }
        }
        if (!$stmt->execute()) { sse_send("error:exec produtos."); $stmt->close(); exit; }
        $res = $stmt->get_result();
        if (!$res) { sse_send("error:get_result."); $stmt->close(); exit; }
        $prods = $res->fetch_all(MYSQLI_ASSOC);
        $total = count($prods);
        if ($total === 0) { sse_send("error:Nenhum produto para processar."); $res->free(); $stmt->close(); exit; }

        sse_send("init:{$total}");
        error_log("[precos.php] {$action} total={$total} mode={$mode} catId={$catId}");

        // (3) UPDATE em prodcalculatedprice
        $upd = $con->prepare("UPDATE isc_products SET prodcalculatedprice=? WHERE productid=?");
        if (!$upd) { sse_send("error:prep UPDATE."); $res->free(); $stmt->close(); exit; }

        foreach ($prods as $i => $row) {
            $pid   = (int)$row['productid'];
            $base  = (float)$row['prodprice']; // base SEMPRE prodprice
            $orig  = $row['preco_original'] !== null ? (float)$row['preco_original'] : $base;

            if ($isReset) {
                $novo = $orig; // volta ao original (prioriza backup; se vazio, cai no prodprice)
            } else {
                $novo = round($base * (1.0 + ($percent/100.0)), 2);
            }

            $upd->bind_param('di', $novo, $pid);
            if (!$upd->execute()) {
                error_log("[precos.php] ERRO update pid={$pid}: ".$upd->error);
            } else {
                if ($isReset) {
                    error_log("[precos.php] pid={$pid} reset → prodcalculatedprice={$novo} (orig={$orig})");
                } else {
                    error_log("[precos.php] pid={$pid} base={$base} %={$percent} novo={$novo} → prodcalculatedprice");
                }
            }

            $pct = number_format((($i+1)/$total)*100.0, 2, '.', '');
            sse_send("progress:{$pct}");

            // Throttle por config/GET
            if ($delayMs > 0) {
                if ($pauseEvery <= 1 || ((($i + 1) % $pauseEvery) === 0)) {
                    usleep($delayMs * 1000);
                }
            }
        }

        sse_send("done");
        $res->free(); $stmt->close(); $upd->close();
        error_log("[precos.php] {$action} DONE");
    } break;

    default:
        header('Content-Type: text/plain; charset=utf-8');
        echo "Uso:\n".
             "  GET ?action=list_categories\n".
             "  GET ?action=update_prices&mode=all|category&percent=10[&id=...]\n".
             "  GET ?action=reset_prices&mode=all|category[&id=...]\n";
    break;
}
