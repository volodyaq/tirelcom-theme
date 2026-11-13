<?php
/**
 * Заявки с сайта: «Подберите деталь» (/podbor/) и «Оптовый запрос».
 * Заявка сохраняется в админке (меню «Заявки»), уходит в Telegram и на почту заказов (опция tirelcom_order_email).
 */
defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('tek_request', [
        'labels' => ['name' => 'Заявки', 'singular_name' => 'Заявка', 'menu_name' => 'Заявки', 'all_items' => 'Все заявки', 'edit_item' => 'Заявка'],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-email-alt', 'menu_position' => 56,
        'supports' => ['title', 'editor'], 'capability_type' => 'post', 'map_meta_cap' => true,
    ]);
});

function tek_req_types() {
    return [
        'podbor' => ['label' => 'Подбор детали', 'emoji' => '🔎', 'page' => '/podbor/'],
        'opt' => ['label' => 'Оптовый запрос', 'emoji' => '📦', 'page' => '/o-magazine/'],
    ];
}

function tek_req_token($ts) { return $ts . '.' . substr(hash_hmac('sha256', 'req' . $ts, wp_salt('auth')), 0, 20); }
function tek_req_token_ok($tok) {
    $x = explode('.', (string) $tok);
    if (count($x) !== 2 || !ctype_digit($x[0])) return false;
    $age = time() - (int) $x[0];
    return hash_equals(tek_req_token($x[0]), $tok) && $age >= 4 && $age < 6 * HOUR_IN_SECONDS;
}

function tek_req_submit() {
    $types = tek_req_types();
    $type = sanitize_key($_POST['rq_type'] ?? '');
    if (!isset($types[$type])) { wp_safe_redirect(home_url('/')); exit; }
    $back = home_url($types[$type]['page']);
    $fail = function ($code) use ($back, $type) { wp_safe_redirect(add_query_arg(['rq' => $code, 'rqt' => $type], $back) . '#rq-' . $type); exit; };
    if (!empty($_POST['rq_site'])) $fail('ok');
    if (!tek_req_token_ok($_POST['rq_t'] ?? '')) $fail('time');
    $text = trim(sanitize_textarea_field(wp_unslash($_POST['rq_text'] ?? '')));
    $name = trim(sanitize_text_field(wp_unslash($_POST['rq_name'] ?? '')));
    $phone = trim(sanitize_text_field(wp_unslash($_POST['rq_phone'] ?? '')));
    $how = sanitize_text_field(wp_unslash($_POST['rq_how'] ?? ''));
    if (mb_strlen($text) < 4 || mb_strlen($text) > 2000) $fail('text');
    if (!preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $phone)) $fail('phone');
    if (mb_strlen($name) > 80) $fail('text');
    if (preg_match('~https?://|www\.~iu', $text . ' ' . $name)) $fail('links');
    $key = 'tek_rq_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    if ((int) get_transient($key) >= 5) $fail('many');
    set_transient($key, (int) get_transient($key) + 1, DAY_IN_SECONDS);

    $photo = '';
    if (!empty($_FILES['rq_photo']['name']) && $_FILES['rq_photo']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['rq_photo']['size'] > 6 * MB_IN_BYTES) $fail('big');
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $up = wp_handle_upload($_FILES['rq_photo'], ['test_form' => false, 'mimes' => ['jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp']]);
        if (!empty($up['error'])) $fail('photo');
        $photo = $up['url'];
    }

    $info = $types[$type];
    $body = $text . "\n\nТелефон: " . $phone . ($name ? "\nИмя: " . $name : '') . ($how ? "\nСвязаться: " . $how : '') . ($photo ? "\nФото: " . $photo : '');
    $id = wp_insert_post(['post_type' => 'tek_request', 'post_status' => 'publish', 'post_title' => $info['label'] . ' · ' . $phone . ' · ' . mb_substr($text, 0, 40), 'post_content' => $body]);

    if (function_exists('tek_tg_send') && get_option('tek_tg_token')) {
        $m = $info['emoji'] . ' <b>' . esc_html($info['label']) . '</b>' . "\n\n" . esc_html($text) . "\n\n📞 " . esc_html($phone)
            . ($name ? "\n👤 " . esc_html($name) : '') . ($how ? "\nСвязаться: " . esc_html($how) : '') . ($photo ? "\n🖼 <a href=\"" . esc_url($photo) . '">Фото</a>' : '');
        tek_tg_send($m);
    }
    $to = get_option('tirelcom_order_email') ?: get_option('admin_email');
    wp_mail($to, $info['label'] . ' с сайта: ' . $phone, $body);
    $fail('ok');
}
add_action('admin_post_nopriv_tek_request', 'tek_req_submit');
add_action('admin_post_tek_request', 'tek_req_submit');

/* Форма. $type: podbor | opt */
function tek_req_form($type = 'podbor') {
    $types = tek_req_types();
    $rq = (isset($_GET['rqt']) && sanitize_key($_GET['rqt']) === $type && isset($_GET['rq'])) ? sanitize_key($_GET['rq']) : '';
    $msgs = [
        'ok' => ['Спасибо! Заявка получена, мы свяжемся с вами в рабочее время.', true],
        'text' => ['Опишите, что нужно (от 4 символов).', false],
        'phone' => ['Проверьте номер телефона.', false],
        'links' => ['Ссылки в заявке не принимаются, напишите маркировку или опишите деталь.', false],
        'time' => ['Страница устарела или форма отправлена слишком быстро. Обновите страницу и повторите.', false],
        'many' => ['Слишком много заявок с одного устройства. Позвоните нам: +373 533 51338.', false],
        'big' => ['Фото больше 6 МБ. Уменьшите его или пришлите в мессенджер.', false],
        'photo' => ['Фото не принято: нужен файл JPG, PNG или WEBP.', false],
    ];
    $opt = $type === 'opt';
    ob_start(); ?>
<section id="rq-<?php echo esc_attr($type); ?>" style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:22px;display:flex;flex-direction:column;gap:14px">
<?php if ($rq && isset($msgs[$rq])) : ?><div role="status" style="padding:12px 14px;border-radius:10px;font-size:15px;line-height:1.45;<?php echo $msgs[$rq][1] ? 'background:#E7F6EC;color:#14602B' : 'background:#FDECEC;color:#8A1C1C'; ?>"><?php echo esc_html($msgs[$rq][0]); ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;flex-direction:column;gap:14px">
<input type="hidden" name="action" value="tek_request">
<input type="hidden" name="rq_type" value="<?php echo esc_attr($type); ?>">
<input type="hidden" name="rq_t" value="<?php echo esc_attr(tek_req_token(time())); ?>">
<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Сайт<input type="text" name="rq_site" tabindex="-1" autocomplete="off"></label></div>
<label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600"><?php echo $opt ? 'Что нужно и в каком количестве' : 'Что ищете: маркировка, название, где используется'; ?>
<textarea name="rq_text" rows="5" maxlength="2000" required placeholder="<?php echo $opt ? 'Например: резисторы 0603, 10 кОм, 5000 шт.' : 'Например: LM317T, или транзистор с платы стиральной машины'; ?>" style="border:1px solid #C5D1E6;border-radius:10px;padding:10px 12px;font:inherit;font-size:16px;font-weight:400;resize:vertical"></textarea></label>
<?php if (!$opt) : ?>
<label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600">Фото платы или детали (по желанию)
<input type="file" name="rq_photo" accept="image/jpeg,image/png,image/webp" style="font:inherit;font-size:15px;font-weight:400"></label>
<?php endif; ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
<label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600">Телефон *
<input type="tel" name="rq_phone" required placeholder="+373 ..." style="height:46px;border:1px solid #C5D1E6;border-radius:10px;padding:0 12px;font:inherit;font-size:16px;font-weight:400"></label>
<label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600"><?php echo $opt ? 'Имя или организация' : 'Имя (по желанию)'; ?>
<input type="text" name="rq_name" maxlength="80" style="height:46px;border:1px solid #C5D1E6;border-radius:10px;padding:0 12px;font:inherit;font-size:16px;font-weight:400"></label>
</div>
<label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600">Как удобнее связаться
<select name="rq_how" style="height:46px;border:1px solid #C5D1E6;border-radius:10px;padding:0 10px;font:inherit;font-size:16px;font-weight:400;background:#fff"><option>Позвонить</option><option>Viber</option><option>Telegram</option><option>WhatsApp</option></select></label>
<button type="submit" class="hv-orange" style="height:50px;border:0;border-radius:12px;background:#FF8A1F;color:#0F1B33;font:inherit;font-weight:700;font-size:16px;cursor:pointer"><?php echo $opt ? 'Отправить запрос' : 'Отправить заявку'; ?></button>
<span style="font-size:13px;line-height:1.45;color:#6A7894">Номер телефона нужен только для ответа по заявке. Отправляя форму, вы соглашаетесь на обработку данных для ответа.</span>
</form>
</section>
<?php return ob_get_clean();
}
