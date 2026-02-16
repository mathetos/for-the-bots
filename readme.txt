=== For the Bots ===
Contributors: webdevmattcrom
Tags: markdown, content, api, llm
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides markdown versions of posts and pages via .md URLs. Fork of Markdown Alternate by Joost de Valk.

== Description ==

For the Bots exposes your WordPress content as clean markdown through predictable URLs. Simply append `.md` to any post or page URL. Fork of [Markdown Alternate](https://github.com/ProgressPlanner/markdown-alternate) by Joost de Valk.

= Why For the Bots? =

This fork improves on the original Markdown Alternate with better performance, stability, and security.

* **Performance & caching** — Rendered markdown is cached using the WordPress object cache and transients. A singleton converter avoids redundant HTML-to-markdown work. Configurable via `for_the_bots_cache_ttl`, `for_the_bots_dual_cache_write`, and `for_the_bots_use_cache` filters. Password-protected posts are never cached.
* **Stability** — HTML-to-Markdown conversion is wrapped in try/catch with a `wp_strip_all_tags` fallback. Use the `for_the_bots_conversion_error_fallback` filter to customize fallback output. Hierarchical pages use `get_page_by_path()` with a `for_the_bots_resolve_post_by_path` filter when `url_to_postid` fails. Accept-header handling is limited to singular content to avoid 404s on feeds and archives.
* **Security** — Uses `wp_safe_redirect` instead of `wp_redirect`, and `esc_url_raw` for safer URL handling. Path validation helps prevent edge-case redirect issues.
* **Extensibility** — Support for custom post types and post resolution via filters, plus cache context and conversion-fallback hooks for advanced control.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/for-the-bots/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Visit any post or page URL with `.md` extension

== Custom Post Type Support ==

Add custom post types using the filter:

add_filter('for_the_bots_supported_post_types', function($types) {
    $types[] = 'your_custom_type';
    return $types;
});
