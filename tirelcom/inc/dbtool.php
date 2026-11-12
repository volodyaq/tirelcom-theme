<?php
// ВРЕМЕННЫЙ инструмент диагностики базы (удалить после использования). Только для админа.
defined('ABSPATH') || exit;
add_action('init', function () {
    if (empty($_GET['tek_db']) || !current_user_can('manage_options')) return;
    global $wpdb; header('Content-Type: text/plain; charset=utf-8');
    $act = sanitize_key($_GET['tek_db']);
    if ($act === 'report') {
        $rows = $wpdb->get_results($wpdb->prepare("SELECT table_name n, ROUND((data_length+index_length)/1048576,1) mb, ROUND(data_free/1048576,1) fr, table_rows r FROM information_schema.tables WHERE table_schema=%s ORDER BY (data_length+index_length) DESC LIMIT 25", DB_NAME));
        foreach ($rows as $r) echo "$r->n\t{$r->mb}MB\tfree{$r->fr}\trows~$r->r\n";
        echo "total: " . $wpdb->get_var($wpdb->prepare("SELECT ROUND(SUM(data_length+index_length)/1048576,1) FROM information_schema.tables WHERE table_schema=%s", DB_NAME)) . "MB\n";
        echo "revisions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='revision'") . "\n";
        echo "autoload: " . $wpdb->get_var("SELECT ROUND(SUM(LENGTH(option_value))/1048576,1) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto','auto-on')") . "MB\n";
        echo "transients: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%' OR option_name LIKE '\\_site\\_transient\\_%'") . "\n";
    }
    exit;
}, 1);
