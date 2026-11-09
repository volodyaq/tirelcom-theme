<?php defined('ABSPATH') || exit; ?>
<div style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:20px">
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Корзина пуста</h1>
<?php do_action('woocommerce_cart_is_empty'); ?>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A">Добавьте товары из каталога, и они появятся здесь.</p>
<div><a class="hv-blue" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" style="display:inline-flex;align-items:center;min-height:52px;padding:0 28px;border-radius:12px;background:#1D4ED8;color:#FFFFFF;font-weight:600;font-size:17px">Открыть каталог</a></div>
</div>
