<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main style="max-width:820px;margin:0 auto;padding:28px 24px 72px">
<?php while (have_posts()) : the_post(); ?>
<nav style="font-size:14px;color:#44506A;margin-bottom:12px"><a class="hv-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / <a class="hv-link" href="<?php echo esc_url(home_url('/poleznoe/')); ?>">Полезное</a></nav>
<article>
<h1 style="margin:0 0 10px;font-size:36px;line-height:1.15;font-weight:800;letter-spacing:-0.02em"><?php the_title(); ?></h1>
<div style="font-size:14px;color:#6A7894;margin-bottom:18px">Обновлено <?php echo esc_html(get_the_modified_date('j F Y')); ?></div>
<div class="tek-content tek-article"><?php the_content(); ?></div>
</article>
<aside style="margin-top:32px;background:#0F2A5C;color:#fff;border-radius:16px;padding:22px;display:flex;flex-direction:column;gap:10px">
<span style="font-size:20px;font-weight:800">Нужна помощь с подбором?</span>
<span style="font-size:15px;line-height:1.55;color:#C9D6F2">Пришлите маркировку или фото детали, подскажем аналог и проверим наличие.</span>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:4px">
<a class="hv-orange" href="<?php echo esc_url(home_url('/podbor/')); ?>" style="display:inline-flex;align-items:center;min-height:46px;padding:0 22px;border-radius:12px;background:#FF8A1F;color:#0F1B33;font-weight:700">Оставить заявку</a>
<a href="tel:+37353351338" style="display:inline-flex;align-items:center;min-height:46px;padding:0 22px;border-radius:12px;border:2px solid #fff;color:#fff;font-weight:600">+373 533 51338</a>
</div></aside>
<?php endwhile; ?>
</main>
<?php get_footer();
