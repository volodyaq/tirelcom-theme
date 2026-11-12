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
    if ($act === 'status') {
        foreach ($wpdb->get_results("SELECT status s, COUNT(*) c FROM {$wpdb->prefix}actionscheduler_actions GROUP BY status") as $r) echo "$r->s\t$r->c\n";
        foreach ($wpdb->get_results("SELECT hook h, status s, COUNT(*) c FROM {$wpdb->prefix}actionscheduler_actions GROUP BY hook, status ORDER BY c DESC LIMIT 12") as $r) echo "$r->h\t$r->s\t$r->c\n";
    }
    if ($act === 'purge') {
        $a = $wpdb->prefix . 'actionscheduler_actions'; $l = $wpdb->prefix . 'actionscheduler_logs';
        $keepw = "status IN ('pending','in-progress') AND hook NOT LIKE 'hostinger_tools/jobs/generate_llmstxt%'";
        $keep = $wpdb->get_var("SELECT COUNT(*) FROM $a WHERE $keepw");
        echo "pending/in-progress: $keep\n";
        if ($keep > 5000) { echo "too many pending, abort\n"; exit; }
        $wpdb->query("DROP TABLE IF EXISTS {$a}_keep");
        echo 'create keep: ' . var_export($wpdb->query("CREATE TABLE {$a}_keep AS SELECT * FROM $a WHERE $keepw"), true) . ' ' . $wpdb->last_error . "\n";
        echo 'truncate logs: ' . var_export($wpdb->query("TRUNCATE TABLE $l"), true) . ' ' . $wpdb->last_error . "\n";
        echo 'truncate actions: ' . var_export($wpdb->query("TRUNCATE TABLE $a"), true) . ' ' . $wpdb->last_error . "\n";
        echo 'restore: ' . var_export($wpdb->query("INSERT INTO $a SELECT * FROM {$a}_keep"), true) . ' ' . $wpdb->last_error . "\n";
        $wpdb->query("DROP TABLE {$a}_keep");
        echo 'now actions: ' . $wpdb->get_var("SELECT COUNT(*) FROM $a") . "\n";
    }
    exit;
}, 1);
