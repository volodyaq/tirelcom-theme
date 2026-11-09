<?php if (!defined('ABSPATH')) exit; ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div style="font-family:'Manrope',system-ui,sans-serif;color:#0F1B33;background:#F4F7FB;width:100%">

<div style="background:#0F2A5C;color:#DCE6FA;font-size:16px">
<div style="max-width:1200px;margin:0 auto;padding:12px 24px;display:flex;flex-wrap:wrap;gap:8px 24px;justify-content:space-between;align-items:center">
<div style="flex:1 1 auto;display:flex;flex-wrap:wrap;align-items:baseline;gap:2px 16px;color:#FFFFFF">
<span style="font-size:18px;font-weight:800;letter-spacing:-0.01em">Нужны детали? <span style="color:#FF8A1F">Вам к нам!</span></span>
<span style="font-size:15px;color:#DCE6FA">Подберём, найдём аналог, подскажем по схеме</span>
</div>
<?php if ((float) get_option('tirelcom_mdl_rate', 0) > 0) : ?>
<span style="display:flex;gap:6px">
<button type="button" class="tek-cur-btn" data-cur="PRB" aria-pressed="true" style="border-radius:6px;padding:5px 12px;font-size:14px">руб. ПМР</button>
<button type="button" class="tek-cur-btn" data-cur="MDL" aria-pressed="false" style="border-radius:6px;padding:5px 12px;font-size:14px">MDL</button>
</span>
<?php endif; ?>
</div>
</div>

<header style="background:#FFFFFF;border-bottom:1px solid #D5DEEE">
<div style="max-width:1200px;margin:0 auto;padding:18px 24px;display:flex;flex-wrap:wrap;gap:16px 12px;align-items:center">
<a href="<?php echo esc_url(home_url('/')); ?>" style="display:flex;align-items:center;gap:12px;margin-right:16px;color:#0F1B33;text-decoration:none">
<span style="width:54px;height:54px;border-radius:14px;background:#1D4ED8;display:flex;align-items:center;justify-content:center">
<svg aria-hidden="true" width="32" height="32" viewBox="0 0 26 26" fill="none" stroke="#FF8A1F" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 13h4l2-6 4 12 4-12 2 6h6"/></svg>
</span>
<span style="display:flex;flex-direction:column;line-height:1.15">
<span style="font-family:'Manrope',system-ui,sans-serif;font-weight:800;font-size:32px;letter-spacing:-0.01em;color:#0F2A5C">Тир<span style="color:#FF8A1F">Эл</span>Ком</span>
<span style="font-size:13px;color:#44506A">Радиодетали и электроника</span>
</span>
</a>
<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" style="flex:1 1 360px;display:flex;gap:8px;min-width:0">
<label for="q" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap">Поиск по каталогу</label>
<input id="q" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Название или маркировка: LM317, ATmega328, A4988…" style="flex:1 1 auto;min-width:0;height:48px;border:2px solid #1D4ED8;border-radius:12px;padding:0 16px;font-family:inherit;font-size:16px;background:#FFFFFF;color:#0F1B33">
<input type="hidden" name="post_type" value="product">
<button class="hv-blue" type="submit" style="height:48px;padding:0 24px;border:0;border-radius:12px;background:#1D4ED8;color:#FFFFFF;font-size:16px;font-weight:600">Найти</button>
</form>
<div style="display:flex;gap:10px">
<a class="hv-orange" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : '#'); ?>" style="display:flex;align-items:center;justify-content:center;gap:10px;min-width:200px;min-height:48px;padding:0 32px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-weight:700;font-size:16px">
<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F1B33" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 7H6"/></svg>
Корзина · <span class="tek-cart-count"><?php echo function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0; ?></span>
</a>
</div>
</div>
<nav aria-label="Разделы каталога" style="border-top:1px solid #E6ECF6">
<div class="navrow" style="max-width:1200px;margin:0 auto;padding:0 24px;display:flex;flex-wrap:wrap;gap:0 6px">
<?php foreach (tek_top_cats() as $c) : ?>
<a href="<?php echo esc_url(get_term_link($c)); ?>" style="display:block;padding:14px 12px;color:#0F1B33;font-weight:500;font-size:15px"><?php echo esc_html($c->name); ?></a>
<?php endforeach; ?>
</div>
</nav>
</header>
