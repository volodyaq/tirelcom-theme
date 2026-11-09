<?php
if (!defined('ABSPATH')) exit;
get_header();
if (is_product()) {
    get_template_part('single-product-view');
} else {
    get_template_part('archive-view');
}
get_footer();
