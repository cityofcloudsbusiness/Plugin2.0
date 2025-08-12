<?php
// backend/metaTags/tags.php
/**
 * Ações:
 *  - ?action=count  → JSON { tags_total, assoc_total }
 *  - ?action=purge  → SSE: apaga TODAS as meta tags de produtos em lotes
 *
 * Tabelas:
 *  - principal: isc_product_tags
 *  - associação (se existir): isc_product_tagassociations
 *
 * Controle de velocidade:
 *  - Usa siteTimeout (ms) de backend/metaTags/config.php (padrão)
 *  - Pode sobrepor via ?delay_ms=xxx
 *  - Lote padrão: 500 (troque com ?batch=1000)
 *
 * SSE frames: init:<total>, progress:<xx.xx>, done, error:<msg>
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
set_time_limit(0);

require_once __DIR__ . '/../conexao.php';

// ---- Config (throttle) ----
$cfg = [];
$cfgFile = __DIR__ . '/config.php';
if (is_file($cfgFile)) {
    $tmp = require $cfgFile;
    if (is_array($tmp)) $cfg = $tmp;
}
$defaultDelayMs = isset($cfg['siteTimeout']) ? max(0, (int)$cfg['siteTimeout']) : 0;

function sse_headers() {
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

$action = $_GET['action'] ?? '';
if ($action === 'purge') { sse_headers(); }

$tagsTable  = 'isc_product_tags';
$assocTable = 'isc_product_tagassociations';
$hasAssoc   = table_exists($con, $assocTable);

switch ($action) {
    case 'count':
        header('Content-Type: application/json; charset=utf-8');
        $tags_total = 0; $assoc_total = 0;
        if ($q = $con->query("SELECT COUNT(*) AS c FROM {$tagsTable}")) {
            $r = $q->fetch_assoc(); $tags_total = (int)$r['c']; $q->free();
        }
        if ($hasAssoc && ($q = $con->query("SELECT COUNT(*) AS c FROM {$assocTable}"))) {
            $r = $q->fetch_assoc(); $assoc_total = (int)$r['c']; $q->free();
        }
        echo json_encode(['tags_total'=>$tags_total,'assoc_total'=>$assoc_total]);
    break;

    case 'purge':
        $batch   = isset($_GET['batch']) ? max(1, (int)$_GET['batch']) : 500;
        $delayMs = isset($_GET['delay_ms']) ? max(0, (int)$_GET['delay_ms']) : $defaultDelayMs;

        // total inicial
        $total = 0;
        if ($q = $con->query("SELECT COUNT(*) AS c FROM {$tagsTable}")) {
            $r = $q->fetch_assoc(); $total = (int)$r['c']; $q->free();
        }
        sse_send("init:{$total}");
        if ($total === 0) { sse_send("done"); exit; }

        $processed = 0;

        while (true) {
            $ids = [];
            $rs = $con->query("SELECT tagid FROM {$tagsTable} ORDER BY tagid ASC LIMIT {$batch}");
            if ($rs) { while ($row = $rs->fetch_assoc()) { $ids[] = (int)$row['tagid']; } $rs->free(); }
            if (!$ids) break;

            $idList = implode(',', $ids);

            $con->begin_transaction();
            try {
                if ($hasAssoc) {
                    $con->query("DELETE FROM {$assocTable} WHERE tagid IN ({$idList})");
                }
                $con->query("DELETE FROM {$tagsTable} WHERE tagid IN ({$idList})");
                $con->commit();
            } catch (Throwable $e) {
                $con->rollback();
                sse_send("error:Falha ao apagar tags: ".$e->getMessage());
                exit;
            }

            $processed += count($ids);
            $pct = number_format(($processed / $total) * 100, 2, '.', '');
            sse_send("progress:{$pct}");

            if ($delayMs > 0) { usleep($delayMs * 1000); }
        }

        sse_send("done");
    break;

    default:
        header('Content-Type: text/plain; charset=utf-8');
        echo "Uso:\n".
             "  GET ?action=count\n".
             "  GET ?action=purge[&batch=500][&delay_ms=0]\n";
    break;
}
