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
