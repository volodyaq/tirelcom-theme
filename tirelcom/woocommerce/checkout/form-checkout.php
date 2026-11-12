<?php
defined('ABSPATH') || exit;
do_action('woocommerce_before_checkout_form', $checkout);
$inp = 'height:52px;border:1px solid #C5D1E6;border-radius:12px;padding:0 16px;font-family:inherit;font-size:16px;color:#0F1B33;background:#FFFFFF;width:100%';
$opts = [
    'pickup' => ['Самовывоз в Тирасполе', 'ул. Ленина 7, ТК «Монарх», бутик №5', 'Пн–сб 10:00–19:00, вс 10:00–14:00, бесплатно'],
    'courier' => ['Курьер по Тирасполю', 'Заказ от 300 руб.', 'Стоимость и время согласуем по телефону'],
    'other' => ['Другой город или страна', 'Почта ПМР и Молдовы, Нова Пошта, Ин Тайм', 'Условия и стоимость согласуем по телефону'],
];
$cur = isset($_POST['tek_delivery']) ? sanitize_text_field($_POST['tek_delivery']) : 'pickup';
?>
<div style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:24px">
<ol style="margin:0;padding:0;list-style:none;display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center;font-size:15px">
<li style="display:flex;align-items:center;gap:8px;color:#44506A"><span style="width:30px;height:30px;border-radius:999px;background:#0F2A5C;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">1</span>Корзина</li>
<li aria-hidden="true" style="width:32px;height:2px;background:#C5D1E6"></li>
<li style="display:flex;align-items:center;gap:8px;font-weight:700"><span style="width:30px;height:30px;border-radius:999px;background:#1D4ED8;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">2</span>Оформление</li>
<li aria-hidden="true" style="width:32px;height:2px;background:#C5D1E6"></li>
<li style="display:flex;align-items:center;gap:8px;color:#44506A"><span style="width:30px;height:30px;border-radius:999px;background:#E6EEFC;color:#44506A;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">3</span>Заказ принят</li>
</ol>
<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" novalidate="novalidate" style="display:flex;flex-wrap:wrap;gap:28px;align-items:flex-start">
<div style="flex:1 1 600px;min-width:0;display:flex;flex-direction:column;gap:20px">
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Оформление заказа</h1>

<section style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:24px 28px;display:flex;flex-direction:column;gap:16px">
<h2 style="margin:0;font-size:20px;font-weight:800">1. Ваши контакты</h2>
<div style="display:flex;flex-wrap:wrap;gap:16px">
<div style="flex:1 1 240px;display:flex;flex-direction:column;gap:6px">
<label for="billing_first_name" style="font-size:14px;font-weight:600">Имя</label>
<input id="billing_first_name" name="billing_first_name" type="text" autocomplete="name" placeholder="Как к вам обращаться" value="<?php echo esc_attr($checkout->get_value('billing_first_name')); ?>" style="<?php echo $inp; ?>">
</div>
<div style="flex:1 1 240px;display:flex;flex-direction:column;gap:6px">
<label for="billing_phone" style="font-size:14px;font-weight:600">Телефон *</label>
<input id="billing_phone" name="billing_phone" type="tel" autocomplete="tel" placeholder="+373 …" value="<?php echo esc_attr($checkout->get_value('billing_phone')); ?>" style="<?php echo $inp; ?>">
</div>
</div>
<div style="font-size:14px;color:#44506A">Позвоним по этому номеру, чтобы подтвердить наличие и время получения.</div>
</section>

<section style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:24px 28px;display:flex;flex-direction:column;gap:16px">
<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;gap:6px 16px"><h2 style="margin:0;font-size:20px;font-weight:800">2. Получение заказа</h2><a class="hv-link" href="<?php echo esc_url(home_url('/dostavka-i-oplata/')); ?>" target="_blank" style="font-size:15px;font-weight:700">Условия доставки</a></div>
<div style="display:flex;flex-wrap:wrap;gap:12px">
<?php foreach ($opts as $k => $o) : ?>
<label class="opt" style="flex:1 1 260px;min-width:0;display:flex;gap:12px;align-items:flex-start;border:2px solid #D5DEEE;background:#FFFFFF;border-radius:16px;padding:16px;cursor:pointer">
<input type="radio" name="tek_delivery" value="<?php echo esc_attr($k); ?>" <?php checked($cur, $k); ?> style="width:20px;height:20px;margin-top:2px;accent-color:#1D4ED8">
<span style="display:flex;flex-direction:column;gap:4px"><span style="font-size:16px;font-weight:700"><?php echo esc_html($o[0]); ?></span><span style="font-size:14px;line-height:1.45;color:#33405A"><?php echo esc_html($o[1]); ?></span><span style="font-size:14px;line-height:1.45;color:#33405A"><?php echo esc_html($o[2]); ?></span></span>
</label>
<?php endforeach; ?>
</div>
<div id="tek-addr" style="display:none;flex-direction:column;gap:6px">
<label for="tek_address" style="font-size:14px;font-weight:600">Адрес или город доставки</label>
<input id="tek_address" name="tek_address" type="text" placeholder="Улица, дом или город и способ доставки" style="<?php echo $inp; ?>">
</div>
<div style="display:flex;flex-direction:column;gap:6px">
<label for="order_comments" style="font-size:14px;font-weight:600">Комментарий к заказу <span style="font-weight:400;color:#44506A">(необязательно)</span></label>
<textarea id="order_comments" name="order_comments" rows="3" placeholder="Например: удобное время или вопрос по товару" style="border:1px solid #C5D1E6;border-radius:12px;padding:14px 16px;font-family:inherit;font-size:16px;color:#0F1B33;background:#FFFFFF;width:100%;resize:vertical"></textarea>
</div>
</section>

<section style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:24px 28px;display:flex;flex-direction:column;gap:10px">
<h2 style="margin:0;font-size:20px;font-weight:800">3. Оплата</h2>
<div style="font-size:16px;line-height:1.5">Наличными или безналичным расчётом при получении заказа.</div>
</section>

<div style="display:flex;flex-direction:column;gap:12px">
<input type="hidden" name="payment_method" value="cod">
<?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
<button class="hv-orange" type="submit" id="place_order" name="woocommerce_checkout_place_order" value="Подтвердить заказ" style="height:60px;border:0;border-radius:14px;background:#FF8A1F;color:#0F1B33;font-size:18px;font-weight:800">Подтвердить заказ</button>
<div style="font-size:13px;line-height:1.5;color:#44506A">Нажимая кнопку, вы соглашаетесь с обработкой данных для выполнения заказа.</div>
</div>
</div>

<aside style="flex:1 1 340px;max-width:440px;min-width:0;background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:24px;display:flex;flex-direction:column;gap:14px">
<h2 style="margin:0;font-size:20px;font-weight:800">Ваш заказ</h2>
<ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:12px">
<?php foreach (WC()->cart->get_cart() as $item) : $p = $item['data']; ?>
<li style="display:flex;justify-content:space-between;gap:16px;font-size:14px;line-height:1.45">
<span style="min-width:0"><?php echo esc_html($p->get_name()); ?> <span style="color:#44506A">× <?php echo (int) $item['quantity']; ?></span></span>
<span style="font-family:'JetBrains Mono',monospace;font-weight:600;white-space:nowrap"><?php echo esc_html(number_format((float) $p->get_price() * (int) $item['quantity'], 2, ',', ' ')); ?></span>
</li>
<?php endforeach; ?>
</ul>
<div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding-top:12px;border-top:1px solid #E6ECF6">
<span style="font-size:16px;font-weight:700">Итого</span>
<span><span style="font-family:'JetBrains Mono',monospace;font-size:28px;font-weight:600"><?php echo esc_html(number_format((float) WC()->cart->get_subtotal(), 2, ',', ' ')); ?></span> <span style="font-size:14px;color:#44506A">руб. ПМР</span></span>
</div>
<a class="hv-link" href="<?php echo esc_url(wc_get_cart_url()); ?>" style="font-weight:700;font-size:15px">← Изменить корзину</a>
</aside>
</form>
</div>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
