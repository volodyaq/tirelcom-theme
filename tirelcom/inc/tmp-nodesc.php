<?php
/* ВРЕМЕННО: заполняет пустые описания товаров из inc/nodesc.json. После запуска удалить. */
defined('ABSPATH') || exit;
add_action('rest_api_init', function () {
    register_rest_route('tek/v1', '/applydesc', ['methods' => 'POST', 'permission_callback' => function () { return current_user_can('manage_woocommerce'); }, 'callback' => function ($req) {
        @set_time_limit(120);
        $f = get_template_directory() . '/inc/nodesc.json';
        $all = json_decode(file_get_contents($f), true);
        $off = (int) $req->get_param('offset'); $n = min(200, max(1, (int) $req->get_param('n')));
        $done = 0; $skip = 0; $nf = 0;
        foreach (array_slice($all, $off, $n) as $e) {
            $id = wc_get_product_id_by_sku($e['c']);
            if (!$id) { $nf++; continue; }
            $p = get_post($id);
            if (!$p || trim($p->post_content) !== '') { $skip++; continue; }
            wp_update_post(['ID' => $id, 'post_content' => $e['d'], 'post_excerpt' => $e['s']]);
            $done++;
        }
        return ['total' => count($all), 'offset' => $off, 'done' => $done, 'skipped' => $skip, 'notfound' => $nf];
    }]);
});
