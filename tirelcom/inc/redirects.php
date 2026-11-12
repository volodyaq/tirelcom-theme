<?php
/**
 * Редиректы со старого сайта tirelcom.net (ocStore) на новые адреса.
 * Работают, если домен tirelcom.net направлен на этот же сайт (псевдоним домена).
 *   index.php?route=product/product&product_id=N   -> товар с артикулом N (код товара = product_id)
 *   index.php?route=product/category&path=A_B_C    -> раздел (карта inc/catmap.json: id старого раздела -> id нового)
 *   прочие route=... -> ближайшая страница нового сайта
 */
defined('ABSPATH') || exit;

add_action('init', function () {
    if (is_admin() || empty($_GET['route']) || !is_string($_GET['route'])) return;
    $route = preg_replace('~[^a-z0-9/_]~i', '', wp_unslash($_GET['route']));
    $go = function ($url, $code = 301) { wp_redirect($url, $code, 'tirelcom-redirect'); exit; };
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

    if ($route === 'product/product') {
        $id = isset($_GET['product_id']) ? preg_replace('~\D~', '', (string) $_GET['product_id']) : '';
        if ($id !== '' && function_exists('wc_get_product_id_by_sku')) {
            $pid = wc_get_product_id_by_sku($id);
            if ($pid && get_post_status($pid) === 'publish') $go(get_permalink($pid));
        }
        // товара нет: ведём в раздел из пути, иначе в каталог
        if (!empty($_GET['path'])) { $u = tek_old_cat_url((string) $_GET['path']); if ($u) $go($u); }
        $go($shop, 302);
    }
    if ($route === 'product/category') {
        $u = !empty($_GET['path']) ? tek_old_cat_url((string) $_GET['path']) : '';
        if ($u) {
            $pg = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            if ($pg > 1) $u = trailingslashit($u) . 'page/' . $pg . '/';
            $go($u);
        }
        $go($shop, 302);
    }
    if ($route === 'product/search') {
        $q = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $go($q !== '' ? add_query_arg(['s' => $q, 'post_type' => 'product'], home_url('/')) : $shop);
    }
    $static = [
        'common/home' => home_url('/'),
        'information/contact' => home_url('/o-magazine/'),
        'information/information' => home_url('/o-magazine/'),
        'checkout/cart' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/'),
        'checkout/checkout' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/'),
        'product/manufacturer' => $shop,
        'product/special' => $shop,
    ];
    $go(isset($static[$route]) ? $static[$route] : home_url('/'), isset($static[$route]) ? 301 : 302);
}, 20);

function tek_old_cat_url($path) {
    static $map = null;
    if ($map === null) { $f = __DIR__ . '/catmap.json'; $map = is_readable($f) ? json_decode(file_get_contents($f), true) : []; }
    $parts = array_values(array_filter(explode('_', preg_replace('~[^0-9_]~', '', $path))));
    // идём с самого глубокого уровня вверх, пока не найдём известный раздел
    for ($i = count($parts) - 1; $i >= 0; $i--) {
        if (!empty($map[$parts[$i]])) {
            $link = get_term_link((int) $map[$parts[$i]], 'product_cat');
            if (!is_wp_error($link)) return $link;
        }
    }
    return '';
}
