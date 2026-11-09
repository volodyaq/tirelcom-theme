<?php if (!defined('ABSPATH')) exit; $cats = tek_top_cats(); ?>
<footer style="background:#0F2A5C;color:#C9D6F2">
<div style="max-width:1200px;margin:0 auto;padding:48px 24px 32px;display:flex;flex-wrap:wrap;gap:32px 48px;justify-content:space-between">
<div style="flex:1 1 260px;display:flex;flex-direction:column;gap:10px">
<span style="font-family:'Manrope',system-ui,sans-serif;font-weight:800;font-size:28px;color:#FFFFFF;letter-spacing:-0.01em">Тир<span style="color:#FF8A1F">Эл</span>Ком</span>
<span style="font-size:15px;line-height:1.5">Радиодетали, электроника и инструмент в Тирасполе.</span>
</div>
<div style="display:flex;flex-direction:column;gap:8px;font-size:15px">
<span style="color:#FFFFFF;font-weight:600;margin-bottom:4px">Покупателям</span>
<a class="hv-foot" href="<?php echo esc_url(home_url('/dostavka-i-oplata/')); ?>" style="color:#C9D6F2">Доставка и оплата</a>
<a class="hv-foot" href="<?php echo esc_url(home_url('/o-magazine/')); ?>" style="color:#C9D6F2">О магазине и контакты</a>
</div>
<div style="display:flex;flex-direction:column;gap:8px;font-size:15px">
<span style="color:#FFFFFF;font-weight:600;margin-bottom:4px">Каталог</span>
<?php foreach (array_slice($cats, 0, 4) as $c) : ?>
<a class="hv-foot" href="<?php echo esc_url(get_term_link($c)); ?>" style="color:#C9D6F2"><?php echo esc_html($c->name); ?></a>
<?php endforeach; ?>
</div>
<div style="display:flex;flex-direction:column;gap:8px;font-size:15px">
<span style="color:#FFFFFF;font-weight:600;margin-bottom:4px">Контакты</span>
<a class="hv-foot" href="tel:+37353351338" style="color:#C9D6F2">+373 533 51338</a>
<?php get_template_part('messengers'); ?>
</div>
</div>
<div style="border-top:1px solid #2D4A85">
<div style="max-width:1200px;margin:0 auto;padding:16px 24px;display:flex;flex-wrap:wrap;gap:8px 24px;justify-content:space-between;font-size:13px">
<span>© <?php echo date('Y'); ?> ТирЭлКом</span>
</div>
</div>
</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
