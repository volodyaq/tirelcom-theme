<?php
/**
 * Защита оформления заказа от ботов: скрытое поле-ловушка, подписанный токен со временем,
 * лимит заказов с одного адреса в сутки, проверка номера телефона.
 */
defined('ABSPATH') || exit;

function tek_co_token($ts) { return $ts . '.' . substr(hash_hmac('sha256', 'co' . $ts, wp_salt('auth')), 0, 20); }

function tek_co_fields() {
    echo '<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Сайт<input type="text" name="tek_co_site" tabindex="-1" autocomplete="off"></label></div>';
    echo '<input type="hidden" name="tek_co_t" value="' . esc_attr(tek_co_token(time())) . '">';
}

add_action('woocommerce_checkout_process', function () {
    $stop = function ($msg) { wc_add_notice($msg, 'error'); };
    if (!empty($_POST['tek_co_site'])) { $stop('Не удалось оформить заказ. Позвоните нам: +373 533 51338.'); return; }
    $tok = isset($_POST['tek_co_t']) ? (string) wp_unslash($_POST['tek_co_t']) : '';
    $x = explode('.', $tok);
    $ok = count($x) === 2 && ctype_digit($x[0]) && hash_equals(tek_co_token($x[0]), $tok);
    $age = $ok ? time() - (int) $x[0] : 0;
    if (!$ok || $age < 3) { $stop('Страница устарела или форма отправлена слишком быстро. Обновите страницу и оформите заказ ещё раз.'); return; }
    if ($age > 2 * DAY_IN_SECONDS) { $stop('Страница оформления открыта слишком давно. Обновите страницу и повторите.'); return; }
    $phone = isset($_POST['billing_phone']) ? preg_replace('/\D/', '', (string) wp_unslash($_POST['billing_phone'])) : '';
    if (strlen($phone) < 7 || strlen($phone) > 15) { $stop('Проверьте номер телефона: нужен номер, по которому мы сможем связаться.'); return; }
    $key = 'tek_co_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $n = (int) get_transient($key);
    if ($n >= 8) { $stop('С этого устройства сегодня уже оформлено много заказов. Позвоните нам: +373 533 51338.'); return; }
    set_transient($key, $n + 1, DAY_IN_SECONDS);
});
