<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main style="max-width:1200px;margin:0 auto;padding:40px 24px 72px">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article>
<h1 style="margin:0 0 20px;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em"><?php the_title(); ?></h1>
<div class="tek-content" style="font-size:17px;line-height:1.6;color:#33405A"><?php the_content(); ?></div>
</article>
<?php endwhile; else : ?>
<h1 style="font-size:38px;font-weight:800">Ничего не найдено</h1>
<?php endif; ?>
</main>
<?php get_footer();
