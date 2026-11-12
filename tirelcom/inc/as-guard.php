<?php
/**
 * Защита базы: плагин Hostinger Tools зациклил задачу generate_llmstxt и создал 2,7 млн записей
 * в Action Scheduler (около 2,7 ГБ), из-за чего база достигла лимита хостинга. Новые такие задачи не создаём.
 */
defined('ABSPATH') || exit;
foreach (['pre_as_enqueue_async_action', 'pre_as_schedule_single_action', 'pre_as_schedule_recurring_action', 'pre_as_schedule_cron_action'] as $f) {
    add_filter($f, function ($pre, $hook = '') {
        return (is_string($hook) && strpos($hook, 'hostinger_tools/jobs/generate_llmstxt') === 0) ? 0 : $pre;
    }, 10, 2);
}
