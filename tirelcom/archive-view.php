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
if ($is_search) $title = trim(get_search_query()) === '' ? 'Поиск по каталогу' : 'Поиск: ' . get_search_query();
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

<?php $nsel_early = tek_only_stock() || tek_selected_filters() || !empty($_GET['min_price']) || !empty($_GET['max_price']); ?>
<section style="max-width:1200px;margin:0 auto;padding:20px 24px 0">
<?php
$tiles = [];
if ($term) { foreach (get_terms(['taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => true, 'orderby' => 'name']) as $tc) $tiles[] = $tc; }
elseif (!$is_search) { $tiles = array_filter(tek_top_cats(), function ($x) { return $x->count > 0 || get_terms(['taxonomy' => 'product_cat', 'parent' => $x->term_id, 'hide_empty' => true, 'fields' => 'ids']); }); }
if ($tiles && !$nsel_early) : ?>
<section class="tek-tiles" aria-label="Подразделы">
<?php foreach ($tiles as $tc) : ?><a class="tek-tile" href="<?php echo esc_url(get_term_link($tc)); ?>"><span class="tek-tile-n"><?php echo esc_html($tc->name); ?></span><span class="tek-tile-c"><?php echo (int) $tc->count; ?> шт.</span></a><?php endforeach; ?>
</section>
<?php endif; ?>
</section>
<section style="max-width:1200px;margin:0 auto;padding:16px 24px 16px;display:flex;flex-wrap:wrap;gap:28px;align-items:flex-start">
<aside class="cat-aside" style="flex:0 0 260px;max-width:100%;display:flex;flex-direction:column;gap:20px">
<details class="tek-fbox tek-catbox" open>
<summary class="tek-fsum"><?php echo $root ? esc_html($root->name) : 'Разделы каталога'; ?></summary>
<ul style="margin:0;padding:0 20px 20px;list-style:none;display:flex;flex-direction:column;gap:2px">
<?php if (!is_wp_error($side_cats)) foreach ($side_cats as $s) :
    $cur = $term && ($s->term_id === $term->term_id || term_is_ancestor_of($s->term_id, $term->term_id, 'product_cat')); ?>
<li><a href="<?php echo esc_url(get_term_link($s)); ?>" style="display:block;padding:8px 10px;border-radius:8px;background:<?php echo $cur ? '#E6EEFC' : 'transparent'; ?>;color:<?php echo $cur ? '#1D4ED8' : '#0F1B33'; ?>;font-weight:<?php echo $cur ? '700' : '500'; ?>;font-size:14px;line-height:1.35"><?php echo esc_html($s->name); ?></a></li>
<?php endforeach; ?>
</ul>
</details>


<div style="background:#0F2A5C;color:#FFFFFF;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:10px">
<span style="font-size:16px;font-weight:700;line-height:1.35">Не нашли нужную деталь?</span>
<span style="font-size:14px;line-height:1.5;color:#C9D6F2">Позвоните или напишите маркировку. Подскажем аналог.</span>
<a href="tel:+37353351338" style="color:#FF8A1F;font-weight:700;font-size:16px">+373 533 51338</a>
<a href="<?php echo esc_url(home_url('/podbor/')); ?>" style="color:#FFFFFF;font-weight:600;font-size:14px;text-decoration:underline">Оставить заявку на подбор</a>
</div>
</aside>

<div class="cat-main" style="flex:1 1 560px;min-width:0;display:flex;flex-direction:column;gap:20px">
<?php if ($nsel) : ?>
<div class="tek-chips"><?php
$chip = function ($label, $url) { echo '<a class="tek-chip" href="' . esc_url($url) . '">' . esc_html($label) . ' <span aria-hidden="true">×</span></a>'; };
if (tek_only_stock()) $chip('Только в наличии', remove_query_arg(['stock', 'paged']));
if ($minp !== '' || $maxp !== '') $chip('Цена: ' . ($minp !== '' ? 'от ' . $minp . ' ' : '') . ($maxp !== '' ? 'до ' . $maxp : ''), remove_query_arg(['min_price', 'max_price', 'paged']));
foreach ($sel as $tax => $vals) foreach ($vals as $v) {
    $t = get_term_by('slug', $v, $tax);
    $rest = array_values(array_diff($vals, [$v]));
    $url = $rest ? add_query_arg(['f' => array_merge($sel, [$tax => $rest])], remove_query_arg('paged')) : add_query_arg(['f' => array_diff_key($sel, [$tax => 1])], remove_query_arg('paged'));
    $chip(wc_attribute_label($tax) . ': ' . ($t ? $t->name : $v), $url);
}
?><a class="tek-chip tek-chip-x" href="<?php echo esc_url($reset_url); ?>">Сбросить всё</a></div>
<?php endif; ?>
<?php if ($is_search && $wp_query->get('tek_alt')) : ?><div style="background:#FFF4E6;border:1px solid #F3D3A5;border-radius:12px;padding:12px 16px;font-size:15px;color:#5B3300">Показаны результаты для «<?php echo esc_html($wp_query->get('tek_alt')); ?>»: мы исправили раскладку клавиатуры.</div><?php endif; ?>
<?php if ($is_search && $wp_query->get('tek_relaxed')) : ?><div style="background:#FFF1DE;border:1px solid #F3D3A5;border-radius:12px;padding:12px 16px;font-size:15px;line-height:1.5;color:#5B3300">Точного совпадения по всем параметрам нет. Показаны товары по названию без размеров и единиц. Проверьте номинал в карточке или позвоните: <a href="tel:+37353351338" style="font-weight:700;color:#1D4ED8">+373 533 51338</a>.</div><?php endif; ?>
<?php
$facets = tek_facets($term, $is_search ? get_search_query() : '');
$sel = tek_selected_filters();
$nsel = (tek_only_stock() ? 1 : 0) + ($minp !== '' || $maxp !== '' ? 1 : 0);
foreach ($sel as $vv) $nsel += count($vv);
$reset_url = $is_search ? add_query_arg(['s' => get_search_query(), 'post_type' => 'product'], home_url('/')) : $base_url;
?>
<div class="tek-tbcard">
<div class="tek-toolbar">
<div class="tek-tbl"><button type="button" class="tek-ftoggle" id="tek-ftoggle" aria-expanded="<?php echo $nsel ? 'true' : 'false'; ?>" aria-controls="tek-fpanel">Фильтры<?php if ($nsel) : ?> <span class="tek-fbadge"><?php echo (int) $nsel; ?></span><?php endif; ?></button>
<span style="font-size:15px;color:#33405A"><?php if ($total) : ?>Показано <strong style="font-weight:700;color:#0F1B33"><?php echo (int) $from; ?>–<?php echo (int) $to; ?> из <?php echo (int) $total; ?></strong><?php else : ?>Ничего не найдено<?php endif; ?></span></div>
<form method="get" action="<?php echo esc_url($base_url); ?>" style="display:flex;flex-wrap:wrap;gap:12px 20px;align-items:center">
<?php echo tek_hidden_inputs(['orderby', 'per_page']); ?>
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
<div class="tek-fpanel" id="tek-fpanel"<?php echo $nsel ? '' : ' hidden'; ?>>
<form id="tek-ff" method="get" action="<?php echo esc_url($base_url); ?>">
<?php foreach (['s', 'post_type', 'orderby', 'per_page'] as $hk) if (isset($_GET[$hk]) && $_GET[$hk] !== '') echo '<input type="hidden" name="' . esc_attr($hk) . '" value="' . esc_attr(wp_unslash($_GET[$hk])) . '">'; ?>
<div class="tek-fgrid">
<?php if ($nsel) : ?><a class="tek-freset" href="<?php echo esc_url($reset_url); ?>">✕ Сбросить все фильтры (<?php echo (int) $nsel; ?>)</a><?php endif; ?>
<label class="tek-fi tek-fstock"><input type="checkbox" name="stock" value="1" <?php checked(tek_only_stock()); ?>> <span>Только в наличии</span></label>
<?php $fi = 0; foreach ($facets as $tax => $f) : $fi++; $chosen = isset($sel[$tax]) ? $sel[$tax] : []; $cnt = count($f['terms']); ?>
<details class="tek-fct"<?php echo ($chosen) ? ' open' : ''; ?>>
<summary><?php echo esc_html($f['label']); ?><?php if ($chosen) : ?> <span class="tek-fbadge"><?php echo count($chosen); ?></span><?php endif; ?></summary>
<div class="tek-fl">
<?php foreach ($f['terms'] as $k => $tm) : $on = in_array($tm[0], $chosen, true); ?>
<label class="tek-fi<?php echo ($k >= 8 && !$on) ? ' tek-more' : ''; ?>"><input type="checkbox" name="f[<?php echo esc_attr($tax); ?>][]" value="<?php echo esc_attr($tm[0]); ?>" <?php checked($on); ?>> <span><?php echo esc_html($tm[1]); ?></span><em><?php echo (int) $tm[2]; ?></em></label>
<?php endforeach; ?>
<?php if ($cnt > 8) : ?><button type="button" class="tek-fmore" data-more="Показать ещё <?php echo (int) ($cnt - 8); ?>" data-less="Свернуть">Показать ещё <?php echo (int) ($cnt - 8); ?></button><?php endif; ?>
</div>
</details>
<?php endforeach; ?>
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
</div>
<div class="tek-fact">
<button class="hv-blue" type="submit" style="height:46px;border:0;border-radius:12px;background:#1D4ED8;color:#FFFFFF;font-size:15px;font-weight:700">Показать</button>
<?php if ($nsel) : ?><a href="<?php echo esc_url($reset_url); ?>" style="display:flex;align-items:center;justify-content:center;height:44px;border:1px solid #C5D1E6;border-radius:12px;background:#FFFFFF;color:#0F1B33;font-size:15px;font-weight:600">Сбросить фильтры</a><?php endif; ?>
</div>
</form>
</div>
</div>

<?php if (have_posts() && !($is_search && trim(get_search_query()) === '')) : ?>
<div class="pgrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px">
<?php while (have_posts()) : the_post(); $p = wc_get_product(get_the_ID()); if (!$p) continue; $img = get_the_post_thumbnail_url($p->get_id(), 'woocommerce_single'); ?>
<article style="display:flex;flex-direction:column;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;overflow:hidden">
<a href="<?php the_permalink(); ?>" style="position:relative;display:block;height:160px;background:#FFFFFF;border-bottom:1px solid #E6ECF6;padding:10px"><?php echo tek_new_badge($p); ?><?php if ($img) : ?><img class="tek-pimg" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p->get_name()); ?>" loading="lazy"><?php else : ?><?php echo tek_nophoto($p); ?><?php endif; ?></a>
<div style="padding:16px;display:flex;flex-direction:column;gap:10px;flex:1">
<span class="tek-ptag"><?php echo esc_html(tek_product_tag($p)); ?></span>
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
<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:32px;font-size:16px;line-height:1.6;color:#33405A"><?php echo ($is_search && trim(get_search_query()) === '') ? 'Вы ничего не ввели. Напишите название или маркировку детали, например LM317, 2N2222 или «резистор 10 кОм», и нажмите «Найти». ' : 'По вашему запросу ничего не найдено. '; ?>Попробуйте другое название или позвоните нам: <a class="hv-link" href="tel:+37353351338" style="font-weight:700">+373 533 51338</a>. Подскажем аналог.<br><a class="hv-orange" href="<?php echo esc_url(add_query_arg('q', get_search_query(), home_url('/podbor/'))); ?>" style="display:inline-flex;align-items:center;margin-top:14px;min-height:48px;padding:0 24px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-weight:700">Оставить заявку на подбор</a></div>
<?php endif; ?>
</div>
</section>
</main>
