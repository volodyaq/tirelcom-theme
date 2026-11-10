<?php
if (!defined('ABSPATH')) exit;
get_header();
$ids = isset($_GET['ids']) ? array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', wp_unslash($_GET['ids'])))))), 0, 4) : [];
$products = [];
foreach ($ids as $id) { $x = wc_get_product($id); if ($x && $x->get_status() === 'publish') $products[] = $x; }
$base = home_url('/sravnenie/');
$rows = $products ? tek_compare_rows($products) : [];
$n = count($products);
?>
<main style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:20px">
<nav style="font-size:14px;color:#44506A"><a class="hv-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / Сравнение товаров</nav>
<h1 style="margin:0;font-size:34px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Сравнение товаров</h1>
<?php if ($n < 1) : ?>
<div id="tek-cmp-empty" style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:28px;display:flex;flex-direction:column;gap:12px;align-items:flex-start">
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A">Пока ничего не выбрано. На карточке товара нажмите «⇄», чтобы добавить его к сравнению (до 4 товаров).</p>
<a class="hv-orange" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" style="display:inline-flex;align-items:center;height:48px;padding:0 24px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-weight:700;text-decoration:none">Перейти в каталог</a>
</div>
<?php else : ?>
<div style="display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;justify-content:space-between">
<label style="display:flex;align-items:center;gap:8px;font-size:15px;color:#33405A;cursor:pointer"><input type="checkbox" id="tek-cmp-diff"> Показать только отличия</label>
<?php if ($n < 2) : ?><span style="font-size:14px;color:#8A4B00">Добавьте ещё товар, чтобы сравнить.</span><?php endif; ?>
</div>
<div class="tek-cmp-wrap">
<table class="tek-cmp" data-ids="<?php echo esc_attr(implode(',', array_map(function ($x) { return $x->get_id(); }, $products))); ?>">
<thead><tr><th class="tek-cmp-lab"></th>
<?php foreach ($products as $p) : $img = get_the_post_thumbnail_url($p->get_id(), 'woocommerce_single'); $rest = implode(',', array_diff(array_map(function ($x) { return $x->get_id(); }, $products), [$p->get_id()])); ?>
<th>
<a href="<?php echo esc_url(get_permalink($p->get_id())); ?>" class="tek-cmp-img"><?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p->get_name()); ?>"><?php endif; ?></a>
<a class="tek-cmp-name" href="<?php echo esc_url(get_permalink($p->get_id())); ?>"><?php echo esc_html($p->get_name()); ?></a>
<div class="tek-cmp-price"><span data-prc="<?php echo esc_attr($p->get_price()); ?>"><?php echo esc_html(tek_price($p)); ?></span> <span data-unit>руб. ПМР</span></div>
<?php if ($p->get_stock_status() === 'onbackorder') : ?><div class="tek-cmp-st tek-cmp-pre">Предзаказ</div><?php else : ?><div class="tek-cmp-st tek-cmp-in">В наличии</div><?php endif; ?>
<a class="hv-orange tek-cmp-buy" href="<?php echo esc_url($p->add_to_cart_url()); ?>" rel="nofollow">В корзину</a>
<a class="tek-cmp-rm" href="<?php echo esc_url(add_query_arg('ids', $rest, $base)); ?>" data-rm="<?php echo (int) $p->get_id(); ?>">Убрать</a>
</th>
<?php endforeach; ?>
</tr></thead>
<tbody>
<tr class="tek-cmp-row"><th class="tek-cmp-lab">Код товара</th><?php foreach ($products as $p) : ?><td><?php echo esc_html(preg_replace('/^TEK-/', '', (string) $p->get_sku())); ?></td><?php endforeach; ?></tr>
<tr class="tek-cmp-row"><th class="tek-cmp-lab">Раздел</th><?php foreach ($products as $p) : ?><td><?php echo esc_html(ucfirst(mb_strtolower(tek_product_tag($p)))); ?></td><?php endforeach; ?></tr>
<?php foreach ($rows as $r) : ?>
<tr class="tek-cmp-row<?php echo $r['diff'] ? ' tek-diff' : ''; ?>"><th class="tek-cmp-lab"><?php echo esc_html($r['label']); ?></th>
<?php foreach ($r['v'] as $v) : ?><td><?php echo $v === '' ? '<span class="tek-cmp-none">—</span>' : esc_html($v); ?></td><?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<p style="margin:0;font-size:14px;line-height:1.5;color:#44506A;max-width:760px">Строки с отличиями подсвечены. Прочерк значит, что параметр у товара не указан. Если сомневаетесь, подойдёт ли замена, позвоните: <a class="hv-link" href="tel:+37353351338" style="font-weight:700">+373 533 51338</a>.</p>
<?php endif; ?>
</main>
<?php get_footer(); ?>
