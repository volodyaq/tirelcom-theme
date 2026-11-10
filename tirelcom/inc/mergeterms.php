<?php
/* ВРЕМЕННО: объединение одинаковых значений параметров (разный регистр, порядок слов и т. п.). Удалить после запуска. */
if (!defined('ABSPATH')) exit;
add_action('init', function () {
    if (!isset($_GET['tek_merge']) || !current_user_can('manage_options')) return;
    @set_time_limit(120); ignore_user_abort(true);
    global $wpdb;
    $dry = $_GET['tek_merge'] !== 'run';
    $map = json_decode(file_get_contents(__DIR__ . '/merge-map.json'), true);
    $res = ['dry' => $dry, 'merged' => 0, 'links' => 0, 'missing' => 0, 'deleted' => 0, 'ex' => []];
    foreach ($map as $tax => $pairs) {
        if (!taxonomy_exists($tax)) { $res['missing_tax'][] = $tax; continue; }
        $rows = $wpdb->get_results($wpdb->prepare("SELECT t.term_id, t.name, tt.term_taxonomy_id tt FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy = %s", $tax));
        $by = [];
        foreach ($rows as $r) $by[html_entity_decode($r->name, ENT_QUOTES, 'UTF-8')] = $r;
        foreach ($pairs as $dup => $canon) {
            if (!isset($by[$dup]) || !isset($by[$canon]) || $by[$dup]->term_id === $by[$canon]->term_id) { $res['missing']++; continue; }
            $d = $by[$dup]; $c = $by[$canon];
            $objs = $wpdb->get_col($wpdb->prepare("SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", $d->tt));
            $res['merged']++; $res['links'] += count($objs);
            if (count($res['ex']) < 5) $res['ex'][] = "$tax: $dup -> $canon (" . count($objs) . ')';
            if ($dry) continue;
            foreach ($objs as $pid) {
                wp_set_object_terms((int) $pid, [(int) $c->term_id], $tax, true);
                wp_remove_object_terms((int) $pid, [(int) $d->term_id], $tax);
            }
            $left = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", $d->tt));
            if ($left === 0) { wp_delete_term((int) $d->term_id, $tax); $res['deleted']++; }
        }
    }
    nocache_headers(); header('Content-Type: application/json; charset=utf-8'); echo wp_json_encode($res, JSON_UNESCAPED_UNICODE); exit;
});
