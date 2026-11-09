<?php
if (!defined('ABSPATH')) exit;
while (have_posts()) : the_post();
$p = wc_get_product(get_the_ID());
if (!$p) continue;
$terms = get_the_terms($p->get_id(), 'product_cat');
$deep = null;
if ($terms && !is_wp_error($terms)) foreach ($terms as $t) { if ($deep === null || $t->parent) $deep = $t; }
$crumbs = [['Главная', home_url('/')], ['Каталог', wc_get_page_permalink('shop')]];
if ($deep) {
    foreach (array_reverse(get_ancestors($deep->term_id, 'product_cat')) as $aid) { $a = get_term($aid, 'product_cat'); $crumbs[] = [$a->name, get_term_link($a)]; }
    $crumbs[] = [$deep->name, get_term_link($deep)];
}
$img_ids = array_filter(array_merge([$p->get_image_id()], $p->get_gallery_image_ids()));
$main = $img_ids ? wp_get_attachment_image_url(reset($img_ids), 'full') : '';
$code = preg_replace('/^TEK-/', '', (string) $p->get_sku());
$similar = [];
if ($deep) {
    $q = new WP_Query(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 4, 'post__not_in' => [$p->get_id()], 'orderby' => 'rand', 'no_found_rows' => true, 'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => [$deep->term_id]]], 'meta_query' => [['key' => '_thumbnail_id', 'compare' => 'EXISTS']]]);
    foreach ($q->posts as $sp) { $x = wc_get_product($sp->ID); if ($x) $similar[] = $x; }
    wp_reset_postdata();
}
?>
<main>
<section style="max-width:1200px;margin:0 auto;padding:24px 24px 0">
<nav aria-label="Хлебные крошки" style="display:flex;flex-wrap:wrap;gap:6px 10px;font-size:14px;color:#44506A">
<?php foreach ($crumbs as $c) : ?><a class="hv-link" href="<?php echo esc_url($c[1]); ?>"><?php echo esc_html($c[0]); ?></a><span aria-hidden="true">/</span><?php endforeach; ?>
<span><?php echo esc_html($p->get_name()); ?></span>
</nav>
</section>

<section style="max-width:1200px;margin:0 auto;padding:20px 24px 24px;display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
<div style="flex:1 1 460px;min-width:0;display:flex;flex-direction:column;gap:12px">
<div class="ph-big" style="height:460px;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:16px;display:flex;align-items:center;justify-content:center">
<?php if ($main) : ?><img id="tek-main-img" class="tek-pimg" src="<?php echo esc_url($main); ?>" alt="<?php echo esc_attr($p->get_name()); ?>"><?php else : ?><span style="font-family:'JetBrains Mono',monospace;font-size:13px;letter-spacing:0.08em;color:#8091B5">ФОТО СКОРО ПОЯВИТСЯ</span><?php endif; ?>
</div>
<?php if (count($img_ids) > 1) : ?>
<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px">
<?php foreach (array_slice($img_ids, 0, 4) as $i => $iid) : ?>
<button type="button" class="tek-thumb" data-full="<?php echo esc_url(wp_get_attachment_image_url($iid, 'full')); ?>" style="height:96px;background:#FFFFFF;border:<?php echo $i === 0 ? '2px solid #1D4ED8' : '1px solid #D5DEEE'; ?>;border-radius:12px;padding:6px"><img class="tek-pimg" src="<?php echo esc_url(wp_get_attachment_image_url($iid, 'woocommerce_thumbnail')); ?>" alt=""></button>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div style="flex:1 1 400px;min-width:0;display:flex;flex-direction:column;gap:20px">
<div style="display:flex;flex-direction:column;gap:10px">
<?php if ($deep) : ?><span style="align-self:flex-start;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8;background:#E6EEFC;border-radius:6px;padding:5px 9px"><?php echo esc_html(mb_strtoupper($deep->name)); ?></span><?php endif; ?>
<h1 style="margin:0;font-size:34px;line-height:1.15;font-weight:800;letter-spacing:-0.02em"><?php echo esc_html($p->get_name()); ?></h1>
<?php if ($code) : ?><span style="font-size:14px;color:#44506A">Код товара: <?php echo esc_html($code); ?></span><?php endif; ?>
</div>

<form method="post" action="<?php echo esc_url(get_permalink()); ?>" style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:24px;display:flex;flex-direction:column;gap:18px">
<div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap">
<span data-prc="<?php echo esc_attr($p->get_price()); ?>" style="font-family:'JetBrains Mono',monospace;font-size:40px;font-weight:600;letter-spacing:-0.02em"><?php echo esc_html(tek_price($p)); ?></span>
<span data-unit style="font-size:16px;color:#44506A">руб. ПМР</span>
</div>
<div style="font-size:14px;line-height:1.5;color:#33405A">Самовывоз в Тирасполе или доставка. Наличие подтверждаем при оформлении заказа. <a class="hv-link" href="<?php echo esc_url(home_url('/dostavka-i-oplata/')); ?>" style="font-weight:700">Подробнее о доставке</a></div>
<div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
<div style="display:flex;align-items:center;border:1px solid #C5D1E6;border-radius:12px;overflow:hidden;height:52px">
<button type="button" class="tek-qty-minus" aria-label="Уменьшить количество" style="width:44px;height:52px;border:0;background:#FFFFFF;color:#0F1B33;font-size:22px;font-weight:600">−</button>
<label for="qty" class="screen-reader-text">Количество</label>
<input id="qty" name="quantity" type="text" inputmode="numeric" value="1" style="width:48px;height:52px;border:0;border-left:1px solid #E6ECF6;border-right:1px solid #E6ECF6;text-align:center;font-family:'JetBrains Mono',monospace;font-size:17px;font-weight:600;color:#0F1B33;background:#FFFFFF">
<button type="button" class="tek-qty-plus" aria-label="Увеличить количество" style="width:44px;height:52px;border:0;background:#FFFFFF;color:#0F1B33;font-size:22px;font-weight:600">+</button>
</div>
<button class="hv-orange" type="submit" name="add-to-cart" value="<?php echo (int) $p->get_id(); ?>" style="flex:1 1 200px;display:flex;align-items:center;justify-content:center;height:52px;border:0;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-size:17px;font-weight:800">В корзину</button>
</div>
<a class="hv-outline" href="<?php echo esc_url(add_query_arg('add-to-cart', $p->get_id(), wc_get_checkout_url())); ?>" rel="nofollow" style="display:flex;align-items:center;justify-content:center;height:52px;border:2px solid #1D4ED8;border-radius:12px;background:#FFFFFF;color:#1D4ED8;font-size:16px;font-weight:700">Купить в 1 клик</a>
<div style="font-size:14px;line-height:1.5;color:#44506A">В 1 клик: сразу к оформлению, нужны только имя и телефон.</div>
</form>

<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:14px">
<div style="display:flex;flex-direction:column;gap:2px">
<span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8">САМОВЫВОЗ</span>
<span style="font-size:15px;line-height:1.45">ул. Ленина 7, ТК «Монарх», бутик №7. Пн–сб 10:00–19:00, вс 10:00–14:00.</span>
</div>
<div style="display:flex;flex-direction:column;gap:2px">
<span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8">ОПЛАТА</span>
<span style="font-size:15px;line-height:1.45">Наличными или безналичным расчётом.</span>
</div>
</div>

<div style="background:#0F2A5C;color:#FFFFFF;border-radius:16px;padding:18px 20px;display:flex;flex-wrap:wrap;gap:8px 16px;align-items:center;justify-content:space-between">
<span style="font-size:15px;font-weight:600">Вопрос по товару? Спросите у нас.</span>
<a href="tel:+37353351338" style="color:#FF8A1F;font-weight:800;font-size:17px">+373 533 51338</a>
</div>
</div>
</section>

<section style="max-width:1200px;margin:0 auto;padding:16px 24px 8px;display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
<?php $desc = trim(wp_strip_all_tags($p->get_description())); if ($desc !== '') : ?>
<div style="flex:1 1 480px;min-width:0;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:28px 32px;display:flex;flex-direction:column;gap:14px">
<h2 style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.01em">Описание</h2>
<div style="font-size:16px;line-height:1.65;color:#33405A"><?php echo wp_kses_post(wpautop($p->get_description())); ?></div>
</div>
<?php endif; ?>
<div style="flex:1 1 380px;min-width:0;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:28px 32px;display:flex;flex-direction:column;gap:14px">
<h2 style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.01em">Характеристики</h2>
<dl style="margin:0;display:flex;flex-direction:column">
<?php
$rows = [];
if ($deep) $rows[] = ['Раздел', $deep->name];
if ($code) $rows[] = ['Код товара', $code];
foreach ($p->get_attributes() as $a) { if (is_object($a) && !$a->is_taxonomy()) $rows[] = [$a->get_name(), implode(', ', $a->get_options())]; }
$rows[] = ['Наличие', 'уточняйте по телефону'];
foreach ($rows as $r) : ?>
<div style="display:flex;flex-wrap:wrap;gap:4px 16px;justify-content:space-between;padding:12px 0;border-bottom:1px solid #E6ECF6">
<dt style="font-size:15px;color:#44506A"><?php echo esc_html($r[0]); ?></dt>
<dd style="margin:0;font-size:15px;font-weight:600;text-align:right"><?php echo esc_html($r[1]); ?></dd>
</div>
<?php endforeach; ?>
</dl>
</div>
</section>

<?php if ($similar) : ?>
<section style="max-width:1200px;margin:0 auto;padding:48px 24px 72px">
<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:baseline;margin-bottom:24px">
<h2 style="margin:0;font-size:30px;font-weight:800;letter-spacing:-0.02em">Похожие товары</h2>
<?php if ($deep) : ?><a class="hv-link" href="<?php echo esc_url(get_term_link($deep)); ?>" style="font-weight:700;font-size:16px">Весь раздел →</a><?php endif; ?>
</div>
<div class="pgrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
<?php foreach ($similar as $s) : $img = get_the_post_thumbnail_url($s->get_id(), 'woocommerce_single'); ?>
<article style="display:flex;flex-direction:column;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;overflow:hidden">
<a href="<?php echo esc_url(get_permalink($s->get_id())); ?>" style="display:block;height:150px;background:#FFFFFF;border-bottom:1px solid #E6ECF6;padding:10px"><?php if ($img) : ?><img class="tek-pimg" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($s->get_name()); ?>" loading="lazy"><?php endif; ?></a>
<div style="padding:16px;display:flex;flex-direction:column;gap:10px;flex:1">
<span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8"><?php echo esc_html(tek_product_tag($s)); ?></span>
<h3 style="margin:0;font-size:15px;font-weight:600;line-height:1.4;flex:1"><a href="<?php echo esc_url(get_permalink($s->get_id())); ?>" style="color:inherit;text-decoration:none"><?php echo esc_html($s->get_name()); ?></a></h3>
<div style="display:flex;align-items:baseline;gap:6px"><span data-prc="<?php echo esc_attr($s->get_price()); ?>" style="font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:600"><?php echo esc_html(tek_price($s)); ?></span><span data-unit style="font-size:13px;color:#44506A">руб. ПМР</span></div>
<a class="hv-orange" href="<?php echo esc_url($s->add_to_cart_url()); ?>" rel="nofollow" style="display:flex;align-items:center;justify-content:center;height:44px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-size:15px;font-weight:700;text-decoration:none">В корзину</a>
</div>
</article>
<?php endforeach; ?>
</div>
</section>
<?php else : ?><div style="height:56px"></div><?php endif; ?>
</main>
<?php endwhile;
