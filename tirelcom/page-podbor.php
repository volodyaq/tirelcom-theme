<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:24px">
<div style="display:flex;flex-direction:column;gap:12px">
<nav style="font-size:14px;color:#44506A"><a class="hv-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / Подбор детали</nav>
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Подберите деталь</h1>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:760px">Не нашли нужное на сайте или не уверены, что подойдёт? Напишите маркировку, опишите деталь или приложите фото платы. Проверим наличие, предложим аналог или привезём под заказ. Ответим в рабочее время: пн–сб 10–19, вс 10–14.</p>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;align-items:start">
<?php echo tek_req_form('podbor'); ?>
<aside style="background:#0F2A5C;color:#FFFFFF;border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:12px">
<span style="font-size:20px;font-weight:800">Быстрее по телефону</span>
<span style="font-size:15px;line-height:1.55;color:#C9D6F2">Позвоните, и подскажем сразу. Фото маркировки можно прислать в мессенджер.</span>
<a class="hv-link" href="tel:+37353351338" style="color:#FF8A1F;font-weight:800;font-size:20px">+373 533 51338</a>
<?php get_template_part('messengers'); ?>
</aside>
</div>
</main>
<?php get_footer();
