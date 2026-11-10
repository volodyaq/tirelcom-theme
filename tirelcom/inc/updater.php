<?php
/* Временный загрузчик описаний/характеристик. Удалить после загрузки. */
if (!defined('ABSPATH')) exit;
add_action('init', function () {
    if (!isset($_GET['tek_upd'])) return;
    $tok = get_option('tek_upd_tok');
    $ok = current_user_can('manage_options') || ($tok && isset($_GET['tok']) && hash_equals($tok, (string) $_GET['tok']));
    if (!$ok) return;
    @set_time_limit(120); ignore_user_abort(true);
    $act = sanitize_key($_GET['tek_upd']);
    $dir = wp_upload_dir()['basedir'] . '/tek-data';
    $out = function ($d) { nocache_headers(); header('Content-Type: application/json; charset=utf-8'); echo wp_json_encode($d, JSON_UNESCAPED_UNICODE); exit; };
    $names = ['00_test_50', 'novye_tovary', 'obnovlenie_01', 'obnovlenie_02', 'obnovlenie_03', 'obnovlenie_04', 'obnovlenie_05', 'obnovlenie_06', 'obnovlenie_07', 'predzakaz_status'];

    if ($act === 'fetch') {
        wp_mkdir_p($dir); $res = [];
        foreach ($names as $n) {
            $gz = "$dir/$n.csv.gz";
            $r = wp_remote_get("https://raw.githubusercontent.com/volodyaq/tirelcom-data/main/$n.csv.gz", ['timeout' => 90, 'stream' => true, 'filename' => $gz]);
            if (is_wp_error($r)) { $res[$n] = $r->get_error_message(); continue; }
            $fh = gzopen($gz, 'rb'); $o = fopen("$dir/$n.csv", 'wb');
            while (!gzeof($fh)) fwrite($o, gzread($fh, 1048576));
            gzclose($fh); fclose($o); @unlink($gz);
            $res[$n] = filesize("$dir/$n.csv");
        }
        $out($res);
    }
    if ($act === 'cleanup') {
        foreach (glob("$dir/*") ?: [] as $f) @unlink($f); @rmdir($dir);
        foreach (['tek_upd_tok', 'tek_upd_auto', 'tek_upd_state'] as $o) delete_option($o);
        $out(['cleaned' => !is_dir($dir)]);
    }
    if ($act === 'halt') { update_option('tek_upd_auto', 0, false); $out(['halted' => true]); }
    if ($act === 'status') $out(['state' => get_option('tek_upd_state'), 'auto' => get_option('tek_upd_auto')]);

    $step = function ($name, $pos, $lines) use ($dir) {
        require_once WC_ABSPATH . 'includes/import/abstract-wc-product-importer.php';
        require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
        $file = "$dir/$name.csv";
        $fh = fopen($file, 'r'); $head = fgetcsv($fh); fclose($fh);
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
        $map = [];
        $fixed = ['SKU' => 'sku', 'Name' => 'name', 'Short description' => 'short_description', 'Description' => 'description', 'In stock?' => 'stock_status', 'Meta: _tirelcom_status' => 'meta:_tirelcom_status', 'Type' => 'type', 'Published' => 'published', 'Visibility in catalog' => 'catalog_visibility', 'Regular price' => 'regular_price', 'Categories' => 'category_ids', 'Images' => 'images'];
        foreach ($head as $h) {
            if (isset($fixed[$h])) $map[] = $fixed[$h];
            elseif (preg_match('/^Attribute (\d+) name$/', $h, $m)) $map[] = 'attributes:name' . $m[1];
            elseif (preg_match('/^Attribute (\d+) value\(s\)$/', $h, $m)) $map[] = 'attributes:value' . $m[1];
            elseif (preg_match('/^Attribute (\d+) visible$/', $h, $m)) $map[] = 'attributes:visible' . $m[1];
            elseif (preg_match('/^Attribute (\d+) global$/', $h, $m)) $map[] = 'attributes:taxonomy' . $m[1];
            else $map[] = '';
        }
        $imp = new WC_Product_CSV_Importer($file, ['mapping' => $map, 'parse' => true, 'update_existing' => true, 'start_pos' => $pos, 'lines' => $lines, 'prevent_timeouts' => false]);
        $r = $imp->import();
        return ['res' => ['imported' => count($r['imported']), 'updated' => count($r['updated']), 'failed' => count($r['failed']), 'skipped' => count($r['skipped']), 'fail_msgs' => array_slice(array_map(function ($e) { return is_wp_error($e) ? $e->get_error_message() : (string) $e; }, $r['failed']), 0, 3)], 'pos' => $imp->get_file_position(), 'pct' => $imp->get_percent_complete()];
    };

    if ($act === 'test') {
        $r = $step('00_test_50', 0, 100); $out($r);
    }
    if ($act === 'start') {
        $t = wp_generate_password(24, false);
        update_option('tek_upd_tok', $t, false); update_option('tek_upd_auto', 1, false);
        update_option('tek_upd_state', ['i' => 1, 'pos' => 0, 'done' => false, 'tot' => ['imported' => 0, 'updated' => 0, 'failed' => 0, 'skipped' => 0]], false);
        wp_remote_get(add_query_arg(['tek_upd' => 'run', 'tok' => $t], home_url('/')), ['timeout' => 1, 'blocking' => false, 'sslverify' => false]);
        $out(['started' => true]);
    }
    if ($act === 'run') {
        $st = get_option('tek_upd_state');
        if (!$st || !empty($st['done']) || !get_option('tek_upd_auto')) $out(['stop' => true]);
        $t0 = microtime(true); $n = 0;
        do {
            $name = $names[$st['i']];
            $r = $step($name, $st['pos'], 30);
            foreach (['imported', 'updated', 'failed', 'skipped'] as $k) $st['tot'][$k] += $r['res'][$k];
            if (!empty($r['res']['fail_msgs'])) $st['err'] = $r['res']['fail_msgs'];
            $st['pos'] = $r['pos']; $st['file'] = $name; $st['pct'] = $r['pct'];
            if ($r['pct'] >= 100 || $r['res']['imported'] + $r['res']['updated'] + $r['res']['failed'] + $r['res']['skipped'] === 0) {
                $st['i']++; $st['pos'] = 0;
                if ($st['i'] >= count($names)) { $st['done'] = true; break; }
            }
            update_option('tek_upd_state', $st, false);
            $n++;
        } while ((microtime(true) - $t0) < 35);
        update_option('tek_upd_state', $st, false);
        if (empty($st['done'])) wp_remote_get(add_query_arg(['tek_upd' => 'run', 'tok' => $tok], home_url('/')), ['timeout' => 1, 'blocking' => false, 'sslverify' => false]);
        $out($st);
    }
    $out(['error' => 'unknown']);
});
