<?php if (!defined('ABSPATH')) exit; get_header();
function tek_card($tag, $title, $lines) { ?>
<div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:8px">
<span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;letter-spacing:0.06em;color:#1D4ED8"><?php echo esc_html($tag); ?></span>
<h3 style="margin:0;font-size:18px;font-weight:700;line-height:1.3"><?php echo esc_html($title); ?></h3>
<?php foreach ($lines as $l) : ?><p style="margin:0;font-size:15px;line-height:1.5;color:#33405A"><?php echo esc_html($l); ?></p><?php endforeach; ?>
</div>
<?php }
function tek_group($h, $cards) { ?>
<section style="display:flex;flex-direction:column;gap:14px"><h2 style="margin:0;font-size:24px;font-weight:800"><?php echo esc_html($h); ?></h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px"><?php foreach ($cards as $c) tek_card($c[0], $c[1], $c[2]); ?></div></section>
<?php }
$agree = 'Стоимость и условия согласуем при оформлении заказа.'; ?>
<main style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:36px">
<div style="display:flex;flex-direction:column;gap:12px">
<nav style="font-size:14px;color:#44506A"><a class="hv-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / Доставка и оплата</nav>
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Доставка и оплата</h1>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:760px">Заказать можно на сайте или по телефону. Заберите заказ в магазине или выберите доставку: по Тирасполю, Приднестровью, Молдове, Украине и в другие страны.</p>
</div>
<?php
tek_group('Приднестровье', [
 ['САМОВЫВОЗ','Тирасполь, из магазина',['ул. Ленина 7, ТК «Монарх», бутик №5','Пн–сб 10:00–19:00, вс 10:00–14:00','Бесплатно']],
 ['КУРЬЕР','По Тирасполю',['Минимальная сумма заказа 300 руб.','Стоимость и сроки согласуем при оформлении заказа.']],
 ['САМОВЫВОЗ','Рыбница',[$agree]],
 ['ПОЧТА','Почта ПМР',['Стоимость доставки согласуем при оформлении заказа.']],
]);
tek_group('Молдова', [
 ['САМОВЫВОЗ','Кишинёв, рынок «Птичка»',['Piata de Pasari, str. Izmail 3, стол №111 (Валера)','Поставка по четвергам с 10 до 11 часов.','Валера работает каждый день.']],
 ['САМОВЫВОЗ','Бельцы',[$agree]],
 ['ПОЧТА','Почта Молдовы',['Стоимость доставки от 35 лей.','Заказы отправляем по четвергам с 10 до 14 часов.']],
]);
tek_group('Украина', [
 ['ПЕРЕВОЗЧИК','«Нова Пошта»',['Доставка по Украине.','Условия и оплату согласуем при оформлении заказа.']],
 ['ПЕРЕВОЗЧИК','«Ин Тайм»',['Доставка по Украине.','Условия и оплату согласуем при оформлении заказа.']],
]);
tek_group('Европа и весь мир', [
 ['ИНДИВИДУАЛЬНО','Страны Европейского союза',['Оплата и доставка оговариваются индивидуально при оформлении заказа.']],
 ['ИНДИВИДУАЛЬНО','Остальные страны',['Оплата и доставка оговариваются индивидуально при оформлении заказа.']],
]);
tek_group('Оплата', [
 ['ОПЛАТА','Наличными, картой или переводом',['Оплата при получении заказа: наличными, картой через терминал или переводом на карту. На сайте онлайн-оплаты нет. Для других городов и стран способ оплаты согласуем при оформлении заказа.']],
]);
?>
<section style="background:#0F2A5C;color:#FFFFFF;border-radius:20px;padding:28px;display:flex;flex-wrap:wrap;gap:16px 24px;align-items:center;justify-content:space-between">
<div style="display:flex;flex-direction:column;gap:6px"><h2 style="margin:0;font-size:24px;font-weight:800">Остались вопросы?</h2><span style="font-size:16px;color:#DCE6F8">Позвоните на +373 533 51338 или напишите в мессенджер.</span></div>
<div style="display:flex;flex-wrap:wrap;gap:12px"><a class="hv-orange" href="tel:+37353351338" style="display:inline-flex;align-items:center;min-height:52px;padding:0 26px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-weight:800;font-size:16px">Позвонить</a><a class="hv-blue" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" style="display:inline-flex;align-items:center;min-height:52px;padding:0 26px;border-radius:12px;background:#1D4ED8;color:#FFFFFF;font-weight:700;font-size:16px">В каталог</a></div>
</section>
</main>
<?php get_footer();
