<?php if (!defined('ABSPATH')) exit; get_header();
$q = new WP_Query(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 30, 'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => true]); ?>
<main style="max-width:1000px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:22px">
<nav style="font-size:14px;color:#44506A"><a class="hv-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / Полезное</nav>
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Полезное</h1>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:700px">Короткие справочные статьи: как подобрать аналог, прочитать маркировку, выбрать блок питания и паяльное оборудование.</p>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:16px">
<?php while ($q->have_posts()) : $q->the_post(); ?>
<a class="hv-card" href="<?php the_permalink(); ?>" style="display:flex;flex-direction:column;gap:10px;background:#fff;border:1px solid #D5DEEE;border-radius:16px;padding:20px">
<span style="font-size:19px;font-weight:800;line-height:1.3;color:#0F1B33"><?php the_title(); ?></span>
<span style="font-size:15px;line-height:1.55;color:#44506A"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 26)); ?></span>
<span style="font-size:14px;font-weight:700;color:#1D4ED8;margin-top:auto">Читать →</span>
</a>
<?php endwhile; wp_reset_postdata(); ?>
</div>
</main>
<?php get_footer();
