<?php
/** Заголовки безопасности для страниц сайта. */
defined('ABSPATH') || exit;

add_action('send_headers', function () {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    if (is_ssl()) header('Strict-Transport-Security: max-age=15552000');
});
