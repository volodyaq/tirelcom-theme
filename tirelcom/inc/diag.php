<?php
defined('ABSPATH') || exit;
add_action('rest_api_init', function () {
    register_rest_route('tek/v1', '/ipdiag', ['methods' => 'GET', 'permission_callback' => function () { return current_user_can('manage_options'); },
        'callback' => function () {
            $o = [];
            foreach (['REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_FORWARDED', 'HTTP_X_FORWARDED_PROTO', 'HTTP_CDN_LOOP', 'HTTP_X_HOSTINGER_REAL_IP'] as $k) $o[$k] = $_SERVER[$k] ?? null;
            return $o;
        }]);
});
