<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main style="max-width:760px;margin:0 auto;padding:56px 24px 88px;text-align:center;display:flex;flex-direction:column;gap:18px;align-items:center">
<span style="font-family:'JetBrains Mono',monospace;font-size:72px;font-weight:700;color:#1D4ED8;line-height:1">404</span>
<h1 style="margin:0;font-size:34px;line-height:1.2;font-weight:800;letter-spacing:-0.02em">Такой страницы нет</h1>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:560px">Возможно, ссылка устарела или товар уже продан. Найдите деталь поиском или загляните в каталог.</p>
<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" style="display:flex;gap:8px;width:100%;max-width:520px">
<input type="hidden" name="post_type" value="product">
<input type="search" name="s" placeholder="Название или маркировка, например LM317" style="flex:1;height:50px;border:1px solid #C5D1E6;border-radius:12px;padding:0 14px;font:inherit;font-size:16px">
<button type="submit" style="height:50px;padding:0 22px;border:0;border-radius:12px;background:#1D4ED8;color:#fff;font:inherit;font-weight:700;cursor:pointer">Найти</button>
</form>
<div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-top:6px">
<a class="hv-orange" href="<?php echo esc_url(home_url('/shop/')); ?>" style="display:inline-flex;align-items:center;min-height:48px;padding:0 24px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-weight:700">Открыть каталог</a>
<a class="hv-outline" href="<?php echo esc_url(home_url('/podbor/')); ?>" style="display:inline-flex;align-items:center;min-height:48px;padding:0 24px;border-radius:12px;border:2px solid #0F2A5C;color:#0F2A5C;font-weight:600">Подобрать деталь</a>
</div>
</main>
<?php get_footer();
