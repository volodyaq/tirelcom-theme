<?php
/* ВРЕМЕННО: исправление значений параметров, у которых совпали адреса (2,2 мкФ и 22 мкФ). Удалить после запуска. */
if (!defined('ABSPATH')) exit;
add_action('init', function () {
    if (!isset($_GET['tek_fix']) || !current_user_can('manage_options')) return;
    @set_time_limit(120); ignore_user_abort(true);
    $act = sanitize_key($_GET['tek_fix']);
    $dir = wp_upload_dir()['basedir'] . '/tek-data';
    $out = function ($d) { nocache_headers(); header('Content-Type: application/json; charset=utf-8'); echo wp_json_encode($d, JSON_UNESCAPED_UNICODE); exit; };
    $names = ['00_test_50', 'novye_tovary', 'obnovlenie_01', 'obnovlenie_02', 'obnovlenie_03', 'obnovlenie_04', 'obnovlenie_05', 'obnovlenie_06', 'obnovlenie_07'];
    $dry = !empty($_GET['dry']);
    $skey = $dry ? 'tek_fix_dry' : 'tek_fix_state';

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
        foreach (['tek_fix_state', 'tek_fix_dry'] as $o) delete_option($o);
        $out(['cleaned' => !is_dir($dir)]);
    }
    if ($act === 'status') $out(['state' => get_option($skey)]);
    if ($act === 'reset') { delete_option($skey); $out(['reset' => true]); }

    $translit = function ($s) {
        $m = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'];
        $s = strtr(mb_strtolower(trim($s)), $m);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim(substr($s, 0, 20), '-') ?: 'attr' . substr(md5($s), 0, 6);
    };
    $norm = function ($s) { return mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $s, ENT_QUOTES, 'UTF-8'))), 'UTF-8'); };
    $maps = [];
    $load = function ($tax) use (&$maps, $norm, $translit) {
        if (isset($maps[$tax])) return;
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT t.term_id, t.name, t.slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy = %s ORDER BY t.term_id", $tax));
        $by_name = []; $by_slug = [];
        foreach ($rows as $r) { $k = $norm($r->name); if (!isset($by_name[$k])) $by_name[$k] = (int) $r->term_id; $by_slug[$r->slug] = ['id' => (int) $r->term_id, 'n' => $k]; }
        $maps[$tax] = ['name' => $by_name, 'slug' => $by_slug];
    };

    $step = function ($name, $pos, $lines) use ($dir, $translit, $norm, $load, &$maps, $dry) {
        $fh = fopen("$dir/$name.csv", 'r');
        $head = fgetcsv($fh); $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
        if ($pos > 0) fseek($fh, $pos);
        $res = ['rows' => 0, 'affected' => 0, 'fixed' => 0, 'created' => 0, 'noprod' => 0, 'ex' => []];
        $cnt = 0;
        while ($cnt < $lines && ($row = fgetcsv($fh)) !== false) {
            $cnt++;
            if (count($row) < 2) continue;
            $d = array_combine($head, array_pad(array_slice($row, 0, count($head)), count($head), ''));
            $sku = trim($d['SKU'] ?? ''); if ($sku === '') continue;
            $res['rows']++;
            $todo = [];
            for ($i = 1; $i <= 8; $i++) {
                $an = trim($d["Attribute $i name"] ?? ''); $av = trim($d["Attribute $i value(s)"] ?? '');
                if ($an === '' || $av === '') continue;
                $tax = 'pa_' . $translit($an);
                if (!taxonomy_exists($tax)) continue;
                $load($tax);
                $vals = array_filter(array_map(function ($x) { return trim(str_replace('\,', ',', $x)); }, preg_split('/(?<!\\\\),/', $av)));
                $bad = false;
                foreach ($vals as $v) {
                    $sl = $maps[$tax]['slug'][sanitize_title($v)] ?? null;
                    if ($sl && $sl['n'] !== $norm($v)) { $bad = true; break; }
                }
                if ($bad) $todo[$tax] = $vals;
            }
            if (!$todo) continue;
            $res['affected']++;
            $pid = wc_get_product_id_by_sku($sku);
            if (!$pid) { $res['noprod']++; continue; }
            foreach ($todo as $tax => $vals) {
                $ids = [];
                foreach ($vals as $v) {
                    $k = $norm($v);
                    if (isset($maps[$tax]['name'][$k])) { $ids[] = $maps[$tax]['name'][$k]; continue; }
                    if ($dry) { $res['created']++; continue; }
                    $t = wp_insert_term($v, $tax);
                    if (is_wp_error($t)) continue;
                    $tid = (int) $t['term_id']; $maps[$tax]['name'][$k] = $tid; $maps[$tax]['slug'][get_term($tid)->slug] = ['id' => $tid, 'n' => $k];
                    $ids[] = $tid; $res['created']++;
                }
                $cur = array_map('intval', wp_get_object_terms($pid, $tax, ['fields' => 'ids']));
                sort($cur); $want = array_values(array_unique($ids)); sort($want);
                if ($cur !== $want) {
                    $res['fixed']++;
                    if (count($res['ex']) < 4) $res['ex'][] = "$sku $tax: " . implode('|', $vals);
                    if (!$dry && $want) wp_set_object_terms($pid, $want, $tax);
                }
            }
        }
        $newpos = ftell($fh); $eof = feof($fh) || fgetcsv($fh) === false; $size = filesize("$dir/$name.csv"); fclose($fh);
        return ['res' => $res, 'pos' => $newpos, 'pct' => $eof ? 100 : round($newpos * 100 / $size, 1)];
    };

    if ($act === 'run') {
        $st = get_option($skey);
        if (!$st) $st = ['i' => 0, 'pos' => 0, 'done' => false, 'tot' => ['rows' => 0, 'affected' => 0, 'fixed' => 0, 'created' => 0, 'noprod' => 0], 'ex' => []];
        if (!empty($st['done'])) $out($st);
        $t0 = microtime(true);
        do {
            $name = $names[$st['i']];
            $r = $step($name, $st['pos'], 200);
            foreach ($st['tot'] as $k => $_) $st['tot'][$k] += $r['res'][$k];
            foreach ($r['res']['ex'] as $e) if (count($st['ex']) < 8) $st['ex'][] = $e;
            $st['pos'] = $r['pos']; $st['file'] = $name; $st['pct'] = $r['pct'];
            if ($r['pct'] >= 100 || $r['res']['rows'] === 0) { $st['i']++; $st['pos'] = 0; if ($st['i'] >= count($names)) { $st['done'] = true; break; } }
        } while ((microtime(true) - $t0) < 28);
        update_option($skey, $st, false);
        $out($st);
    }
    $out(['error' => 'unknown']);
});
