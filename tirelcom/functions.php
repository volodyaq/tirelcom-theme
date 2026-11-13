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
        'cmpUrl' => home_url('/sravnenie/'),
        'suggest' => esc_url_raw(rest_url('tek/v1/suggest')),
        'searchUrl' => home_url('/'),
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
    return $deep->name;
}

/* Товары для главной: с фото, случайные, кэш на 6 часов */
function tek_home_products($n = 7) {
    $ids = get_transient('tek_home_ids10');
    if (!is_array($ids) || count($ids) < $n) {
        $q = new WP_Query([
            'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => $n,
            'orderby' => 'rand', 'fields' => 'ids', 'no_found_rows' => true,
            'meta_query' => [['key' => '_thumbnail_id', 'compare' => 'EXISTS']],
        ]);
        $ids = $q->posts;
        if ($ids) set_transient('tek_home_ids10', $ids, 6 * HOUR_IN_SECONDS);
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

/* Дерево разделов для меню: [[term, link, [[term, link, [[term, link], ...]], ...]], ...] (кэш на час) */
function tek_nav_tree() {
    $c = get_transient('tek_nav_tree_v2');
    if (is_array($c)) return $c;
    $all = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name']);
    if (is_wp_error($all)) return [];
    $kids = [];
    foreach ($all as $t) $kids[$t->parent][] = $t;
    $node = function ($t) use (&$node, &$kids) {
        $o = ['n' => $t->name, 'u' => get_term_link($t), 'c' => (int) $t->count, 'k' => []];
        foreach ($kids[$t->term_id] ?? [] as $k) $o['k'][] = ['n' => $k->name, 'u' => get_term_link($k), 'c' => (int) $k->count, 'k' => array_map(function ($x) { return ['n' => $x->name, 'u' => get_term_link($x), 'c' => (int) $x->count]; }, array_slice((function ($arr) { usort($arr, function ($a, $b) { return $b->count <=> $a->count; }); return $arr; })($kids[$k->term_id] ?? []), 0, 6))];
        return $o;
    };
    $tree = [];
    foreach (tek_top_cats() as $t) { if (empty($t->count) && empty($kids[$t->term_id])) continue; $tree[] = $node($t); }
    set_transient('tek_nav_tree_v2', $tree, HOUR_IN_SECONDS);
    return $tree;
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

add_filter('woocommerce_coupons_enabled', '__return_false');

/* ---- Письмо о новом заказе ---- */
add_filter('woocommerce_email_subject_new_order', function ($s, $order) {
    return 'ТирЭлКом. Радиодетали - Заказ ' . $order->get_order_number();
}, 10, 2);
add_filter('woocommerce_email_heading_new_order', '__return_empty_string');
add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
add_filter('woocommerce_email_enabled_customer_on_hold_order', '__return_false');
add_filter('woocommerce_email_enabled_customer_completed_order', '__return_false');
add_filter('woocommerce_email_recipient_new_order', function ($r) {
    $to = get_option('tirelcom_order_email');
    return $to ? $to : $r;
});

/* Описание: важное открыто, «Полезно знать» и «Частые вопросы» раскрываются по нажатию */
function tek_desc_html($html) {
    $html = wp_kses_post(wpautop($html));
    $parts = preg_split('/(<h3[^>]*>.*?<\/h3>)/isu', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (count($parts) < 3) return $html;
    $out = $parts[0];
    for ($i = 1; $i < count($parts); $i += 2) {
        $title = trim(wp_strip_all_tags($parts[$i]));
        $body = $parts[$i + 1] ?? '';
        if (preg_match('/^(Полезно знать|Частые вопросы)/u', $title)) {
            $out .= '<details class="tek-acc"><summary>' . esc_html($title) . '</summary><div class="tek-acc-body">' . $body . '</div></details>';
        } else {
            $out .= $parts[$i] . $body;
        }
    }
    return $out;
}

/* ===== Сравнение и похожие товары ===== */

/* Кнопка «Сравнить» (иконка) для карточек */
function tek_cmp_btn($id, $wide = false) {
    $id = (int) $id;
    if ($wide) {
        return '<button type="button" class="tek-cmp-btn tek-cmp-wide" data-cmp="' . $id . '" aria-pressed="false"><span class="tek-cmp-ic" aria-hidden="true">⇄</span> <span class="tek-cmp-tx">Добавить к сравнению</span></button>';
    }
    return '<button type="button" class="tek-cmp-btn" data-cmp="' . $id . '" aria-pressed="false" title="Добавить к сравнению" aria-label="Добавить к сравнению"><span class="tek-cmp-ic" aria-hidden="true">⇄</span></button>';
}

/* Атрибуты, которые не считаются «параметрами» при подборе похожих */
function tek_nonparam_attr($tax) {
    static $skip = ['pa_brend','pa_proizvoditel','pa_cvet-korpusa','pa_cvet-svecheniya','pa_naznachenie','pa_funkciya',
        'pa_drugie-oboznacheniya','pa_katalozhnyy-nomer','pa_markirovka','pa_markirovka-drugoe-ob','pa_analog','pa_model-pulta',
        'pa_marka-avto','pa_osobennost','pa_ispolnenie','pa_komplementarnaya-par','pa_cokolevka-to-220-vid','pa_klass',
        'pa_emkost-drugie-edinic','pa_golosovoe-upravlenie'];
    return in_array($tax, $skip, true);
}

/* Параметры товара для подбора: [taxonomy => [term_taxonomy_id => имя]] */
function tek_param_terms($p) {
    $out = [];
    foreach ($p->get_attributes() as $a) {
        if (!is_object($a) || !$a->is_taxonomy() || !$a->get_visible()) continue;
        $tax = $a->get_name();
        if (tek_nonparam_attr($tax)) continue;
        $terms = wp_get_object_terms($p->get_id(), $tax);
        if (is_wp_error($terms) || !$terms) continue;
        foreach ($terms as $t) $out[$tax][(int) $t->term_taxonomy_id] = $t->name;
    }
    return $out;
}

/* Похожие в наличии: та же категория, совпадение ≥ 2 параметров .
   Возвращает [[product, [совпавшие параметры: «Название: значение»]], ...] */
function tek_similar($p, $n = 4) {
    global $wpdb;
    $terms = get_the_terms($p->get_id(), 'product_cat');
    if (!$terms || is_wp_error($terms)) return [];
    $deep = null;
    foreach ($terms as $t) { if ($deep === null || $t->parent) $deep = $t; }
    $params = tek_param_terms($p);
    $tt = [];
    foreach ($params as $arr) foreach ($arr as $id => $nm) $tt[] = $id;
    $nparams = count($params);
    $ids = [];
    if ($tt) {
        $in = implode(',', array_map('intval', $tt));
        $sql = "SELECT r.object_id AS id, COUNT(DISTINCT tt.taxonomy) AS s
            FROM {$wpdb->term_relationships} r
            JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = r.term_taxonomy_id
            JOIN {$wpdb->term_relationships} c ON c.object_id = r.object_id AND c.term_taxonomy_id = %d
            JOIN {$wpdb->posts} po ON po.ID = r.object_id AND po.post_status = 'publish' AND po.post_type = 'product'
            JOIN {$wpdb->prefix}wc_product_meta_lookup l ON l.product_id = r.object_id AND l.stock_status = 'instock'
            WHERE r.term_taxonomy_id IN ($in) AND r.object_id <> %d
            GROUP BY r.object_id
            HAVING s >= %d
            ORDER BY s DESC, l.min_price ASC
            LIMIT %d";
        $need = 2;
        $rows = $wpdb->get_results($wpdb->prepare($sql, (int) $deep->term_taxonomy_id, $p->get_id(), $need, $n));
        foreach ($rows as $r) $ids[] = (int) $r->id;
    }
    $res = [];
    foreach ($ids as $id) {
        $x = wc_get_product($id);
        if (!$x) continue;
        $match = [];
        foreach ($params as $tax => $arr) {
            $vals = wp_get_object_terms($id, $tax, ['fields' => 'tt_ids']);
            if (is_wp_error($vals)) continue;
            $same = array_intersect(array_keys($arr), array_map('intval', $vals));
            if ($same) $match[] = wc_attribute_label($tax) . ': ' . implode(', ', array_map(function ($i) use ($arr) { return $arr[$i]; }, $same));
        }
        $res[] = [$x, $match];
    }
    return $res;
}

/* Данные для таблицы сравнения: [[label, [значение по товарам]], ...] */
function tek_compare_rows($products) {
    $rows = [];
    $order = [];
    $vals = [];
    foreach ($products as $i => $p) {
        foreach ($p->get_attributes() as $a) {
            if (!is_object($a) || !$a->get_visible()) continue;
            if ($a->is_taxonomy()) {
                $key = $a->get_name();
                $label = wc_attribute_label($key);
                $v = implode(', ', wc_get_product_terms($p->get_id(), $key, ['fields' => 'names']));
            } else {
                $key = 'c_' . sanitize_title($a->get_name());
                $label = $a->get_name();
                $v = implode(', ', $a->get_options());
            }
            if ($v === '') continue;
            if (!isset($vals[$key])) { $vals[$key] = ['label' => $label, 'v' => array_fill(0, count($products), '')]; $order[$key] = 0; }
            $vals[$key]['v'][$i] = $v;
            $order[$key]++;
        }
    }
    arsort($order);
    foreach (array_keys($order) as $key) {
        $v = $vals[$key]['v'];
        $rows[] = ['label' => $vals[$key]['label'], 'v' => $v, 'diff' => count(array_unique($v)) > 1];
    }
    return $rows;
}

require_once __DIR__ . '/inc/search.php';
require_once __DIR__ . '/inc/filters.php';
require_once __DIR__ . '/inc/telegram.php';
require_once __DIR__ . '/inc/reviews.php';
require_once __DIR__ . '/inc/redirects.php';
require_once __DIR__ . '/inc/seo.php';
require_once get_template_directory() . '/inc/as-guard.php';

/* Популярные подразделы для главной: где больше всего товаров в наличии (прямое назначение товара разделу) */
function tek_popular_cats($n = 12) {
    $c = get_transient('tek_popular_cats_v1');
    if (is_array($c)) return array_slice($c, 0, $n);
    global $wpdb;
    $rows = $wpdb->get_results("SELECT tt.term_id id, COUNT(*) c FROM {$wpdb->term_relationships} r JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = r.term_taxonomy_id AND tt.taxonomy = 'product_cat' JOIN {$wpdb->prefix}wc_product_meta_lookup l ON l.product_id = r.object_id AND l.stock_status = 'instock' JOIN {$wpdb->posts} p ON p.ID = r.object_id AND p.post_status = 'publish' GROUP BY tt.term_id ORDER BY c DESC LIMIT 40");
    $out = []; $seen = [];
    foreach ($rows as $r) {
        $t = get_term((int) $r->id, 'product_cat');
        if (!$t || is_wp_error($t) || isset($seen[mb_strtolower($t->name)])) continue;
        if (preg_match('/^(прочее|прочие|другое|разное)/ui', $t->name)) continue;
        $seen[mb_strtolower($t->name)] = 1;
        $out[] = ['n' => $t->name, 'u' => get_term_link($t), 'c' => (int) $r->c];
    }
    set_transient('tek_popular_cats_v1', $out, 6 * HOUR_IN_SECONDS);
    return array_slice($out, 0, $n);
}

/* Новые поступления: товары с кодом TEK-… (добавлены вручную, не из старого каталога), в наличии, с фото */
/* «Новое»: товар считается новым, пока не прошла дата _tek_new_until (по умолчанию 30 дней с создания) */
function tek_is_new($p) {
    $id = is_object($p) ? $p->get_id() : (int) $p;
    return (int) get_post_meta($id, '_tek_new_until', true) > time();
}
function tek_new_badge($p) {
    return tek_is_new($p) ? '<span class="tek-new">Новое</span>' : '';
}
add_action('woocommerce_new_product', function ($id) {
    if (!get_post_meta($id, '_tek_new_until', true)) update_post_meta($id, '_tek_new_until', time() + 30 * DAY_IN_SECONDS);
});

function tek_new_products($n = 4) {
    $ids = get_transient('tek_new_ids_v3');
    if (!is_array($ids)) {
        global $wpdb;
        $ids = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT l.product_id FROM {$wpdb->prefix}wc_product_meta_lookup l JOIN {$wpdb->posts} p ON p.ID = l.product_id AND p.post_status = 'publish' LEFT JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '_thumbnail_id' JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_tek_new_until' AND CAST(m.meta_value AS UNSIGNED) > %d WHERE l.stock_status = 'instock' ORDER BY (t.meta_id IS NULL), RAND() LIMIT 24", time())));
        set_transient('tek_new_ids_v3', $ids, HOUR_IN_SECONDS);
    }
    if (count($ids) < $n) return [];
    $pick = array_slice($ids, 0, $n);
    return array_filter(array_map('wc_get_product', $pick));
}

/* Товары в наличии с фото для главной */
function tek_home_instock($n = 8) {
    $ids = get_transient('tek_home_instock_v1');
    if (!is_array($ids)) {
        global $wpdb;
        $ids = array_map('intval', $wpdb->get_col("SELECT l.product_id FROM {$wpdb->prefix}wc_product_meta_lookup l JOIN {$wpdb->posts} p ON p.ID = l.product_id AND p.post_status = 'publish' JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id' WHERE l.stock_status = 'instock' AND l.min_price > 0 ORDER BY RAND() LIMIT 24"));
        set_transient('tek_home_instock_v1', $ids, 6 * HOUR_IN_SECONDS);
    }
    return array_filter(array_map('wc_get_product', array_slice($ids, 0, $n)));
}


/* Заглушка для товаров без фото */
function tek_nophoto($label = 'Фото скоро появится') {
    return '<span class="tek-nophoto"><svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="13" y="13" width="22" height="22" rx="3"/><path d="M19 13V7M24 13V7M29 13V7M19 41v-6M24 41v-6M29 41v-6M13 19H7M13 24H7M13 29H7M41 19h-6M41 24h-6M41 29h-6"/><circle cx="19.5" cy="19.5" r="1.5"/></svg><span>' . esc_html($label) . '</span></span>';
}
