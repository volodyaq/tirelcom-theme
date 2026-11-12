<?php
/**
 * SEO: заголовки, описания, canonical, noindex для служебных адресов, Open Graph,
 * микроразметка (магазин, товар, хлебные крошки), robots.txt, карта сайта, alt у фото товаров.
 */
defined('ABSPATH') || exit;

const TEK_BRAND = 'ТирЭлКом';
const TEK_ADDR  = 'ул. Ленина 7, ТК «Монарх», бутик №5, Тирасполь';

function tek_seo_trim($s, $len) {
    $s = trim(preg_replace('~\s+~u', ' ', wp_strip_all_tags((string) $s)));
    if (mb_strlen($s) <= $len) return $s;
    $cut = mb_substr($s, 0, $len);
    $sp = mb_strrpos($cut, ' ');
    return rtrim(($sp && $sp > $len * 0.6) ? mb_substr($cut, 0, $sp) : $cut, " ,;:-–—") . '…';
}

/** Служебные параметры: страница с ними не должна попадать в индекс. */
function tek_seo_is_param_page() {
    foreach (['f', 'stock', 'min_price', 'max_price', 'orderby', 'per_page', 'ids', 'add-to-cart', 'filter_', 'route'] as $k) {
        if (isset($_GET[$k])) return true;
    }
    return false;
}

function tek_seo_noindex() {
    if (is_search() || is_404() || tek_seo_is_param_page()) return true;
    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) return true;
    if (is_page('sravnenie')) return true;
    return false;
}

function tek_seo_ctx() {
    static $c = null;
    if ($c !== null) return $c;
    $c = ['title' => '', 'desc' => '', 'canon' => '', 'img' => '', 'type' => 'website'];
    $paged = max(1, (int) get_query_var('paged'));
    $suffix = $paged > 1 ? ' — страница ' . $paged : '';
    if (is_front_page()) {
        $c['title'] = 'ТирЭлКом — радиодетали и электроника в Тирасполе, магазин более 25 лет';
        $c['desc'] = 'Микросхемы, транзисторы, Arduino, паяльное оборудование и всё для ремонта электроники. Более 25 лет в Тирасполе: самовывоз, подбор аналогов, ул. Ленина 7, ТК «Монарх».';
        $c['canon'] = home_url('/');
    } elseif (function_exists('is_product') && is_product()) {
        $p = wc_get_product(get_queried_object_id());
        if ($p) {
            $name = $p->get_name(); $sku = $p->get_sku();
            $c['title'] = tek_seo_trim($name, 52) . ' — купить в Тирасполе' . ($sku ? ', код ' . $sku : '') . ' | ' . TEK_BRAND;
            $first = tek_seo_trim($p->get_short_description() ?: $name, 120);
            $price = (float) $p->get_price();
            $tail = ($price > 0 ? ' Цена: ' . number_format($price, 2, ',', ' ') . ' руб. ПМР.' : '') . ($p->get_stock_status() === 'onbackorder' ? ' Под заказ, подберём аналог.' : ' В наличии, самовывоз в Тирасполе.');
            $c['desc'] = trim($first . $tail);
            $c['canon'] = get_permalink($p->get_id());
            $iid = $p->get_image_id(); if ($iid) $c['img'] = wp_get_attachment_image_url($iid, 'large');
            $c['type'] = 'product';
        }
    } elseif (function_exists('is_product_category') && is_product_category()) {
        $t = get_queried_object();
        if ($t && !is_wp_error($t)) {
            $c['title'] = $t->name . ' — купить в Тирасполе' . $suffix . ' | ' . TEK_BRAND;
            $c['desc'] = $t->description ? tek_seo_trim($t->description, 155) : tek_seo_trim($t->name . ': цены и наличие, товаров в разделе: ' . (int) $t->count . '. Самовывоз в Тирасполе, ул. Ленина 7, ТК «Монарх». Подберём аналог.', 160);
            $link = get_term_link($t); $c['canon'] = is_wp_error($link) ? '' : ($paged > 1 ? trailingslashit($link) . 'page/' . $paged . '/' : $link);
        }
    } elseif (function_exists('is_shop') && is_shop()) {
        $c['title'] = 'Каталог радиодеталей и электроники' . $suffix . ' | ' . TEK_BRAND;
        $c['desc'] = 'Каталог магазина ТирЭлКом: микросхемы, транзисторы, резисторы, конденсаторы, Arduino, инструмент для пайки и ремонта. Самовывоз в Тирасполе.';
        $c['canon'] = $paged > 1 ? trailingslashit(wc_get_page_permalink('shop')) . 'page/' . $paged . '/' : wc_get_page_permalink('shop');
    } elseif (is_search()) {
        $c['title'] = 'Поиск: ' . get_search_query() . ' | ' . TEK_BRAND;
    } elseif (is_page('o-magazine')) {
        $c['title'] = 'О магазине и контакты — ТирЭлКом, Тирасполь, ул. Ленина 7';
        $c['desc'] = 'Магазин радиодеталей в Тирасполе, больше 25 лет на рынке. ТК «Монарх», бутик №5. Пн–сб 10–19, вс 10–14. Звоните: +373 533 51338.';
        $c['canon'] = get_permalink();
    } elseif (is_page('dostavka-i-oplata')) {
        $c['title'] = 'Доставка и оплата | ' . TEK_BRAND;
        $c['desc'] = 'Самовывоз из магазина в Тирасполе бесплатно, курьер по городу, отправка в другие города. Оплата при получении.';
        $c['canon'] = get_permalink();
    } elseif (is_singular()) {
        $c['title'] = get_the_title() . ' | ' . TEK_BRAND;
        $c['canon'] = get_permalink();
    }
    return $c;
}

add_filter('pre_get_document_title', function ($t) {
    $c = tek_seo_ctx();
    return $c['title'] ?: $t;
}, 20);

add_filter('wp_robots', function ($r) {
    if (tek_seo_noindex()) { unset($r['index']); $r['noindex'] = true; $r['follow'] = true; }
    return $r;
}, 20);

remove_action('wp_head', 'rel_canonical');
add_action('wp_head', function () {
    $c = tek_seo_ctx();
    if (tek_seo_noindex() && !$c['canon']) return;
    if ($c['canon']) echo '<link rel="canonical" href="' . esc_url($c['canon']) . '">' . "\n";
}, 1);

add_action('wp_head', function () {
    $c = tek_seo_ctx();
    if ($c['desc']) echo '<meta name="description" content="' . esc_attr($c['desc']) . '">' . "\n";
    if (!$c['title']) return;
    $img = $c['img'] ?: get_site_icon_url(512);
    echo '<meta property="og:locale" content="ru_RU">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(TEK_BRAND) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($c['type']) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($c['title']) . '">' . "\n";
    if ($c['desc']) echo '<meta property="og:description" content="' . esc_attr($c['desc']) . '">' . "\n";
    if ($c['canon']) echo '<meta property="og:url" content="' . esc_url($c['canon']) . '">' . "\n";
    if ($img) { echo '<meta property="og:image" content="' . esc_url($img) . '">' . "\n"; echo '<meta name="twitter:card" content="summary_large_image">' . "\n"; }
}, 2);

function tek_ld($data) {
    echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

function tek_crumbs_ld($items) {
    $list = []; $i = 1;
    foreach ($items as $it) $list[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $it[0], 'item' => $it[1]];
    tek_ld(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list]);
}

function tek_term_crumbs($term) {
    $chain = [];
    while ($term && !is_wp_error($term)) {
        array_unshift($chain, [html_entity_decode($term->name), get_term_link($term)]);
        $term = $term->parent ? get_term($term->parent, 'product_cat') : null;
    }
    return $chain;
}

add_action('wp_head', function () {
    if (is_front_page()) {
        tek_ld([
            '@context' => 'https://schema.org',
            '@type' => ['Store', 'LocalBusiness'],
            'name' => TEK_BRAND,
            'description' => 'Магазин радиодеталей и электроники в Тирасполе, более 25 лет на рынке.',
            'url' => home_url('/'),
            'telephone' => '+37353351338',
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'ул. Ленина 7, ТК «Монарх», бутик №5', 'addressLocality' => 'Тирасполь'],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => 46.8405, 'longitude' => 29.6300],
            'openingHoursSpecification' => [
                ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], 'opens' => '10:00', 'closes' => '19:00'],
                ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Sunday', 'opens' => '10:00', 'closes' => '14:00'],
            ],
            'priceRange' => 'PRB',
            'potentialAction' => ['@type' => 'SearchAction', 'target' => home_url('/?post_type=product&s={search_term_string}'), 'query-input' => 'required name=search_term_string'],
        ]);
    } elseif (function_exists('is_product') && is_product()) {
        $p = wc_get_product(get_queried_object_id());
        if (!$p) return;
        $c = tek_seo_ctx();
        $imgs = [];
        foreach (array_filter(array_merge([$p->get_image_id()], $p->get_gallery_image_ids())) as $iid) $imgs[] = wp_get_attachment_image_url($iid, 'large');
        $d = [
            '@context' => 'https://schema.org', '@type' => 'Product',
            'name' => $p->get_name(), 'sku' => $p->get_sku(),
            'description' => tek_seo_trim($p->get_short_description() ?: $p->get_name(), 300),
            'url' => $c['canon'],
        ];
        if ($imgs) $d['image'] = $imgs;
        $price = (float) $p->get_price();
        if ($price > 0) $d['offers'] = [
            '@type' => 'Offer', 'url' => $c['canon'], 'price' => number_format($price, 2, '.', ''), 'priceCurrency' => get_woocommerce_currency(),
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability' => $p->get_stock_status() === 'onbackorder' ? 'https://schema.org/PreOrder' : ($p->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'),
            'seller' => ['@type' => 'Organization', 'name' => TEK_BRAND],
        ];
        tek_ld($d);
        $cats = get_the_terms($p->get_id(), 'product_cat');
        if ($cats && !is_wp_error($cats)) {
            usort($cats, function ($a, $b) { return count(get_ancestors($b->term_id, 'product_cat')) - count(get_ancestors($a->term_id, 'product_cat')); });
            $items = array_merge([['Главная', home_url('/')]], tek_term_crumbs($cats[0]), [[$p->get_name(), $c['canon']]]);
            tek_crumbs_ld($items);
        }
    } elseif (function_exists('is_product_category') && is_product_category()) {
        $t = get_queried_object();
        if ($t && !is_wp_error($t)) tek_crumbs_ld(array_merge([['Главная', home_url('/')]], tek_term_crumbs($t)));
    }
}, 5);

/** Фото товаров: если alt пустой, подставляем название товара. */
add_filter('wp_get_attachment_image_attributes', function ($attr, $att) {
    if (empty($attr['alt']) && $att && $att->post_parent && get_post_type($att->post_parent) === 'product') $attr['alt'] = get_the_title($att->post_parent);
    return $attr;
}, 10, 2);

/** robots.txt: закрываем служебные адреса и бесконечные комбинации фильтров. */
add_filter('robots_txt', function ($out, $public) {
    if (!$public) return $out;
    $add = "Disallow: /cart/\nDisallow: /checkout/\nDisallow: /my-account/\nDisallow: /sravnenie/\n"
        . "Disallow: /*?s=\nDisallow: /*&s=\nDisallow: /*?orderby=\nDisallow: /*&orderby=\nDisallow: /*?f[\nDisallow: /*&f[\n"
        . "Disallow: /*?stock=\nDisallow: /*&stock=\nDisallow: /*?min_price=\nDisallow: /*?max_price=\nDisallow: /*?per_page=\nDisallow: /*?add-to-cart=\nDisallow: /*?ids=\n";
    // вставляем после первой строки User-agent
    return preg_replace('~(User-agent: \*\R)~', "$1" . $add, $out, 1);
}, 20, 2);

/** Карта сайта: без пользователей, корзины, оформления и сравнения. */
add_filter('wp_sitemaps_add_provider', function ($p, $name) { return $name === 'users' ? false : $p; }, 10, 2);
add_filter('wp_sitemaps_posts_query_args', function ($args, $type) {
    if ($type === 'page') {
        $ex = [];
        foreach (['cart', 'checkout', 'myaccount'] as $k) { $id = function_exists('wc_get_page_id') ? wc_get_page_id($k) : 0; if ($id > 0) $ex[] = $id; }
        $cmp = get_page_by_path('sravnenie'); if ($cmp) $ex[] = $cmp->ID;
        if ($ex) $args['post__not_in'] = $ex;
    }
    return $args;
}, 10, 2);
