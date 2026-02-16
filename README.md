# For the Bots

A WordPress plugin that provides markdown versions of posts and pages for LLMs and users who prefer clean, structured content over HTML.

Fork of [Markdown Alternate](https://github.com/ProgressPlanner/markdown-alternate) by [Joost de Valk](https://joost.blog).

## Why For the Bots?

This fork improves on the original Markdown Alternate with better performance, stability, and security.

- **Performance & caching** — Rendered markdown is cached using the WordPress object cache and transients. A singleton converter avoids redundant HTML-to-markdown work. Configurable via `for_the_bots_cache_ttl`, `for_the_bots_dual_cache_write`, and `for_the_bots_use_cache` filters. Password-protected posts are never cached.
- **Stability** — HTML-to-Markdown conversion is wrapped in try/catch with a `wp_strip_all_tags` fallback. Use the `for_the_bots_conversion_error_fallback` filter to customize fallback output. Hierarchical pages use `get_page_by_path()` with a `for_the_bots_resolve_post_by_path` filter when `url_to_postid` fails. Accept-header handling is limited to singular content to avoid 404s on feeds and archives.
- **Security** — Uses `wp_safe_redirect` instead of `wp_redirect`, and `esc_url_raw` for safer URL handling. Path validation helps prevent edge-case redirect issues.
- **Extensibility** — Support for custom post types and post resolution via filters, plus cache context and conversion-fallback hooks for advanced control.

## Features

- Access any post at `/post-slug.md`
- Access any page at `/page-slug.md`
- Nested pages work: `/parent/child.md`
- Date-based permalinks supported: `/2024/01/my-post.md`
- Content negotiation: Use `Accept: text/markdown` header on any post/page URL
- Zero configuration required

## Installation

### For Users

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/for-the-bots/`
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Visit any post or page URL with `.md` extension

### For Developers

```bash
git clone https://github.com/mathetos/for-the-bots.git
cd for-the-bots
composer install
```

## Usage

Append `.md` to any post or page URL, or use `?format=markdown`. See the original [Markdown Alternate](https://github.com/ProgressPlanner/markdown-alternate) README for full documentation.

## Custom Post Type Support

```php
add_filter('for_the_bots_supported_post_types', function($types) {
    $types[] = 'book';
    return $types;
});
```

## License

GPL-2.0-or-later
