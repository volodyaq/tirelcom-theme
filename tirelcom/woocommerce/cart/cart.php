<?php
defined('ABSPATH') || exit;
$count = WC()->cart->get_cart_contents_count();
?>
<div class="tek-cart" style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:24px">
<ol style="margin:0;padding:0;list-style:none;display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center;font-size:15px">
<li style="display:flex;align-items:center;gap:8px;font-weight:700"><span style="width:30px;height:30px;border-radius:999px;background:#1D4ED8;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">1</span>Корзина</li>
<li aria-hidden="true" style="width:32px;height:2px;background:#C5D1E6"></li>
<li style="display:flex;align-items:center;gap:8px;color:#44506A"><span style="width:30px;height:30px;border-radius:999px;background:#E6EEFC;color:#44506A;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">2</span>Оформление</li>
<li aria-hidden="true" style="width:32px;height:2px;background:#C5D1E6"></li>
<li style="display:flex;align-items:center;gap:8px;color:#44506A"><span style="width:30px;height:30px;border-radius:999px;background:#E6EEFC;color:#44506A;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">3</span>Заказ принят</li>
</ol>
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Корзина</h1>
<?php do_action('woocommerce_before_cart'); ?>
<div style="display:flex;flex-wrap:wrap;gap:28px;align-items:flex-start">

<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post" style="flex:1 1 600px;min-width:0;display:flex;flex-direction:column;gap:14px">
<?php do_action('woocommerce_before_cart_table'); ?>
<?php foreach (WC()->cart->get_cart() as $key => $item) :
    $p = apply_filters('woocommerce_cart_item_product', $item['data'], $item, $key);
    if (!$p || !$p->exists() || $item['quantity'] <= 0) continue;
    $permalink = $p->is_visible() ? $p->get_permalink($item) : '';
    $img = wp_get_attachment_image_url($p->get_image_id(), 'woocommerce_thumbnail');
    $sum = (float) $p->get_price() * (int) $item['quantity']; ?>
<article style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:16px;display:flex;flex-wrap:wrap;gap:16px 20px;align-items:center">
<a href="<?php echo esc_url($permalink); ?>" style="flex:none;width:88px;height:88px;border-radius:12px;background:#FFFFFF;border:1px solid #E6ECF6;padding:4px;display:block"><?php if ($img) : ?><img class="tek-pimg" src="<?php echo esc_url($img); ?>" alt=""><?php endif; ?></a>
<div style="flex:1 1 160px;min-width:0;display:flex;flex-direction:column;gap:6px">
<span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8"><?php echo esc_html(tek_product_tag($p)); ?></span>
<h2 style="margin:0;font-size:16px;font-weight:600;line-height:1.4"><a href="<?php echo esc_url($permalink); ?>" style="color:inherit;text-decoration:none"><?php echo esc_html($p->get_name()); ?></a></h2>
<span style="font-size:14px;color:#44506A"><span data-prc="<?php echo esc_attr($p->get_price()); ?>"><?php echo esc_html(tek_price($p)); ?></span> <span data-unit>руб. ПМР</span> за шт.</span>
</div>
<div style="display:flex;align-items:center;border:1px solid #C5D1E6;border-radius:12px;overflow:hidden;height:44px">
<button type="button" class="tek-cq-minus" aria-label="Уменьшить количество" style="width:40px;height:44px;border:0;background:#FFFFFF;color:#0F1B33;font-size:20px;font-weight:600">−</button>
<input class="tek-cq" name="cart[<?php echo esc_attr($key); ?>][qty]" type="text" inputmode="numeric" value="<?php echo (int) $item['quantity']; ?>" aria-label="Количество" style="width:48px;height:44px;border:0;border-left:1px solid #E6ECF6;border-right:1px solid #E6ECF6;text-align:center;font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:600;color:#0F1B33;background:#FFFFFF">
<button type="button" class="tek-cq-plus" aria-label="Увеличить количество" style="width:40px;height:44px;border:0;background:#FFFFFF;color:#0F1B33;font-size:20px;font-weight:600">+</button>
</div>
<div style="flex:0 0 120px;text-align:right;display:flex;flex-direction:column;gap:6px;align-items:flex-end">
<span style="font-family:'JetBrains Mono',monospace;font-size:20px;font-weight:600"><span data-prc="<?php echo esc_attr($sum); ?>"><?php echo esc_html(number_format($sum, 2, ',', ' ')); ?></span></span>
<a href="<?php echo esc_url(wc_get_cart_remove_url($key)); ?>" style="color:#B42318;font-size:14px;font-weight:600;padding:4px 0">Удалить</a>
</div>
</article>
<?php endforeach; ?>
<button type="submit" class="screen-reader-text" name="update_cart" value="Обновить корзину">Обновить корзину</button>
<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
<a class="hv-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" style="align-self:flex-start;font-weight:700;font-size:16px;padding:8px 0">+ Добавить ещё товары</a>
<?php do_action('woocommerce_after_cart_table'); ?>
</form>

<aside style="flex:1 1 340px;max-width:440px;min-width:0;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:24px;display:flex;flex-direction:column;gap:16px">
<h2 style="margin:0;font-size:20px;font-weight:800">Ваш заказ</h2>
<div style="display:flex;justify-content:space-between;gap:12px;font-size:15px"><span style="color:#44506A">Товары, <?php echo (int) $count; ?> шт.</span><span style="font-family:'JetBrains Mono',monospace;font-weight:600"><span data-prc="<?php echo esc_attr(WC()->cart->get_subtotal()); ?>"><?php echo esc_html(number_format((float) WC()->cart->get_subtotal(), 2, ',', ' ')); ?></span></span></div>
<div style="display:flex;justify-content:space-between;gap:12px;font-size:15px"><span style="color:#44506A">Получение</span><span style="text-align:right">самовывоз бесплатно,<br>доставка по договорённости</span></div>
<div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding-top:16px;border-top:1px solid #E6ECF6">
<span style="font-size:16px;font-weight:700">Итого</span>
<span><span style="font-family:'JetBrains Mono',monospace;font-size:28px;font-weight:600"><span data-prc="<?php echo esc_attr(WC()->cart->get_subtotal()); ?>"><?php echo esc_html(number_format((float) WC()->cart->get_subtotal(), 2, ',', ' ')); ?></span></span> <span data-unit style="font-size:14px;color:#44506A">руб. ПМР</span></span>
</div>
<a class="hv-orange" href="<?php echo esc_url(wc_get_checkout_url()); ?>" style="display:flex;align-items:center;justify-content:center;height:56px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-size:17px;font-weight:800;text-decoration:none">Оформить заказ</a>
<div style="font-size:14px;line-height:1.5;color:#44506A">Регистрация не нужна. <a class="hv-link" href="<?php echo esc_url(home_url('/dostavka-i-oplata/')); ?>" style="font-weight:700">Условия доставки</a>. После заказа мы позвоним и подтвердим наличие.</div>
<div style="display:flex;flex-direction:column;gap:6px;padding-top:12px;border-top:1px solid #E6ECF6;font-size:14px;line-height:1.45">
<span><strong style="font-weight:700">Самовывоз:</strong> ул. Ленина 7, ТК «Монарх», бутик №5</span>
<span><strong style="font-weight:700">Вопросы:</strong> <a class="hv-link" href="tel:+37353351338" style="font-weight:700">+373 533 51338</a></span>
</div>
</aside>
</div>
</div>
