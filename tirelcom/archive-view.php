<?php
if (!defined('ABSPATH')) exit;
global $wp_query;
$term = is_tax('product_cat') ? get_queried_object() : null;
$is_search = is_search();
$shop = wc_get_page_permalink('shop');
$crumbs = [['Главная', home_url('/')]];
if ($term) {
    $anc = array_reverse(get_ancestors($term->term_id, 'product_cat'));
    $crumbs[] = ['Каталог', $shop];
    foreach ($anc as $aid) { $a = get_term($aid, 'product_cat'); $crumbs[] = [$a->name, get_term_link($a)]; }
}
if ($is_search) $title = 'Поиск: ' . get_search_query();
elseif ($term) $title = $term->name;
else $title = 'Каталог';
$root = null;
if ($term) { $anc = get_ancestors($term->term_id, 'product_cat'); $root = $anc ? get_term(end($anc), 'product_cat') : $term; }
$side_parent = $root ? $root->term_id : 0;
$side_cats = get_terms(['taxonomy' => 'product_cat', 'parent' => $side_parent, 'hide_empty' => false, 'orderby' => 'name']);
if ($side_parent === 0 && !is_wp_error($side_cats)) $side_cats = tek_top_cats();
$total = (int) $wp_query->found_posts;
$paged = max(1, (int) get_query_var('paged'));
$per = (int) $wp_query->get('posts_per_page'); if ($per < 1) $per = 24;
$from = $total ? ($paged - 1) * $per + 1 : 0; $to = min($total, $paged * $per);
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'menu_order';
$sorts = ['menu_order' => $is_search ? 'По релевантности' : 'Сначала в наличии', 'title' => 'По названию (А–Я)', 'price' => 'Сначала дешевле', 'price-desc' => 'Сначала дороже'];
$pers = [24, 48, 96];
$minp = isset($_GET['min_price']) ? esc_attr($_GET['min_price']) : '';
$maxp = isset($_GET['max_price']) ? esc_attr($_GET['max_price']) : '';
$base_url = $term ? get_term_link($term) : ($is_search ? home_url('/') : $shop);
?>
<main>
<section style="max-width:1200px;margin:0 auto;padding:28px 24px 8px">
<nav aria-label="Хлебные крошки" style="display:flex;flex-wrap:wrap;gap:6px 10px;font-size:14px;color:#44506A">
<?php foreach ($crumbs as $c) : ?><a class="hv-link" href="<?php echo esc_url($c[1]); ?>"><?php echo esc_html($c[0]); ?></a><span aria-hidden="true">/</span><?php endforeach; ?>
<span><?php echo esc_html($title); ?></span>
</nav>
<h1 style="margin:16px 0 0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em"><?php echo esc_html($title); ?></h1>
<?php if ($term && $term->description) : ?><p style="margin:10px 0 0;font-size:16px;line-height:1.55;color:#33405A;max-width:760px"><?php echo esc_html($term->description); ?></p><?php endif; ?>
</section>

<section style="max-width:1200px;margin:0 auto;padding:24px 24px 16px;display:flex;flex-wrap:wrap;gap:28px;align-items:flex-start">
<aside class="cat-aside" style="flex:0 0 260px;max-width:100%;display:flex;flex-direction:column;gap:20px">
<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:20px">
<h2 style="margin:0 0 12px;font-size:16px;font-weight:800"><?php echo $root ? esc_html($root->name) : 'Разделы каталога'; ?></h2>
<ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:2px">
<?php if (!is_wp_error($side_cats)) foreach ($side_cats as $s) :
    $cur = $term && ($s->term_id === $term->term_id || term_is_ancestor_of($s->term_id, $term->term_id, 'product_cat')); ?>
<li><a href="<?php echo esc_url(get_term_link($s)); ?>" style="display:block;padding:8px 10px;border-radius:8px;background:<?php echo $cur ? '#E6EEFC' : 'transparent'; ?>;color:<?php echo $cur ? '#1D4ED8' : '#0F1B33'; ?>;font-weight:<?php echo $cur ? '700' : '500'; ?>;font-size:14px;line-height:1.35"><?php echo esc_html($s->name); ?></a></li>
<?php endforeach; ?>
</ul>
</div>

<form method="get" action="<?php echo esc_url($base_url); ?>" style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:20px">
<h2 style="margin:0;font-size:16px;font-weight:800">Фильтры</h2>
<?php if ($is_search) : ?><input type="hidden" name="s" value="<?php echo esc_attr(get_search_query()); ?>"><input type="hidden" name="post_type" value="product"><?php endif; ?>
<?php if (isset($_GET['orderby'])) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>"><?php endif; ?>
<fieldset style="margin:0;padding:0;border:0;display:flex;flex-direction:column;gap:10px">
<legend style="padding:0;margin-bottom:10px;font-size:14px;font-weight:700">Цена, руб. ПМР</legend>
<div style="display:flex;gap:8px;align-items:center">
<label for="pmin" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Цена от</label>
<input id="pmin" name="min_price" type="number" min="0" value="<?php echo $minp; ?>" placeholder="от" style="width:100%;min-width:0;height:44px;border:1px solid #C5D1E6;border-radius:10px;padding:0 12px;font-family:inherit;font-size:15px;color:#0F1B33">
<span aria-hidden="true" style="color:#44506A">–</span>
<label for="pmax" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Цена до</label>
<input id="pmax" name="max_price" type="number" min="0" value="<?php echo $maxp; ?>" placeholder="до" style="width:100%;min-width:0;height:44px;border:1px solid #C5D1E6;border-radius:10px;padding:0 12px;font-family:inherit;font-size:15px;color:#0F1B33">
</div>
</fieldset>
<div style="display:flex;flex-direction:column;gap:8px">
<button class="hv-blue" type="submit" style="height:46px;border:0;border-radius:12px;background:#1D4ED8;color:#FFFFFF;font-size:15px;font-weight:700">Показать</button>
<a href="<?php echo esc_url($is_search ? add_query_arg(['s' => get_search_query(), 'post_type' => 'product'], home_url('/')) : $base_url); ?>" style="display:flex;align-items:center;justify-content:center;height:44px;border:1px solid #C5D1E6;border-radius:12px;background:#FFFFFF;color:#0F1B33;font-size:15px;font-weight:600">Сбросить</a>
</div>
</form>

<div style="background:#0F2A5C;color:#FFFFFF;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:10px">
<span style="font-size:16px;font-weight:700;line-height:1.35">Не нашли нужную деталь?</span>
<span style="font-size:14px;line-height:1.5;color:#C9D6F2">Позвоните или напишите маркировку. Подскажем аналог.</span>
<a href="tel:+37353351338" style="color:#FF8A1F;font-weight:700;font-size:16px">+373 533 51338</a>
</div>
</aside>

<div class="cat-main" style="flex:1 1 560px;min-width:0;display:flex;flex-direction:column;gap:20px">
<?php if ($is_search && $wp_query->get('tek_relaxed')) : ?><div style="background:#FFF1DE;border:1px solid #F3D3A5;border-radius:12px;padding:12px 16px;font-size:15px;line-height:1.5;color:#5B3300">Точного совпадения по всем параметрам нет. Показаны товары по названию без размеров и единиц. Проверьте номинал в карточке или позвоните: <a href="tel:+37353351338" style="font-weight:700;color:#1D4ED8">+373 533 51338</a>.</div><?php endif; ?>
<div style="display:flex;flex-wrap:wrap;gap:12px 20px;justify-content:space-between;align-items:center;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:14px;padding:12px 16px">
<span style="font-size:15px;color:#33405A"><?php if ($total) : ?>Показано <strong style="font-weight:700;color:#0F1B33"><?php echo (int) $from; ?>–<?php echo (int) $to; ?> из <?php echo (int) $total; ?></strong><?php else : ?>Ничего не найдено<?php endif; ?></span>
<form method="get" action="<?php echo esc_url($base_url); ?>" style="display:flex;flex-wrap:wrap;gap:12px 20px;align-items:center">
<?php foreach (['s', 'post_type', 'min_price', 'max_price'] as $k) if (!empty($_GET[$k])) echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($_GET[$k]) . '">'; ?>
<div style="display:flex;align-items:center;gap:8px">
<label for="sort" style="font-size:14px;color:#44506A">Сортировка</label>
<select id="sort" name="orderby" onchange="this.form.submit()" style="height:40px;border:1px solid #C5D1E6;border-radius:10px;padding:0 10px;font-family:inherit;font-size:14px;background:#FFFFFF;color:#0F1B33">
<?php foreach ($sorts as $k => $l) echo '<option value="' . esc_attr($k) . '"' . selected($orderby, $k, false) . '>' . esc_html($l) . '</option>'; ?>
</select>
</div>
<div style="display:flex;align-items:center;gap:8px">
<label for="per" style="font-size:14px;color:#44506A">На странице</label>
<select id="per" name="per_page" onchange="this.form.submit()" style="height:40px;border:1px solid #C5D1E6;border-radius:10px;padding:0 10px;font-family:inherit;font-size:14px;background:#FFFFFF;color:#0F1B33">
<?php foreach ($pers as $n) echo '<option value="' . $n . '"' . selected($per, $n, false) . '>' . $n . '</option>'; ?>
</select>
</div>
</form>
</div>

<?php if (have_posts()) : ?>
<div class="pgrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px">
<?php while (have_posts()) : the_post(); $p = wc_get_product(get_the_ID()); if (!$p) continue; $img = get_the_post_thumbnail_url($p->get_id(), 'woocommerce_single'); ?>
<article style="display:flex;flex-direction:column;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;overflow:hidden">
<a href="<?php the_permalink(); ?>" style="display:block;height:160px;background:#FFFFFF;border-bottom:1px solid #E6ECF6;padding:10px"><?php if ($img) : ?><img class="tek-pimg" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p->get_name()); ?>" loading="lazy"><?php else : ?><span style="display:flex;height:100%;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:12px;letter-spacing:0.08em;color:#8091B5">НЕТ ФОТО</span><?php endif; ?></a>
<div style="padding:16px;display:flex;flex-direction:column;gap:10px;flex:1">
<span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8"><?php echo esc_html(tek_product_tag($p)); ?></span>
<h3 style="margin:0;font-size:15px;font-weight:600;line-height:1.4;flex:1"><a href="<?php the_permalink(); ?>" style="color:inherit;text-decoration:none"><?php echo esc_html($p->get_name()); ?></a></h3>
<div style="display:flex;align-items:baseline;gap:6px">
<span data-prc="<?php echo esc_attr($p->get_price()); ?>" style="font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:600"><?php echo esc_html(tek_price($p)); ?></span>
<span data-unit style="font-size:13px;color:#44506A">руб. ПМР</span>
</div>
<div style="display:flex;gap:8px"><a class="hv-orange" href="<?php echo esc_url($p->add_to_cart_url()); ?>" rel="nofollow" style="flex:1;display:flex;align-items:center;justify-content:center;height:44px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-size:15px;font-weight:700;text-decoration:none">В корзину</a><?php echo tek_cmp_btn($p->get_id()); ?></div>
</div>
</article>
<?php endwhile; ?>
</div>
<?php
$links = paginate_links(['total' => (int) $wp_query->max_num_pages, 'current' => $paged, 'type' => 'array', 'prev_text' => '← Назад', 'next_text' => 'Дальше →', 'mid_size' => 2, 'add_args' => false]);
if ($links) : ?>
<nav aria-label="Страницы каталога" class="tek-pager" style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;padding:8px 0">
<?php foreach ($links as $l) echo $l; ?>
</nav>
<?php endif; ?>
<?php else : ?>
<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:32px;font-size:16px;line-height:1.6;color:#33405A">По вашему запросу ничего не найдено. Попробуйте другое название или позвоните нам: <a class="hv-link" href="tel:+37353351338" style="font-weight:700">+373 533 51338</a>. Подскажем аналог.</div>
<?php endif; ?>
</div>
</section>
</main>
