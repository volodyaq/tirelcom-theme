<?php
if (!defined('ABSPATH')) exit;

/* ===== Фильтры каталога: наличие и параметры (атрибуты) со счётчиками ===== */

/* Выбранные значения параметров: [pa_xxx => [slug, ...]] */
function tek_selected_filters() {
    $out = [];
    if (!empty($_GET['f']) && is_array($_GET['f'])) {
        foreach ($_GET['f'] as $tax => $vals) {
            $tax = sanitize_key($tax);
            if (strpos($tax, 'pa_') !== 0 || !taxonomy_exists($tax)) continue;
            $vals = array_values(array_filter(array_map('sanitize_title', (array) $vals)));
            if ($vals) $out[$tax] = array_slice($vals, 0, 20);
        }
    }
    return $out;
}

function tek_only_stock() { return !empty($_GET['stock']); }

/* Применяем выбранные параметры к основному запросу */
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) return;
    if (!($q->is_search() || $q->is_tax('product_cat') || $q->is_post_type_archive('product'))) return;
    $sel = tek_selected_filters();
    if (!$sel) return;
    $tq = (array) $q->get('tax_query');
    foreach ($sel as $tax => $vals) $tq[] = ['taxonomy' => $tax, 'field' => 'slug', 'terms' => $vals, 'operator' => 'IN'];
    $tq['relation'] = 'AND';
    $q->set('tax_query', $tq);
});

/* Только в наличии: условие в SQL основного запроса */
add_filter('posts_clauses', function ($c, $q) {
    global $wpdb;
    if (is_admin() || !$q->is_main_query() || !tek_only_stock()) return $c;
    if (!($q->is_search() || $q->is_tax('product_cat') || $q->is_post_type_archive('product'))) return $c;
    if (strpos($c['join'], 'tek_l') === false) $c['join'] .= " LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup tek_l ON tek_l.product_id = {$wpdb->posts}.ID ";
    $c['where'] .= " AND tek_l.stock_status = 'instock'";
    return $c;
}, 98, 2);

function tek_nonfilter_attr($tax) {
    static $x = ['pa_markirovka', 'pa_markirovka-drugoe-ob', 'pa_analog', 'pa_drugie-oboznacheniya', 'pa_katalozhnyy-nomer', 'pa_model-pulta',
        'pa_naznachenie', 'pa_funkciya', 'pa_cokolevka-to-220-vid', 'pa_komplementarnaya-par', 'pa_osobennost', 'pa_marka-avto', 'pa_emkost-drugie-edinic'];
    return in_array($tax, $x, true) || tek_nonparam_attr($tax) && !in_array($tax, ['pa_brend', 'pa_proizvoditel', 'pa_cvet-korpusa', 'pa_cvet-svecheniya'], true);
}

/* Параметры для фильтров. $cat — термин product_cat или null; $raw — поисковый запрос или ''.
   Возвращает [tax => ['label'=>, 'terms'=>[[slug,name,count], ...]]] */
function tek_facets($cat, $raw = '') {
    global $wpdb;
    if (!$cat && $raw === '') return [];
    $sel = tek_selected_filters();
    $stock = tek_only_stock();
    $minp = isset($_GET['min_price']) ? (float) $_GET['min_price'] : 0;
    $maxp = isset($_GET['max_price']) ? (float) $_GET['max_price'] : 0;
    $key = 'tek_fct_' . md5(wp_json_encode([$cat ? $cat->term_id : 0, $raw, $sel, $stock, $minp, $maxp]));
    $hit = get_transient($key);
    if (is_array($hit)) return $hit;
    $lk = $wpdb->prefix . 'wc_product_meta_lookup';
    $base = "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} LEFT JOIN $lk tek_l ON tek_l.product_id = {$wpdb->posts}.ID WHERE {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish'";
    if ($cat) {
        $ids = array_merge([(int) $cat->term_id], array_map('intval', (array) get_term_children($cat->term_id, 'product_cat')));
        $ctt = $wpdb->get_col("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'product_cat' AND term_id IN (" . implode(',', $ids) . ")");
        if (!$ctt) return [];
        $base .= " AND {$wpdb->posts}.ID IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (" . implode(',', array_map('intval', $ctt)) . "))";
    }
    if ($raw !== '') { list($s) = tek_search_run($raw); $base .= $s['where']; }
    if ($stock) $base .= " AND tek_l.stock_status = 'instock'";
    if ($minp > 0) $base .= $wpdb->prepare(' AND tek_l.min_price >= %f', $minp);
    if ($maxp > 0) $base .= $wpdb->prepare(' AND tek_l.min_price <= %f', $maxp);
    $restr = function ($except) use ($sel, $wpdb) {
        $r = '';
        foreach ($sel as $tax => $vals) {
            if ($tax === $except) continue;
            $tt = $wpdb->get_col($wpdb->prepare("SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND t.slug IN ('" . implode("','", array_map('esc_sql', $vals)) . "')", $tax));
            $r .= " AND {$wpdb->posts}.ID IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (" . implode(',', $tt ?: [0]) . "))";
        }
        return $r;
    };
    $q = function ($b, $only = null) use ($wpdb) {
        $w = "tt.taxonomy LIKE 'pa\\_%'" . ($only ? $wpdb->prepare(' AND tt.taxonomy = %s', $only) : '');
        return $wpdb->get_results("SELECT tt.taxonomy tax, t.slug, t.name, COUNT(DISTINCT r.object_id) c FROM ($b) b JOIN {$wpdb->term_relationships} r ON r.object_id = b.ID JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = r.term_taxonomy_id JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE $w GROUP BY tt.taxonomy, t.term_id");
    };
    $rows = $q($base . $restr(null));
    $by = [];
    foreach ($rows as $r) $by[$r->tax][$r->slug] = [$r->slug, $r->name, (int) $r->c];
    foreach (array_keys($sel) as $tax) {
        $by[$tax] = [];
        foreach ($q($base . $restr($tax), $tax) as $r) $by[$tax][$r->slug] = [$r->slug, $r->name, (int) $r->c];
    }
    $out = [];
    foreach ($by as $tax => $terms) {
        if (tek_nonfilter_attr($tax)) continue;
        $n = count($terms);
        if (!isset($sel[$tax]) && ($n < 2 || $n > 80)) continue;
        $cov = array_sum(array_column($terms, 2));
        uasort($terms, function ($a, $b) { return $b[2] <=> $a[2] ?: strnatcasecmp($a[1], $b[1]); });
        $out[$tax] = ['label' => wc_attribute_label($tax), 'cov' => $cov, 'terms' => array_values($terms)];
    }
    uasort($out, function ($a, $b) { return $b['cov'] <=> $a['cov']; });
    $out = array_slice($out, 0, 7, true);
    set_transient($key, $out, 15 * MINUTE_IN_SECONDS);
    return $out;
}

/* Скрытые поля текущего адреса для форм (кроме перечисленных) */
function tek_hidden_inputs($skip = []) {
    $h = '';
    foreach ($_GET as $k => $v) {
        if (in_array($k, $skip, true) || $k === 'paged') continue;
        if (is_array($v)) { foreach ($v as $kk => $vv) { if (is_array($vv)) foreach ($vv as $x) $h .= '<input type="hidden" name="' . esc_attr($k) . '[' . esc_attr($kk) . '][]" value="' . esc_attr($x) . '">'; else $h .= '<input type="hidden" name="' . esc_attr($k) . '[]" value="' . esc_attr($vv) . '">'; } }
        else $h .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr(wp_unslash($v)) . '">';
    }
    return $h;
}
