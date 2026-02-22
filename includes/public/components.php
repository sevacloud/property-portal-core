<?php
if (!defined('ABSPATH')) exit;

/**
 * Components to help keep front end consistent.
 */

function ppc_btn(string $label, string $url, string $variant = 'default'): string {
    $class = 'ppc-btn';
    if ($variant === 'danger') $class .= ' ppc-btn--danger';

    return sprintf(
        '<a class="%s" href="%s">%s</a>',
        esc_attr($class),
        esc_url($url),
        esc_html($label)
    );
}

function ppc_card_open(string $title): string {
    return '<section class="ppc-card"><h2 class="ppc-card__title">' . esc_html($title) . '</h2>';
}

function ppc_card_close(): string {
    return '</section>';
}