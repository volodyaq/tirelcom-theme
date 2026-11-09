<?php
defined('ABSPATH') || exit;
/** @var WC_Order $order */
$f = function ($v) { return number_format((float) $v, 2, ',', ' ') . ' руб. ПМР'; };
$td = 'style="border:1px solid #D9D9D9;padding:6px 8px;font-size:12px;vertical-align:top"';
$th = 'style="border:1px solid #D9D9D9;padding:6px 8px;font-size:12px;background:#EFEFEF;text-align:left"';
$name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
$deliv = $order->get_meta('tek_delivery');
$addr = $order->get_meta('tek_address');
?>
<p style="font-size:13px">Вы получили заказ.</p>
<table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-family:Arial,sans-serif">
<tr><td colspan="2" <?php echo $th; ?>><b>Информация о заказе</b></td></tr>
<tr>
<td <?php echo $td; ?>><b>Заказ №:</b> <?php echo esc_html($order->get_order_number()); ?><br><b>Дата добавления:</b> <?php echo esc_html(wc_format_datetime($order->get_date_created(), 'd.m.Y H:i')); ?><br><b>Метод оплаты:</b> <?php echo esc_html($order->get_payment_method_title() ?: 'Оплата при получении'); ?><br><b>Метод доставки:</b> <?php echo esc_html($deliv); ?></td>
<td <?php echo $td; ?>><b>Телефон:</b> <?php echo esc_html($order->get_billing_phone()); ?><br><b>IP адрес:</b> <?php echo esc_html($order->get_customer_ip_address()); ?><br><b>Статус заказа:</b> <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
</tr>
</table>
<table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-family:Arial,sans-serif">
<tr><td <?php echo $th; ?>><b>Клиент</b></td><td <?php echo $th; ?>><b>Получение</b></td></tr>
<tr><td <?php echo $td; ?>><?php echo esc_html($name); ?><br><?php echo esc_html($order->get_billing_phone()); ?></td>
<td <?php echo $td; ?>><?php echo esc_html($deliv); ?><?php if ($addr) echo '<br>' . esc_html($addr); ?></td></tr>
<?php if ($order->get_customer_note()) : ?><tr><td colspan="2" <?php echo $td; ?>><b>Комментарий:</b> <?php echo esc_html($order->get_customer_note()); ?></td></tr><?php endif; ?>
</table>
<table style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif">
<tr><td <?php echo $th; ?>><b>Товар</b></td><td <?php echo $th; ?>><b>Модель</b></td><td <?php echo $th; ?>><b>Кол-во</b></td><td <?php echo $th; ?>><b>Цена</b></td><td <?php echo $th; ?>><b>Итого</b></td></tr>
<?php foreach ($order->get_items() as $item) : $p = $item->get_product(); $q = $item->get_quantity(); ?>
<tr><td <?php echo $td; ?>><?php echo esc_html($item->get_name()); ?></td><td <?php echo $td; ?>><?php echo esc_html($p ? $p->get_sku() : ''); ?></td><td <?php echo $td; ?>><?php echo (int) $q; ?></td><td <?php echo $td; ?>><?php echo esc_html($f($q ? $item->get_total() / $q : 0)); ?></td><td <?php echo $td; ?>><?php echo esc_html($f($item->get_total())); ?></td></tr>
<?php endforeach; ?>
<tr><td colspan="4" <?php echo $td; ?> align="right"><b>Итого:</b></td><td <?php echo $td; ?>><b><?php echo esc_html($f($order->get_total())); ?></b></td></tr>
</table>
<?php
