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

    $translit = function ($s) {
        $m = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'];
        $s = mb_strtolower(trim($s));
        $s = strtr($s, $m);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim(substr($s, 0, 20), '-') ?: 'attr' . substr(md5($s), 0, 6);
    };
    $taxcache = [];
    $get_tax = function ($label) use (&$taxcache, $translit) {
        if (isset($taxcache[$label])) return $taxcache[$label];
        $slug = $translit($label);
        $tax = 'pa_' . $slug;
        $aid = wc_attribute_taxonomy_id_by_name($slug);
        if (!$aid) {
            $aid = wc_create_attribute(['name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false]);
            if (is_wp_error($aid)) return $taxcache[$label] = null;
        }
        if (!taxonomy_exists($tax)) register_taxonomy($tax, 'product', ['hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false]);
        return $taxcache[$label] = [$tax, (int) $aid];
    };
    $catcache = [];
    $cat = function ($path) use (&$catcache) {
        if (isset($catcache[$path])) return $catcache[$path];
        $parent = 0; $id = 0;
        foreach (array_map('trim', explode('>', $path)) as $name) {
            $t = term_exists($name, 'product_cat', $parent);
            if (!$t) $t = wp_insert_term($name, 'product_cat', ['parent' => $parent]);
            if (is_wp_error($t)) return 0;
            $id = is_array($t) ? (int) $t['term_id'] : (int) $t; $parent = $id;
        }
        return $catcache[$path] = $id;
    };
    $step = function ($name, $pos, $lines) use ($dir, $get_tax, $cat) {
        $file = "$dir/$name.csv";
        $fh = fopen($file, 'r');
        $head = fgetcsv($fh); $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
        if ($pos > 0) fseek($fh, $pos);
        $res = ['imported' => 0, 'updated' => 0, 'failed' => 0, 'skipped' => 0, 'fail_msgs' => []];
        $cnt = 0;
        while ($cnt < $lines && ($row = fgetcsv($fh)) !== false) {
            $cnt++;
            if (count($row) < 2) continue;
            $d = array_combine($head, array_pad(array_slice($row, 0, count($head)), count($head), ''));
            $sku = trim($d['SKU'] ?? '');
            if ($sku === '') { $res['skipped']++; continue; }
            try {
                $id = wc_get_product_id_by_sku($sku);
                $new = false;
                if ($id) { $p = wc_get_product($id); }
                elseif (isset($d['Type'])) {
                    $p = new WC_Product_Simple(); $new = true;
                    $p->set_sku($sku); $p->set_name($d['Name']);
                    $p->set_status(((string) ($d['Published'] ?? '1')) === '0' ? 'draft' : 'publish');
                    if (($d['Regular price'] ?? '') !== '') $p->set_regular_price($d['Regular price']);
                    if (!empty($d['Categories'])) { $c = $cat($d['Categories']); if ($c) $p->set_category_ids([$c]); }
                } else { $res['skipped']++; continue; }
                if (!$p) { $res['failed']++; continue; }
                if (isset($d['Short description'])) $p->set_short_description($d['Short description']);
                if (isset($d['Description'])) $p->set_description($d['Description']);
                if (isset($d['In stock?']) && $d['In stock?'] !== '') {
                    $v = $d['In stock?'];
                    $p->set_stock_status($v === 'backorder' ? 'onbackorder' : ($v === '0' ? 'outofstock' : 'instock'));
                    if ($v === 'backorder') $p->set_backorders('notify');
                }
                if (!empty($d['Meta: _tirelcom_status'])) $p->update_meta_data('_tirelcom_status', $d['Meta: _tirelcom_status']);
                $attrs = []; $termmap = [];
                for ($i = 1; $i <= 8; $i++) {
                    $an = trim($d["Attribute $i name"] ?? ''); $av = trim($d["Attribute $i value(s)"] ?? '');
                    if ($an === '' || $av === '') continue;
                    $tx = $get_tax($an); if (!$tx) continue;
                    $vals = array_filter(array_map(function ($x) { return trim(str_replace('\,', ',', $x)); }, preg_split('/(?<!\\\\),/', $av)));
                    if (!$vals) continue;
                    $termmap[$tx[0]] = $vals;
                    $pa = new WC_Product_Attribute();
                    $pa->set_id($tx[1]); $pa->set_name($tx[0]); $pa->set_position($i - 1); $pa->set_visible(true); $pa->set_variation(false);
                    $attrs[$tx[0]] = $pa;
                }
                if ($attrs) {
                    foreach ($termmap as $tax => $vals) {
                        $ids = [];
                        foreach ($vals as $val) { $t = term_exists($val, $tax); if (!$t) $t = wp_insert_term($val, $tax); if (!is_wp_error($t)) $ids[] = is_array($t) ? (int) $t['term_id'] : (int) $t; }
                        $attrs[$tax]->set_options($ids);
                    }
                    $p->set_attributes($attrs);
                }
                if ($new && !empty($d['Images'])) {
                    require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
                    $tmp = download_url(trim(explode(',', $d['Images'])[0]), 15);
                    if (!is_wp_error($tmp)) { $aid = media_handle_sideload(['name' => basename(parse_url($d['Images'], PHP_URL_PATH)), 'tmp_name' => $tmp], 0); if (!is_wp_error($aid)) $p->set_image_id($aid); }
                }
                $p->save();
                $new ? $res['imported']++ : $res['updated']++;
            } catch (Throwable $e) { $res['failed']++; $res['fail_msgs'][] = $sku . ': ' . $e->getMessage(); }
        }
        $newpos = ftell($fh); $eof = feof($fh) || fgetcsv($fh) === false;
        if (!$eof) { /* ещё есть строки */ }
        $size = filesize($file); fclose($fh);
        $res['fail_msgs'] = array_slice($res['fail_msgs'], 0, 3);
        return ['res' => $res, 'pos' => $newpos, 'pct' => $eof ? 100 : round($newpos * 100 / $size, 1)];
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
