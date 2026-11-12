<?php if (!defined('ABSPATH')) exit; get_header();
$cats = tek_top_cats();
$prods = tek_home_products(7);
$shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$chip = function ($label, $q) { return '<a class="hv-chip" href="' . esc_url(add_query_arg(['s' => $q, 'post_type' => 'product'], home_url('/'))) . '" style="padding:6px 12px;border:1px solid #C5D1E6;border-radius:999px;background:#FFFFFF;color:#0F1B33;font-size:14px">' . esc_html($label) . '</a>'; };
$ribbon_item = function ($p, $hidden = false) {
    $img = get_the_post_thumbnail_url($p->get_id(), 'woocommerce_thumbnail');
    ob_start(); ?>
<a class="hv-card" href="<?php echo esc_url(get_permalink($p->get_id())); ?>"<?php echo $hidden ? ' tabindex="-1" aria-hidden="true"' : ''; ?> style="display:flex;gap:14px;align-items:center;margin-bottom:12px;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:14px;padding:12px;color:#0F1B33;text-decoration:none">
<span style="flex:none;width:72px;height:72px;border-radius:10px;background:#E9EFF8;overflow:hidden;display:block"><?php if ($img) : ?><img class="tek-pimg" src="<?php echo esc_url($img); ?>" alt="" loading="lazy"><?php endif; ?></span>
<span style="min-width:0;display:flex;flex-direction:column;gap:4px">
<span style="font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8"><?php echo esc_html(tek_product_tag($p)); ?></span>
<span style="font-size:14px;font-weight:600;line-height:1.35"><?php echo esc_html($p->get_name()); ?></span>
<span style="font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:600"><span data-prc="<?php echo esc_attr($p->get_price()); ?>"><?php echo esc_html(tek_price($p)); ?></span> <span data-unit style="font-size:12px;font-weight:400;color:#44506A">руб. ПМР</span></span>
</span>
</a>
<?php return ob_get_clean(); };
?>
<main id="top">

<section style="background:#FFFFFF;border-bottom:1px solid #D5DEEE;background-image:radial-gradient(#DCE4F2 1px, transparent 1px);background-size:20px 20px">
<div class="hero-pad" style="max-width:1200px;margin:0 auto;padding:64px 24px;display:flex;flex-wrap:wrap;gap:40px;align-items:center">
<div style="flex:1 1 480px;min-width:0;display:flex;flex-direction:column;gap:24px">
<span style="align-self:flex-start;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;letter-spacing:0.08em;color:#1D4ED8;background:#E6EEFC;border-radius:6px;padding:6px 10px">МАГАЗИН + ИНТЕРНЕТ-ЗАКАЗ</span>
<h1 class="hero-h1" style="margin:0;font-size:48px;line-height:1.1;font-weight:800;letter-spacing:-0.02em">Радиодетали и инструмент: заказывайте онлайн, забирайте в магазине</h1>
<p style="margin:0;font-size:18px;line-height:1.55;color:#33405A;max-width:560px">Более 25 лет работаем в Тирасполе. Микросхемы, транзисторы, Arduino, паяльное оборудование и всё для ремонта электроники. Не нашли нужную деталь — пришлите маркировку, подберём аналог.</p>
<div style="display:flex;flex-wrap:wrap;gap:12px">
<a class="hv-blue" href="<?php echo esc_url($shop); ?>" style="display:inline-flex;align-items:center;min-height:52px;padding:0 28px;border-radius:12px;background:#1D4ED8;color:#FFFFFF;font-weight:600;font-size:17px">Открыть каталог</a>
<a class="hv-outline" href="tel:+37353351338" style="display:inline-flex;align-items:center;min-height:52px;padding:0 28px;border-radius:12px;border:2px solid #1D4ED8;color:#1D4ED8;font-weight:600;font-size:17px">Позвонить: +373 533 51338</a>
</div>
<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
<span style="font-size:14px;color:#44506A">Часто ищут:</span>
<?php echo $chip('Arduino', 'Arduino') . $chip('ATmega', 'ATmega') . $chip('DC/DC модули', 'DC-DC') . $chip('Припой и флюс', 'припой') . $chip('Паяльники', 'паяльник'); ?>
</div>
</div>
<?php if ($prods) : ?>
<div class="ribbon" aria-label="Лента товаров" style="flex:1 1 340px;min-width:0;height:460px;position:relative;overflow:hidden;border-radius:18px;background:#E6EEFC;border:1px solid #D5DEEE;padding:14px;-webkit-mask-image:linear-gradient(transparent,#000 8%,#000 92%,transparent);mask-image:linear-gradient(transparent,#000 8%,#000 92%,transparent)">
<div class="ribbon-track">
<?php foreach ($prods as $p) echo $ribbon_item($p); foreach ($prods as $p) echo $ribbon_item($p, true); ?>
</div>
</div>
<?php endif; ?>
</div>
</section>

<section style="max-width:1200px;margin:0 auto;padding:40px 24px 8px">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:6px">
<span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:#1D4ED8;letter-spacing:0.06em">САМОВЫВОЗ</span>
<span style="font-size:16px;line-height:1.45">ТК «Монарх», бутик №5, ул. Ленина 7</span>
</div>
<a class="hv-card" href="<?php echo esc_url(home_url('/dostavka-i-oplata/')); ?>" style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:6px;color:#0F1B33;text-decoration:none"><span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:#1D4ED8;letter-spacing:0.06em">ДОСТАВКА</span><span style="font-size:16px;line-height:1.45">По Тирасполю, ПМР, Молдове и Украине. Подробнее →</span></a>
<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:6px">
<span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:#1D4ED8;letter-spacing:0.06em">ОПЛАТА</span>
<span style="font-size:16px;line-height:1.45">Наличный и безналичный расчёт</span>
</div>
<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:6px">
<span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:#1D4ED8;letter-spacing:0.06em">ПОДБОР</span>
<span style="font-size:16px;line-height:1.45">Подскажем аналог по телефону</span>
</div>
</div>
</section>

<section id="catalog" style="max-width:1200px;margin:0 auto;padding:56px 24px 16px">
<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:baseline;margin-bottom:24px">
<h2 style="margin:0;font-size:34px;font-weight:800;letter-spacing:-0.02em">Выберите раздел</h2>
<a class="hv-link" href="<?php echo esc_url($shop); ?>" style="font-weight:600;font-size:16px">Весь каталог →</a>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px">
<?php foreach ($cats as $i => $c) : ?>
<a class="hv-card" href="<?php echo esc_url(get_term_link($c)); ?>" style="display:flex;flex-direction:column;gap:10px;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:24px;color:#0F1B33;text-decoration:none;min-height:176px">
<span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600;color:#C2410C"><?php echo sprintf('%02d', $i + 1); ?></span>
<span style="font-size:22px;font-weight:600;line-height:1.25"><?php echo esc_html($c->name); ?></span>
<span style="font-size:15px;line-height:1.5;color:#44506A"><?php echo esc_html(tek_cat_text($c->name)); ?></span>
</a>
<?php endforeach; ?>
</div>
</section>

<?php if ($prods) : ?>
<section style="max-width:1200px;margin:0 auto;padding:56px 24px 16px">
<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:baseline;margin-bottom:24px">
<h2 style="margin:0;font-size:34px;font-weight:800;letter-spacing:-0.02em">Из нашего ассортимента</h2>
<a class="hv-link" href="<?php echo esc_url($shop); ?>" style="font-weight:600;font-size:16px">Все товары →</a>
</div>
<div class="pgrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px">
<?php foreach ($prods as $p) : $img = get_the_post_thumbnail_url($p->get_id(), 'woocommerce_single'); ?>
<article style="display:flex;flex-direction:column;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;overflow:hidden">
<a href="<?php echo esc_url(get_permalink($p->get_id())); ?>" style="display:block;height:170px;background:#FFFFFF;border-bottom:1px solid #E6ECF6;padding:10px"><?php if ($img) : ?><img class="tek-pimg" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p->get_name()); ?>" loading="lazy"><?php endif; ?></a>
<div style="padding:18px;display:flex;flex-direction:column;gap:10px;flex:1">
<span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8"><?php echo esc_html(tek_product_tag($p)); ?></span>
<h3 style="margin:0;font-size:16px;font-weight:600;line-height:1.4;flex:1"><a href="<?php echo esc_url(get_permalink($p->get_id())); ?>" style="color:inherit;text-decoration:none"><?php echo esc_html($p->get_name()); ?></a></h3>
<div style="display:flex;align-items:baseline;gap:6px">
<span data-prc="<?php echo esc_attr($p->get_price()); ?>" style="font-family:'JetBrains Mono',monospace;font-size:22px;font-weight:600"><?php echo esc_html(tek_price($p)); ?></span>
<span data-unit style="font-size:14px;color:#44506A">руб. ПМР</span>
</div>
<div style="display:flex;gap:8px"><a class="hv-orange" href="<?php echo esc_url($p->add_to_cart_url()); ?>" data-product_id="<?php echo (int) $p->get_id(); ?>" rel="nofollow" style="flex:1;display:flex;align-items:center;justify-content:center;height:44px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-size:15px;font-weight:600;text-decoration:none">В корзину</a><?php echo tek_cmp_btn($p->get_id()); ?></div>
</div>
</article>
<?php endforeach; ?>
<a href="<?php echo esc_url($shop); ?>" style="display:flex;flex-direction:column;justify-content:center;align-items:flex-start;gap:10px;background:#E6EEFC;border:1px dashed #1D4ED8;border-radius:16px;padding:24px;min-height:200px;color:#0F2A5C;text-decoration:none">
<span style="font-size:22px;font-weight:600;line-height:1.25">Весь каталог</span>
<span style="font-size:15px;line-height:1.5">Тысячи позиций: от резисторов до измерительных приборов.</span>
<span style="font-weight:600">Перейти →</span>
</a>
</div>
</section>
<?php endif; ?>

<section style="max-width:1200px;margin:0 auto;padding:56px 24px 16px">
<div class="pad-card" style="background:#0F2A5C;color:#FFFFFF;border-radius:20px;padding:44px 40px;display:flex;flex-wrap:wrap;gap:28px 40px;align-items:center;justify-content:space-between">
<div style="flex:1 1 420px;min-width:0;display:flex;flex-direction:column;gap:12px">
<h2 style="margin:0;font-size:32px;font-weight:700;line-height:1.2">Не нашли нужную деталь?</h2>
<p style="margin:0;font-size:17px;line-height:1.55;color:#C9D6F2;max-width:560px">Пришлите маркировку или фото платы. Проверим наличие, предложим аналог или привезём под заказ.</p>
</div>
<div style="display:flex;flex-wrap:wrap;gap:12px">
<a class="hv-orange" href="tel:+37353351338" style="display:inline-flex;align-items:center;min-height:52px;padding:0 26px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-weight:600;font-size:17px">Позвонить</a>
<?php get_template_part('messengers'); ?>
</div>
</div>
</section>

<section style="max-width:1200px;margin:0 auto;padding:56px 24px 72px">
<h2 style="margin:0 0 24px;font-size:34px;font-weight:800;letter-spacing:-0.02em">Магазин в Тирасполе</h2>
<div style="display:flex;flex-wrap:wrap;gap:24px">
<div style="flex:1 1 360px;min-width:0;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:28px;display:flex;flex-direction:column;gap:18px">
<div><div style="font-size:14px;color:#44506A">Адрес</div><div style="font-size:18px;font-weight:600">ул. Ленина 7, ТК «Монарх», бутик №5</div></div>
<div><div style="font-size:14px;color:#44506A">Часы работы</div><div style="font-size:18px;font-weight:600;line-height:1.5">Пн–сб: 10:00–19:00<br>Вс: 10:00–14:00</div></div>
<div><div style="font-size:14px;color:#44506A">Телефон</div><a href="tel:+37353351338" style="font-size:18px;font-weight:600;color:#0F1B33">+373 533 51338</a></div>
<div style="font-size:15px;line-height:1.5;color:#33405A">Заказ, оформленный на сайте, будет собран к вашему приходу.</div>
</div>
<div style="flex:1 1 420px;min-width:0;min-height:300px;border-radius:16px;border:1px solid #D5DEEE;overflow:hidden;background:#E9EFF8">
<iframe title="Карта: Тирасполь, ул. Ленина 7" src="https://www.google.com/maps?q=%D0%A2%D0%B8%D1%80%D0%B0%D1%81%D0%BF%D0%BE%D0%BB%D1%8C%2C+%D1%83%D0%BB.+%D0%9B%D0%B5%D0%BD%D0%B8%D0%BD%D0%B0+7&output=embed" width="100%" height="100%" style="border:0;min-height:300px;display:block" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</div>
</div>
</section>

</main>
<?php get_footer();
