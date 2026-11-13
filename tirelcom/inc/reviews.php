<?php
/**
 * Отзывы о магазине: страница /otzyvy/ (шаблон page-otzyvy.php).
 * Отзыв сохраняется как комментарий к странице «Отзывы» со статусом «на проверке»,
 * владелец получает сообщение в Telegram, публикует в админке: Комментарии → Одобрить.
 */
defined('ABSPATH') || exit;

function tek_rev_page_id() {
    $p = get_page_by_path('otzyvy');
    return $p ? (int) $p->ID : 0;
}

function tek_rev_token($ts) {
    return $ts . '.' . substr(hash_hmac('sha256', 'rev' . $ts, wp_salt('auth')), 0, 20);
}

function tek_rev_token_ok($tok) {
    $x = explode('.', (string) $tok);
    if (count($x) !== 2 || !ctype_digit($x[0])) return false;
    return hash_equals(tek_rev_token($x[0]), $tok) && (time() - (int) $x[0]) >= 4 && (time() - (int) $x[0]) < 6 * HOUR_IN_SECONDS;
}

function tek_rev_submit() {
    $back = home_url('/otzyvy/');
    $fail = function ($code) use ($back) { wp_safe_redirect(add_query_arg('rv', $code, $back) . '#rev-form'); exit; };
    if (!empty($_POST['rv_site'])) $fail('ok'); // ловушка для ботов: делаем вид, что всё принято
    if (!tek_rev_token_ok($_POST['rv_t'] ?? '')) $fail('time');
    $name = trim(sanitize_text_field(wp_unslash($_POST['rv_name'] ?? '')));
    $text = trim(sanitize_textarea_field(wp_unslash($_POST['rv_text'] ?? '')));
    $rate = (int) ($_POST['rv_rate'] ?? 0);
    if (mb_strlen($name) < 2 || mb_strlen($name) > 60) $fail('name');
    if ($rate < 1 || $rate > 5) $fail('rate');
    if (mb_strlen($text) < 10) $fail('short');
    if (mb_strlen($text) > 1500) $fail('long');
    if (preg_match('~https?://|www\.|@[a-z0-9]~iu', $text . ' ' . $name)) $fail('links');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $key = 'tek_rev_' . md5($ip);
    if (get_transient($key) >= 3) $fail('many');
    set_transient($key, (int) get_transient($key) + 1, DAY_IN_SECONDS);
    $pid = tek_rev_page_id();
    if (!$pid) $fail('err');
    $cid = wp_insert_comment([
        'comment_post_ID' => $pid, 'comment_author' => $name, 'comment_content' => $text,
        'comment_approved' => 0, 'comment_type' => 'comment', 'comment_author_IP' => $ip,
        'comment_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
    ]);
    if (!$cid) $fail('err');
    add_comment_meta($cid, 'tek_rating', $rate);
    if (function_exists('tek_tg_send') && get_option('tek_tg_token')) {
        $stars = str_repeat('★', $rate) . str_repeat('☆', 5 - $rate);
        $msg = "💬 <b>Новый отзыв (ждёт проверки)</b>\n" . esc_html($name) . ' ' . $stars . "\n\n" . esc_html($text)
            . "\n\n<a href=\"" . esc_url(admin_url('edit-comments.php?comment_status=moderated')) . '">Проверить и опубликовать</a>';
        tek_tg_send($msg);
    }
    $fail('ok');
}
add_action('admin_post_nopriv_tek_review', 'tek_rev_submit');
add_action('admin_post_tek_review', 'tek_rev_submit');

function tek_rev_list() {
    $pid = tek_rev_page_id();
    if (!$pid) return [];
    return get_comments(['post_id' => $pid, 'status' => 'approve', 'orderby' => 'comment_date_gmt', 'order' => 'DESC', 'number' => 100]);
}
