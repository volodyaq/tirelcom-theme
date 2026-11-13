<?php if (!defined('ABSPATH')) exit; get_header();
$rv = isset($_GET['rv']) ? sanitize_key($_GET['rv']) : '';
$msgs = [
    'ok' => ['Спасибо! Отзыв получен и появится на сайте после проверки.', true],
    'name' => ['Укажите имя (от 2 символов).', false],
    'rate' => ['Поставьте оценку от 1 до 5.', false],
    'short' => ['Напишите отзыв чуть подробнее (от 10 символов).', false],
    'long' => ['Отзыв слишком длинный, сократите до 1500 символов.', false],
    'links' => ['В отзыве нельзя оставлять ссылки и адреса.', false],
    'time' => ['Страница устарела или форма отправлена слишком быстро. Обновите страницу и попробуйте ещё раз.', false],
    'many' => ['Слишком много отзывов с одного устройства. Попробуйте завтра.', false],
    'err' => ['Не удалось сохранить отзыв. Позвоните нам или попробуйте позже.', false],
];
$list = tek_rev_list();
$sum = 0; $cnt = 0;
foreach ($list as $c) { $r = (int) get_comment_meta($c->comment_ID, 'tek_rating', true); if ($r) { $sum += $r; $cnt++; } }
$star = function ($n) { return '<span aria-label="' . (int) $n . ' из 5" style="color:#FF8A1F;letter-spacing:2px;font-size:18px">' . str_repeat('★', $n) . '<span style="color:#C9D6F2">' . str_repeat('★', 5 - $n) . '</span></span>'; };
?>
<main style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:28px">
<div style="display:flex;flex-direction:column;gap:12px">
<nav style="font-size:14px;color:#44506A"><a class="hv-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / Отзывы</nav>
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Отзывы покупателей</h1>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:760px">Расскажите, как всё прошло: нашли ли нужную деталь, помог ли совет, удобно ли заказывать. Отзывы появляются на сайте после проверки.</p>
<?php if ($cnt) : ?><div style="display:flex;align-items:center;gap:10px"><?php echo $star((int) round($sum / $cnt)); ?><span style="font-size:16px;color:#33405A"><b><?php echo esc_html(number_format($sum / $cnt, 1, ',', '')); ?></b> из 5, отзывов: <?php echo (int) $cnt; ?></span></div><?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;align-items:start">
<section style="display:flex;flex-direction:column;gap:14px">
<?php if (!$list) : ?><div style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:22px;color:#44506A;font-size:16px;line-height:1.5">Пока здесь нет отзывов. Будьте первым: форма справа.</div><?php endif; ?>
<?php foreach ($list as $c) : $r = (int) get_comment_meta($c->comment_ID, 'tek_rating', true); ?>
<article style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:8px">
<div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:baseline"><b style="font-size:17px"><?php echo esc_html($c->comment_author); ?></b><span style="font-size:13px;color:#6A7894"><?php echo esc_html(mysql2date('j F Y', $c->comment_date)); ?></span></div>
<?php if ($r) echo $star($r); ?>
<p style="margin:0;font-size:16px;line-height:1.55;color:#33405A;white-space:pre-line"><?php echo esc_html($c->comment_content); ?></p>
</article>
<?php endforeach; ?>
</section>

<section id="rev-form" style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:22px;display:flex;flex-direction:column;gap:14px">
<h2 style="margin:0;font-size:22px;font-weight:800">Оставить отзыв</h2>
<?php if ($rv && isset($msgs[$rv])) : ?><div role="status" style="padding:12px 14px;border-radius:10px;font-size:15px;line-height:1.45;<?php echo $msgs[$rv][1] ? 'background:#E7F6EC;color:#14602B' : 'background:#FDECEC;color:#8A1C1C'; ?>"><?php echo esc_html($msgs[$rv][0]); ?></div><?php endif; ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;flex-direction:column;gap:14px">
<input type="hidden" name="action" value="tek_review">
<input type="hidden" name="rv_t" value="<?php echo esc_attr(tek_rev_token(time())); ?>">
<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Сайт<input type="text" name="rv_site" tabindex="-1" autocomplete="off"></label></div>
<label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600">Ваше имя
<input type="text" name="rv_name" maxlength="60" required style="height:46px;border:1px solid #C5D1E6;border-radius:10px;padding:0 12px;font:inherit;font-size:16px;font-weight:400"></label>
<fieldset style="border:0;margin:0;padding:0;display:flex;flex-direction:column;gap:6px"><legend style="font-size:14px;font-weight:600;padding:0;margin-bottom:6px">Оценка</legend>
<div class="tek-rate" style="display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px">
<?php for ($i = 5; $i >= 1; $i--) : ?><input type="radio" id="rv<?php echo $i; ?>" name="rv_rate" value="<?php echo $i; ?>" required><label for="rv<?php echo $i; ?>" title="<?php echo $i; ?>">★</label><?php endfor; ?>
</div></fieldset>
<label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600">Отзыв
<textarea name="rv_text" rows="5" maxlength="1500" required style="border:1px solid #C5D1E6;border-radius:10px;padding:10px 12px;font:inherit;font-size:16px;font-weight:400;resize:vertical"></textarea></label>
<button type="submit" class="hv-orange" style="height:50px;border:0;border-radius:12px;background:#FF8A1F;color:#0F1B33;font:inherit;font-weight:700;font-size:16px;cursor:pointer">Отправить отзыв</button>
<span style="font-size:13px;line-height:1.45;color:#6A7894">Телефон и почту не спрашиваем. Ссылки и адреса в отзывах не публикуются. Отправляя отзыв, вы соглашаетесь на его публикацию на сайте под указанным именем.</span>
</form>
</section>
</div>
</main>
<?php get_footer();
