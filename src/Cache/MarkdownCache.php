<?php
/**
 * Cache layer for rendered markdown output.
 *
 * @package ForTheBots
 */

namespace ForTheBots\Cache;

if (!defined('ABSPATH')) {
    exit;
}

use WP_Post;

/**
 * Caches rendered markdown to avoid repeated the_content, conversion on every request.
 */
class MarkdownCache {

    const CACHE_GROUP = 'for_the_bots';
    const DEFAULT_TTL = 43200;

    /**
     * Bump the last_changed value for the cache group.
     *
     * @return string
     */
    public static function bump_last_changed(): string {
        return wp_cache_set_last_changed(self::CACHE_GROUP);
    }

    /**
     * @param WP_Post $post
     * @return string|false
     */
    public function get(WP_Post $post) {
        if (!$this->should_use_cache()) {
            return false;
        }

        $key = $this->get_cache_key($post);
        if ($key === null) {
            return false;
        }

        $use_object_cache = $this->prefer_object_cache();

        if ($use_object_cache) {
            $cached = wp_cache_get($key, self::CACHE_GROUP);
            if (false !== $cached && is_string($cached)) {
                return $cached;
            }
        }

        $transient = get_transient($key);
        if (false !== $transient && is_string($transient)) {
            if ($use_object_cache) {
                wp_cache_set($key, $transient, self::CACHE_GROUP, $this->get_ttl());
            }
            return $transient;
        }

        return false;
    }

    /**
     * @param WP_Post $post
     * @param string  $markdown
     * @return bool
     */
    public function set(WP_Post $post, string $markdown): bool {
        if (!$this->should_use_cache()) {
            return false;
        }

        $key = $this->get_cache_key($post);
        if ($key === null) {
            return false;
        }

        $ttl = $this->get_ttl();
        $use_object_cache = $this->prefer_object_cache();
        $dual_write = $this->dual_cache_write();

        if ($use_object_cache) {
            wp_cache_set($key, $markdown, self::CACHE_GROUP, $ttl);
            if ($dual_write) {
                return (bool) set_transient($key, $markdown, $ttl);
            }
            return true;
        }

        return (bool) set_transient($key, $markdown, $ttl);
    }

    /**
     * @param WP_Post $post
     * @return string|null
     */
    private function get_cache_key(WP_Post $post): ?string {
        $modified_ts = $this->get_modified_timestamp($post);
        if ($modified_ts === null) {
            return null;
        }

        $blog_id = get_current_blog_id();
        $context_hash = $this->get_context_hash();
        $last_changed = wp_cache_get_last_changed(self::CACHE_GROUP);

        return sprintf(
            'for_the_bots_%d_%d_%s_%s_%s',
            $blog_id,
            $post->ID,
            $modified_ts,
            $context_hash,
            $last_changed
        );
    }

    /**
     * @param WP_Post $post
     * @return string|null
     */
    private function get_modified_timestamp(WP_Post $post): ?string {
        $gmt = $post->post_modified_gmt ?? '';
        if ($gmt !== '') {
            $ts = strtotime($gmt);
            return $ts !== false ? (string) $ts : null;
        }
        $ts = get_post_modified_time('U', true, $post);
        return $ts ? (string) $ts : null;
    }

    /**
     * @return string
     */
    private function get_context_hash(): string {
        $context = [
            'locale' => get_locale(),
            'types'  => \ForTheBots\Plugin::get_supported_post_types(),
        ];
        $context = apply_filters('for_the_bots_cache_context', $context);
        return md5(wp_json_encode($context));
    }

    /**
     * @return bool
     */
    private function prefer_object_cache(): bool {
        return function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache();
    }

    /**
     * @return bool
     */
    private function dual_cache_write(): bool {
        return (bool) apply_filters('for_the_bots_dual_cache_write', false);
    }

    /**
     * @return int
     */
    private function get_ttl(): int {
        return (int) apply_filters('for_the_bots_cache_ttl', self::DEFAULT_TTL);
    }

    /**
     * @return bool
     */
    private function should_use_cache(): bool {
        return (bool) apply_filters('for_the_bots_use_cache', true);
    }
}
