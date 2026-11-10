<?php
/* Временный загрузчик каталога. Только для админа. Удалить после загрузки. */
if (!defined('ABSPATH')) exit;
add_action('init', function () {
    if (!isset($_GET['tek_imp'])) return;
    $tok = get_option('tek_imp_tok');
    $ok = current_user_can('manage_options') || ($tok && isset($_GET['tok']) && hash_equals($tok, (string) $_GET['tok']));
    if (!$ok) return;
    @set_time_limit(120); ignore_user_abort(true);
    $act = sanitize_key($_GET['tek_imp']);
    $up = wp_upload_dir();
    $file = $up['basedir'] . '/tek-catalog.csv';
    $out = function ($d) { nocache_headers(); header('Content-Type: application/json; charset=utf-8'); echo wp_json_encode($d, JSON_UNESCAPED_UNICODE); exit; };

    if ($act === 'fetch') {
        $r = wp_remote_get('https://raw.githubusercontent.com/volodyaq/tirelcom-catalog/main/catalog.csv', ['timeout' => 60, 'stream' => true, 'filename' => $file]);
        if (is_wp_error($r)) $out(['error' => $r->get_error_message()]);
        update_option('tek_imp_offset', 0, false);
        $out(['ok' => true, 'size' => filesize($file)]);
    }
    if ($act === 'start') { $t = wp_generate_password(24, false); update_option('tek_imp_tok', $t, false); update_option('tek_imp_auto', 1, false); wp_remote_get(add_query_arg(['tek_imp' => 'run', 'n' => 100, 'auto' => 1, 'tok' => $t], home_url('/')), ['timeout' => 0.01, 'blocking' => false, 'sslverify' => false]); $out(['started' => true]); }
    if ($act === 'halt') { update_option('tek_imp_auto', 0, false); $out(['halted' => true]); }
    if ($act === 'verify') {
        global $wpdb;
        $r = [];
        $r['by_status'] = $wpdb->get_results("SELECT post_status s, COUNT(*) c FROM $wpdb->posts WHERE post_type='product' GROUP BY post_status", OBJECT_K);
        $r['distinct_sku'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT meta_value) FROM $wpdb->postmeta m JOIN $wpdb->posts p ON p.ID=m.post_id WHERE m.meta_key='_sku' AND p.post_type='product'");
        $r['with_thumb'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta m JOIN $wpdb->posts p ON p.ID=m.post_id WHERE m.meta_key='_thumbnail_id' AND p.post_type='product'");
        $r['no_sku'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts p WHERE p.post_type='product' AND NOT EXISTS (SELECT 1 FROM $wpdb->postmeta m WHERE m.post_id=p.ID AND m.meta_key='_sku' AND m.meta_value<>'')");
        $r['cats'] = (int) wp_count_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $r['drafts'] = $wpdb->get_col("SELECT p.post_title FROM $wpdb->posts p WHERE p.post_type='product' AND p.post_status='draft' LIMIT 10");
        $r['nosku_titles'] = $wpdb->get_col("SELECT p.post_title FROM $wpdb->posts p WHERE p.post_type='product' AND NOT EXISTS (SELECT 1 FROM $wpdb->postmeta m WHERE m.post_id=p.ID AND m.meta_key='_sku' AND m.meta_value<>'') LIMIT 15");
        $out($r);
    }
    if ($act === 'dedupe') {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT m.meta_value sku, GROUP_CONCAT(p.ID ORDER BY p.ID) ids FROM $wpdb->postmeta m JOIN $wpdb->posts p ON p.ID=m.post_id WHERE m.meta_key='_sku' AND p.post_type='product' AND m.meta_value<>'' GROUP BY m.meta_value HAVING COUNT(*)>1");
        $del = 0; $sample = [];
        foreach ($rows as $r) { $ids = explode(',', $r->ids); array_shift($ids); foreach ($ids as $id) { if (!empty($_GET['do'])) { wp_delete_post((int) $id, true); } $del++; } if (count($sample) < 5) $sample[] = $r->sku . ':' . $r->ids; }
        $out(['dup_groups' => count($rows), 'extra' => $del, 'sample' => $sample, 'applied' => !empty($_GET['do'])]);
    }
    if ($act === 'cleanup') { @unlink($file); foreach (['tek_imp_offset','tek_imp_tok','tek_imp_auto'] as $o) delete_option($o); $out(['cleaned' => !file_exists($file)]); }
    if ($act === 'status') $out(['offset' => (int) get_option('tek_imp_offset', 0), 'products' => (int) wp_count_posts('product')->publish]);
    if ($act === 'reset') { update_option('tek_imp_offset', 0, false); $out(['ok' => true]); }
    if ($act === 'wipe') {
        $ids = get_posts(['post_type' => 'product', 'numberposts' => 200, 'fields' => 'ids', 'post_status' => 'any']);
        foreach ($ids as $id) { wp_delete_post($id, true); }
        $out(['deleted' => count($ids)]);
    }
    if ($act !== 'run' || !file_exists($file)) $out(['error' => 'no file or action']);

    $n = isset($_GET['n']) ? max(1, (int) $_GET['n']) : 20;
    $withimg = !isset($_GET['img']) || $_GET['img'] !== '0';
    $budget = 40; $t0 = microtime(true);
    $off = (int) get_option('tek_imp_offset', 0);
    $fh = fopen($file, 'r'); fgetcsv($fh); // header
    $i = 0; while ($i < $off && fgetcsv($fh) !== false) $i++;
    $created = 0; $skipped = 0; $errs = [];
    $catcache = [];
    $term = function ($path) use (&$catcache) {
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
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    global $wpdb;
    $done = 0;
    while ($done < $n && (microtime(true) - $t0) < $budget && ($row = fgetcsv($fh)) !== false) {
        $done++;
        list($code, $name, $price, $cat, $img) = array_pad($row, 5, '');
        if (wc_get_product_id_by_sku($code)) { $skipped++; continue; }
        try {
            $p = new WC_Product_Simple();
            $p->set_name(preg_replace('/\s+/u', ' ', trim($name)));
            $p->set_sku($code);
            $p->set_status(((float) $price) > 0 ? 'publish' : 'draft');
            if ((float) $price > 0) $p->set_regular_price($price);
            $p->set_stock_status('instock');
            $c = $term($cat); if ($c) $p->set_category_ids([$c]);
            if ($withimg && $img) {
                $aid = (int) $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_tek_src' AND meta_value=%s LIMIT 1", $img));
                if (!$aid) {
                    $tmp = download_url($img, 15);
                    if (!is_wp_error($tmp)) {
                        $fa = ['name' => basename(parse_url($img, PHP_URL_PATH)), 'tmp_name' => $tmp];
                        $aid = media_handle_sideload($fa, 0);
                        if (is_wp_error($aid)) { @unlink($tmp); $errs[] = $code . ' img: ' . $aid->get_error_message(); $aid = 0; }
                        else update_post_meta($aid, '_tek_src', $img);
                    } else $errs[] = $code . ' dl: ' . $tmp->get_error_message();
                }
                if ($aid) $p->set_image_id($aid);
            }
            $p->save(); $created++;
        } catch (Throwable $e) { $errs[] = $code . ': ' . $e->getMessage(); }
    }
    $new = $off + $done;
    update_option('tek_imp_offset', $new, false);
    if (!empty($_GET['auto']) && get_option('tek_imp_auto') && $new < 34310) {
        wp_remote_get(add_query_arg(['tek_imp' => 'run', 'n' => $n, 'auto' => 1, 'tok' => $tok], home_url('/')), ['timeout' => 1, 'blocking' => false, 'sslverify' => false]);
    }
    $out(['offset' => $new, 'created' => $created, 'skipped' => $skipped, 'errors' => array_slice($errs, 0, 5), 'nerr' => count($errs), 'sec' => round(microtime(true) - $t0, 1)]);
});
