<?php
/**
 * Alternate link tag injection for markdown discovery.
 *
 * @package ForTheBots
 */

namespace ForTheBots\Discovery;

if (!defined('ABSPATH')) {
    exit;
}

use ForTheBots\Plugin;

/**
 * Handles alternate link tag injection in HTML page head.
 */
class AlternateLinkHandler {

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register(): void {
        add_action('wp_head', [$this, 'output_alternate_link'], 5);
    }

    /**
     * Output alternate link tag for markdown version.
     *
     * @return void
     */
    public function output_alternate_link(): void {
        if (!is_singular()) {
            return;
        }

        $post = get_queried_object();
        if (!$post instanceof \WP_Post) {
            return;
        }

        if (get_post_status($post) !== 'publish') {
            return;
        }

        if (!Plugin::is_supported_post_type($post->post_type)) {
            return;
        }

        $md_url = rtrim(get_permalink($post), '/') . '.md';

        printf(
            '<link rel="alternate" type="text/markdown" href="%s" />' . "\n",
            esc_url($md_url)
        );
    }
}
