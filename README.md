# For the Bots

A WordPress plugin that provides markdown versions of posts and pages for LLMs and users who prefer clean, structured content over HTML.

Fork of [Markdown Alternate](https://github.com/ProgressPlanner/markdown-alternate) by [Joost de Valk](https://joost.blog).

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
