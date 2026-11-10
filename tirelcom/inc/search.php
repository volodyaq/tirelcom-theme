<?php
if (!defined('ABSPATH')) exit;

/* ===== Умный поиск по товарам =====
   Ищет по названию, коду товара и маркировкам/аналогам (атрибуты), понимает единицы (мкФ=uF, В=V, к=кОм), игнорирует пробелы в «LM 317»,
   ранжирует: точный код, начало названия, наличие. Описание не используется. */

function tek_unit_family($u) {
    static $fam = [
        ['uf', 'мкф', 'µf', 'μf', 'mkf', 'мф'], ['nf', 'нф'], ['pf', 'пф'],
        ['v', 'в'], ['w', 'вт'], ['a', 'а'], ['ma', 'ма'],
        ['mhz', 'мгц'], ['khz', 'кгц'], ['uh', 'мкгн', 'µh', 'μh'], ['mh', 'мгн'],
        ['k', 'к', 'kom', 'kohm', 'ком', 'kω'], ['ом', 'om', 'ohm', 'ω'],
    ];
    foreach ($fam as $f) if (in_array($u, $f, true)) return $f;
    return null;
}

function tek_search_parse($raw) {
    $q = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $raw)), 'UTF-8');
    $q = str_replace(',', '.', $q);
    $q = preg_replace('/(\d)\s+([a-zа-яµμω]{1,5})(?=\s|$)/u', '$1$2', $q);
    $toks = [];
    foreach (explode(' ', $q) as $t) {
        $t = trim($t, " \t.");
        if ($t === '') continue;
        $tok = ['t' => $t, 'alts' => [$t], 'mode' => 'infix', 'unit' => false];
        if (preg_match('/^(\d+(?:\.\d+)?)([a-zа-яµμω]+)$/u', $t, $m) && ($fam = tek_unit_family($m[2]))) {
            $tok['alts'] = [];
            foreach ($fam as $u) { $tok['alts'][] = $m[1] . $u; $tok['alts'][] = $m[1] . ' ' . $u; }
            $tok['mode'] = 'start'; $tok['unit'] = true;
        } elseif (preg_match('/^\d+(?:\.\d+)?$/', $t)) {
            $tok['mode'] = (strlen($t) >= 3 && strpos($t, '.') === false) ? 'infix' : 'start';
        }
        $toks[] = $tok;
        if (count($toks) >= 6) break;
    }
    return [$q, $toks];
}

/* Возвращает ['where' => ' AND (...)', 'rank' => 'SQL-выражение', 'ok' => bool] */
function tek_search_sql($raw) {
    global $wpdb;
    list($q, $toks) = tek_search_parse($raw);
    if (!$toks) return ['where' => ' AND 1=0', 'rank' => '0', 'ok' => false];
    $N = "CONCAT(' ', REPLACE(LOWER({$wpdb->posts}.post_title), ',', '.'))";
    $C = "REPLACE(REPLACE(REPLACE(LOWER({$wpdb->posts}.post_title), ' ', ''), '-', ''), ',', '.')";
    $lk = $wpdb->prefix . 'wc_product_meta_lookup';
    $mark_tax = ['pa_markirovka', 'pa_markirovka-drugoe-ob', 'pa_analog', 'pa_drugie-oboznacheniya', 'pa_katalozhnyy-nomer', 'pa_model-pulta'];
    $parts = [];
    foreach ($toks as $tk) {
        $or = [];
        foreach ($tk['alts'] as $a) {
            $l = $wpdb->esc_like($a);
            $or[] = $wpdb->prepare($tk['mode'] === 'start' ? "$N LIKE %s" : "$N LIKE %s", ($tk['mode'] === 'start' ? '% ' : '%') . $l . '%');
        }
        if (preg_match('/^\d+$/', $tk['t'])) $or[] = $wpdb->prepare("{$wpdb->posts}.ID IN (SELECT product_id FROM $lk WHERE sku = %s)", $tk['t']);
        if (!$tk['unit'] && mb_strlen($tk['t']) >= 3) {
            $in = "'" . implode("','", array_map('esc_sql', $mark_tax)) . "'";
            $tt = $wpdb->get_col($wpdb->prepare("SELECT tt.term_taxonomy_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy IN ($in) AND LOWER(t.name) LIKE %s LIMIT 300", '%' . $wpdb->esc_like($tk['t']) . '%'));
            if ($tt) $or[] = "{$wpdb->posts}.ID IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (" . implode(',', array_map('intval', $tt)) . "))";
        }
        $parts[] = '(' . implode(' OR ', $or) . ')';
    }
    $cond = '(' . implode(' AND ', $parts) . ')';
    $compact = str_replace([' ', '-'], '', $q);
    if (count($toks) >= 2 && mb_strlen($compact) >= 4) {
        $cond = '(' . $cond . ' OR ' . $wpdb->prepare("$C LIKE %s", '%' . $wpdb->esc_like($compact) . '%') . ')';
    }
    $ql = $wpdb->esc_like($q);
    $rank = "CASE WHEN tek_l.sku = " . $wpdb->prepare('%s', $q) . " THEN 0"
        . " WHEN LOWER({$wpdb->posts}.post_title) LIKE " . $wpdb->prepare('%s', $ql . '%') . " THEN 1"
        . " WHEN $C LIKE " . $wpdb->prepare('%s', $wpdb->esc_like($compact) . '%') . " THEN 1"
        . " WHEN $N LIKE " . $wpdb->prepare('%s', '% ' . $ql . '%') . " THEN 2"
        . " WHEN $C LIKE " . $wpdb->prepare('%s', '%' . $wpdb->esc_like($compact) . '%') . " THEN 3 ELSE 4 END";
    return ['where' => ' AND ' . $cond, 'rank' => $rank, 'ok' => true];
}

/* Основной запрос: подмена поиска WordPress */
add_filter('posts_search', function ($search, $q) {
    if (is_admin() || !$q->is_main_query() || !$q->is_search()) return $search;
    $s = tek_search_sql($q->get('s'));
    $q->set('tek_rank', $s['rank']);
    return $s['where'];
}, 20, 2);

add_filter('posts_clauses', function ($c, $q) {
    global $wpdb;
    if (is_admin() || !$q->is_main_query()) return $c;
    $is_search = $q->is_search() && $q->get('tek_rank');
    $is_cat = $q->is_tax('product_cat') || $q->is_post_type_archive('product');
    if (!$is_search && !$is_cat) return $c;
    $ob = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : '';
    $lk = $wpdb->prefix . 'wc_product_meta_lookup';
    if (strpos($c['join'], 'tek_l') === false) $c['join'] .= " LEFT JOIN $lk tek_l ON tek_l.product_id = {$wpdb->posts}.ID ";
    $stock = "(tek_l.stock_status = 'instock') DESC";
    if ($ob === 'price') $c['orderby'] = "tek_l.min_price ASC, {$wpdb->posts}.post_title ASC";
    elseif ($ob === 'price-desc') $c['orderby'] = "tek_l.min_price DESC, {$wpdb->posts}.post_title ASC";
    elseif ($ob === 'title') $c['orderby'] = "{$wpdb->posts}.post_title ASC";
    elseif ($is_search) $c['orderby'] = $q->get('tek_rank') . ", $stock, CHAR_LENGTH({$wpdb->posts}.post_title) ASC";
    else $c['orderby'] = "$stock, {$wpdb->posts}.post_title ASC";
    return $c;
}, 99, 2);

/* Подсказки при вводе */
add_action('rest_api_init', function () {
    register_rest_route('tek/v1', '/suggest', [
        'methods' => 'GET', 'permission_callback' => '__return_true',
        'callback' => function ($req) {
            global $wpdb;
            $raw = trim((string) $req->get_param('q'));
            if (mb_strlen($raw) < 2) return ['products' => [], 'cats' => [], 'total' => 0];
            $s = tek_search_sql($raw);
            $lk = $wpdb->prefix . 'wc_product_meta_lookup';
            $base = "FROM {$wpdb->posts} LEFT JOIN $lk tek_l ON tek_l.product_id = {$wpdb->posts}.ID WHERE {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish' " . $s['where'];
            $ids = $wpdb->get_col("SELECT {$wpdb->posts}.ID $base ORDER BY " . $s['rank'] . ", (tek_l.stock_status = 'instock') DESC, CHAR_LENGTH({$wpdb->posts}.post_title) ASC LIMIT 7");
            $total = (int) $wpdb->get_var("SELECT COUNT(*) $base");
            $prods = [];
            foreach ($ids as $id) {
                $p = wc_get_product($id);
                if (!$p) continue;
                $img = get_the_post_thumbnail_url($id, 'thumbnail');
                $prods[] = ['n' => $p->get_name(), 'u' => get_permalink($id), 'p' => number_format((float) $p->get_price(), 2, ',', ' '), 'i' => $img ?: '', 's' => $p->get_stock_status() === 'instock' ? 1 : 0, 'c' => preg_replace('/^TEK-/', '', (string) $p->get_sku())];
            }
            $cats = [];
            list($q, $toks) = tek_search_parse($raw);
            if ($toks) {
                $cw = [];
                foreach ($toks as $tk) $cw[] = $wpdb->prepare('LOWER(t.name) LIKE %s', '%' . $wpdb->esc_like($tk['t']) . '%');
                $rows = $wpdb->get_results("SELECT t.term_id, t.name, tt.count FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'product_cat' AND tt.count > 0 AND " . implode(' AND ', $cw) . " ORDER BY tt.count DESC LIMIT 3");
                foreach ($rows as $r) { $l = get_term_link((int) $r->term_id, 'product_cat'); if (!is_wp_error($l)) $cats[] = ['n' => $r->name, 'u' => $l, 'c' => (int) $r->count]; }
            }
            return ['products' => $prods, 'cats' => $cats, 'total' => $total, 'q' => $raw];
        },
    ]);
});
