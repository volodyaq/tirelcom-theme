<?php defined('ABSPATH') || exit; ?>
<div style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:24px">
<ol style="margin:0;padding:0;list-style:none;display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center;font-size:15px">
<li style="display:flex;align-items:center;gap:8px;color:#44506A"><span style="width:30px;height:30px;border-radius:999px;background:#0F2A5C;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">1</span>Корзина</li>
<li aria-hidden="true" style="width:32px;height:2px;background:#C5D1E6"></li>
<li style="display:flex;align-items:center;gap:8px;color:#44506A"><span style="width:30px;height:30px;border-radius:999px;background:#0F2A5C;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">2</span>Оформление</li>
<li aria-hidden="true" style="width:32px;height:2px;background:#C5D1E6"></li>
<li style="display:flex;align-items:center;gap:8px;font-weight:700"><span style="width:30px;height:30px;border-radius:999px;background:#1D4ED8;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600">3</span>Заказ принят</li>
</ol>
<section style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:20px;padding:40px 32px;display:flex;flex-direction:column;gap:18px;align-items:flex-start">
<span style="width:64px;height:64px;border-radius:999px;background:#E6EEFC;display:flex;align-items:center;justify-content:center"><svg aria-hidden="true" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Заказ принят</h1>
<?php if ($order && !$order->has_status('failed')) : ?>
<span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600;color:#44506A">Заказ № <?php echo esc_html($order->get_order_number()); ?></span>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:560px">Мы позвоним по указанному номеру и подтвердим наличие товаров. Самовывоз: ул. Ленина 7, ТК «Монарх», бутик №7. Для доставки согласуем детали по телефону.</p>
<?php else : ?>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:560px">Не удалось оформить заказ. Позвоните нам, и мы оформим его вместе.</p>
<?php endif; ?>
<div style="display:flex;flex-wrap:wrap;gap:12px"><a class="hv-blue" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" style="display:inline-flex;align-items:center;min-height:52px;padding:0 26px;border-radius:12px;background:#1D4ED8;color:#FFFFFF;font-weight:700;font-size:16px">Вернуться в каталог</a></div>
<div style="font-size:15px;color:#44506A">Вопросы по заказу: <a class="hv-link" href="tel:+37353351338" style="font-weight:700">+373 533 51338</a></div>
</section>
</div>
