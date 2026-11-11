<?php
/**
 * Уведомления о новых заказах в Telegram.
 * Токен бота и chat_id хранятся в базе (опции tek_tg_token, tek_tg_chat), в репозиторий не попадают.
 * Настройка: WooCommerce → Telegram-уведомления.
 */
defined('ABSPATH') || exit;

function tek_tg_api($method, $params = [], $token = '') {
    $token = $token ?: get_option('tek_tg_token', '');
    if (!$token) return new WP_Error('tek_tg', 'Токен не задан');
    $r = wp_remote_post('https://api.telegram.org/bot' . $token . '/' . $method, ['timeout' => 12, 'body' => $params]);
    if (is_wp_error($r)) return $r;
    $j = json_decode(wp_remote_retrieve_body($r), true);
    if (empty($j['ok'])) return new WP_Error('tek_tg', isset($j['description']) ? $j['description'] : 'Ошибка Telegram');
    return $j['result'];
}

function tek_tg_send($text) {
    $chat = get_option('tek_tg_chat', '');
    if (!$chat) return new WP_Error('tek_tg', 'Не задан чат');
    return tek_tg_api('sendMessage', ['chat_id' => $chat, 'text' => $text, 'parse_mode' => 'HTML', 'disable_web_page_preview' => 'true']);
}

function tek_tg_order_text($order) {
    $e = function ($s) { return esc_html((string) $s); };
    $t = '🛒 <b>Новый заказ № ' . $e($order->get_order_number()) . '</b>' . "\n";
    $name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    $t .= '👤 ' . $e($name ?: 'Клиент') . "\n";
    $t .= '📞 ' . $e($order->get_billing_phone()) . "\n";
    $t .= '📦 ' . $e($order->get_meta('tek_delivery')) . "\n";
    if ($order->get_meta('tek_address')) $t .= '📍 ' . $e($order->get_meta('tek_address')) . "\n";
    if ($order->get_customer_note()) $t .= '💬 ' . $e($order->get_customer_note()) . "\n";
    $t .= "\n";
    foreach ($order->get_items() as $it) {
        $p = $it->get_product();
        $sku = $p ? $p->get_sku() : '';
        $status = ($p && $p->get_stock_status() === 'onbackorder') ? ' (предзаказ)' : '';
        $t .= '• ' . $e($it->get_name()) . ($sku ? ' [код ' . $e($sku) . ']' : '') . $status . ' × ' . (int) $it->get_quantity() . ' = ' . $e(wp_strip_all_tags(wc_price($it->get_total()))) . "\n";
    }
    $t .= "\n<b>Итого: " . $e(html_entity_decode(wp_strip_all_tags(wc_price($order->get_total())))) . '</b>' . "\n";
    $t .= '<a href="' . esc_url($order->get_edit_order_url()) . '">Открыть заказ в админке</a>';
    return $t;
}

add_action('woocommerce_checkout_order_processed', function ($order_id) {
    if (!get_option('tek_tg_token') || !get_option('tek_tg_chat')) return;
    $order = wc_get_order($order_id);
    if (!$order || $order->get_meta('_tek_tg_sent')) return;
    $r = tek_tg_send(tek_tg_order_text($order));
    if (!is_wp_error($r)) { $order->update_meta_data('_tek_tg_sent', '1'); $order->save_meta_data(); }
    else $order->add_order_note('Telegram: не отправлено (' . $r->get_error_message() . ')');
}, 30);

add_action('admin_menu', function () {
    add_submenu_page('woocommerce', 'Telegram-уведомления', 'Telegram-уведомления', 'manage_woocommerce', 'tek-telegram', 'tek_tg_page');
});

function tek_tg_page() {
    if (!current_user_can('manage_woocommerce')) return;
    $msg = '';
    if (!empty($_POST['tek_tg_action']) && check_admin_referer('tek_tg')) {
        $a = sanitize_text_field(wp_unslash($_POST['tek_tg_action']));
        if ($a === 'save') {
            $tok = isset($_POST['tek_tg_token']) ? trim(sanitize_text_field(wp_unslash($_POST['tek_tg_token']))) : '';
            if ($tok !== '') {
                $me = tek_tg_api('getMe', [], $tok);
                if (is_wp_error($me)) $msg = 'Токен не подошёл: ' . $me->get_error_message();
                else { update_option('tek_tg_token', $tok, false); update_option('tek_tg_bot', '@' . $me['username'], false); $msg = 'Токен принят, бот ' . $me['username'] . '. Теперь откройте бота в Telegram, нажмите Start, вернитесь сюда и нажмите «Найти мой чат».'; }
            }
        } elseif ($a === 'find') {
            $u = tek_tg_api('getUpdates', ['limit' => 20]);
            if (is_wp_error($u)) $msg = 'Ошибка: ' . $u->get_error_message();
            else {
                $chat = '';
                foreach (array_reverse($u) as $x) { if (!empty($x['message']['chat']['id'])) { $chat = $x['message']['chat']['id']; $who = trim(($x['message']['chat']['first_name'] ?? '') . ' ' . ($x['message']['chat']['last_name'] ?? '')); break; } }
                if ($chat) { update_option('tek_tg_chat', (string) $chat, false); $msg = 'Чат найден: ' . esc_html($who) . '. Нажмите «Отправить тест».'; }
                else $msg = 'Сообщений боту пока нет. Откройте бота в Telegram, нажмите Start (или напишите ему любое слово) и повторите.';
            }
        } elseif ($a === 'test') {
            $r = tek_tg_send('✅ Тест: уведомления о заказах tirelcom.com подключены.');
            $msg = is_wp_error($r) ? 'Ошибка: ' . $r->get_error_message() : 'Тестовое сообщение отправлено, проверьте Telegram.';
        } elseif ($a === 'reset') {
            delete_option('tek_tg_token'); delete_option('tek_tg_chat'); delete_option('tek_tg_bot');
            $msg = 'Настройки Telegram удалены.';
        }
    }
    $has_tok = (bool) get_option('tek_tg_token'); $chat = get_option('tek_tg_chat'); $bot = get_option('tek_tg_bot');
    echo '<div class="wrap"><h1>Telegram-уведомления о заказах</h1>';
    if ($msg) echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>';
    echo '<p>Статус: токен ' . ($has_tok ? '<b>задан</b> (' . esc_html($bot) . ')' : '<b>не задан</b>') . ', чат ' . ($chat ? '<b>найден</b>' : '<b>не найден</b>') . '.</p>';
    echo '<form method="post">'; wp_nonce_field('tek_tg');
    echo '<h2>1. Токен бота</h2><p>Получить у @BotFather в Telegram: команда /newbot. Токен вставляется только сюда и в репозиторий не попадает.</p>';
    echo '<p><input type="password" name="tek_tg_token" class="regular-text" autocomplete="off" placeholder="' . ($has_tok ? 'Токен сохранён; чтобы заменить, вставьте новый' : 'Вставьте токен') . '"></p>';
    echo '<p><button class="button button-primary" name="tek_tg_action" value="save">Сохранить токен</button></p>';
    echo '<h2>2. Привязать чат</h2><p>Откройте своего бота в Telegram, нажмите Start, затем нажмите кнопку.</p>';
    echo '<p><button class="button" name="tek_tg_action" value="find"' . ($has_tok ? '' : ' disabled') . '>Найти мой чат</button> ';
    echo '<button class="button" name="tek_tg_action" value="test"' . ($chat ? '' : ' disabled') . '>Отправить тест</button> ';
    echo '<button class="button" name="tek_tg_action" value="reset" onclick="return confirm(\'Удалить токен и чат?\')">Сбросить</button></p>';
    echo '</form></div>';
}
