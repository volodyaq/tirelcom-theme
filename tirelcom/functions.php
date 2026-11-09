<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('tirelcom-fonts', 'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap', [], null);
    wp_enqueue_style('tirelcom', get_stylesheet_uri(), [], wp_get_theme()->get('Version') . '.' . filemtime(get_stylesheet_directory() . '/style.css'));
    wp_enqueue_script('tirelcom-js', get_template_directory_uri() . '/theme.js', [], filemtime(get_stylesheet_directory() . '/theme.js'), true);
    wp_localize_script('tirelcom-js', 'TEK', [
        'mdlRate' => (float) get_option('tirelcom_mdl_rate', 0),
    ]);
});

/* Цена в формате «75,01» */
function tek_price($product) {
    $p = (float) $product->get_price();
    return number_format($p, 2, ',', ' ');
}

/* Первая (верхняя) категория товара */
function tek_product_tag($product) {
    $terms = get_the_terms($product->get_id(), 'product_cat');
    if (!$terms || is_wp_error($terms)) return '';
    $deep = null;
    foreach ($terms as $t) { if ($deep === null || $t->parent) $deep = $t; }
    return mb_strtoupper($deep->name);
}

/* Товары для главной: с фото, случайные, кэш на 6 часов */
function tek_home_products($n = 7) {
    $ids = get_transient('tek_home_ids');
    if (!is_array($ids) || count($ids) < $n) {
        $q = new WP_Query([
            'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => $n,
            'orderby' => 'rand', 'fields' => 'ids', 'no_found_rows' => true,
            'meta_query' => [['key' => '_thumbnail_id', 'compare' => 'EXISTS']],
        ]);
        $ids = $q->posts;
        if ($ids) set_transient('tek_home_ids', $ids, 6 * HOUR_IN_SECONDS);
    }
    return array_filter(array_map('wc_get_product', $ids));
}

/* Верхние категории каталога (без «Без категории») */
function tek_top_cats() {
    $terms = get_terms(['taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name']);
    if (is_wp_error($terms)) return [];
    $terms = array_values(array_filter($terms, function ($t) { return $t->slug !== 'uncategorized' && $t->slug !== 'bez-kategorii'; }));
    $order = ['Электронные компоненты', 'Источники питания', 'Оборудование и инструмент', 'Разработка и конструирование', 'Химия для электроники', 'Прочее'];
    usort($terms, function ($a, $b) use ($order) {
        $ia = array_search($a->name, $order); $ib = array_search($b->name, $order);
        $ia = $ia === false ? 99 : $ia; $ib = $ib === false ? 99 : $ib;
        return $ia <=> $ib;
    });
    return $terms;
}

/* Регистрация покупателей не нужна: оформление по имени и телефону */
add_filter('woocommerce_enable_myaccount_registration', '__return_false');

/* Короткие описания разделов для главной */
function tek_cat_text($name) {
    $map = [
        'Электронные компоненты' => 'Микросхемы, транзисторы и диоды, пассивные компоненты, датчики, модули.',
        'Источники питания' => 'DC/DC и AC/DC преобразователи, аккумуляторы, зарядные устройства, платы защиты.',
        'Оборудование и инструмент' => 'Паяльное оборудование, измерительные приборы, инструмент, оптика, сменные жала.',
        'Разработка и конструирование' => 'Платы Arduino, дисплеи, макетные платы, программаторы, корпуса для электроники.',
        'Химия для электроники' => 'Припой, флюс, термоусадка, лаки, клеи, чистящие и смазочные средства.',
        'Прочее' => 'Кабели, готовые устройства и всё, что не вошло в другие разделы.',
    ];
    return $map[$name] ?? '';
}

/* Каталог: количество на странице и стили WooCommerce */
add_filter('loop_shop_per_page', function ($n) {
    $v = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 0;
    return in_array($v, [24, 48, 96], true) ? $v : 24;
}, 20);
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/* После добавления в корзину сразу открываем корзину */
add_filter('option_woocommerce_cart_redirect_after_add', function () { return 'yes'; });

/* ---- Оформление заказа: гостевой, имя + телефон ---- */
add_filter('pre_option_woocommerce_enable_guest_checkout', function () { return 'yes'; });
add_filter('pre_option_woocommerce_enable_checkout_login_reminder', function () { return 'no'; });
add_filter('woocommerce_checkout_fields', function ($f) {
    $b = $f['billing'];
    $f['billing'] = [
        'billing_first_name' => array_merge($b['billing_first_name'], ['required' => false]),
        'billing_phone' => array_merge($b['billing_phone'], ['required' => true]),
    ];
    $f['shipping'] = [];
    return $f;
});
add_filter('woocommerce_cart_needs_shipping', '__return_false');
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');
add_filter('woocommerce_enable_order_notes_field', '__return_true');
add_filter('woocommerce_available_payment_gateways', function ($g) { return isset($g['cod']) ? ['cod' => $g['cod']] : $g; });
add_action('woocommerce_checkout_create_order', function ($order) {
    $labels = ['pickup' => 'Самовывоз', 'courier' => 'Курьер по Тирасполю', 'other' => 'Другой город или страна'];
    $k = isset($_POST['tek_delivery']) ? sanitize_text_field(wp_unslash($_POST['tek_delivery'])) : 'pickup';
    $order->update_meta_data('tek_delivery', $labels[$k] ?? $labels['pickup']);
    if (!empty($_POST['tek_address'])) $order->update_meta_data('tek_address', sanitize_text_field(wp_unslash($_POST['tek_address'])));
    if (!$order->get_billing_first_name()) $order->set_billing_first_name('Клиент');
});
add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    echo '<p><strong>Получение:</strong> ' . esc_html($order->get_meta('tek_delivery')) . '<br><strong>Адрес:</strong> ' . esc_html($order->get_meta('tek_address')) . '</p>';
});

/* Корзина и оформление: классические шорткоды вместо блоков, чтобы работали наши шаблоны */
add_filter('the_content', function ($c) {
    if (!in_the_loop() || !is_main_query()) return $c;
    if (function_exists('is_cart') && is_cart()) return do_shortcode('[woocommerce_cart]');
    if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-received')) return do_shortcode('[woocommerce_checkout]');
    if (function_exists('is_checkout') && is_wc_endpoint_url('order-received')) return do_shortcode('[woocommerce_checkout]');
    return $c;
}, 5);
