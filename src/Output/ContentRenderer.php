<?php
/**
 * Content renderer for markdown output.
 *
 * @package ForTheBots
 */

namespace ForTheBots\Output;

if (!defined('ABSPATH')) {
    exit;
}

use WP_Post;
use ForTheBots\Cache\MarkdownCache;
use ForTheBots\Converter\MarkdownConverter;

/**
 * Renders WordPress posts as markdown with YAML frontmatter.
 */
class ContentRenderer {

    /**
     * @var MarkdownConverter
     */
    private $converter;

    /**
     * @var MarkdownCache
     */
    private $cache;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->converter = MarkdownConverter::get_instance();
        $this->cache     = new MarkdownCache();
    }

    /**
     * Render a post as complete markdown output.
     *
     * @param WP_Post $post The post to render.
     * @return string The rendered markdown content.
     */
    public function render(WP_Post $post): string {
        $use_cache = !post_password_required($post);

        if ($use_cache) {
            $cached = $this->cache->get($post);
            if ($cached !== false) {
                return $cached;
            }
        }

        $frontmatter = $this->generate_frontmatter($post);
        $title = get_the_title($post);

        $content = $post->post_content;
        $content = apply_filters('the_content', $content); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.
        $content = $this->strip_code_block_markup($content);

        try {
            $body = $this->converter->convert($content);
        } catch (\Throwable $e) {
            if ((defined('WP_DEBUG') && WP_DEBUG) || (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG)) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional conditional debug logging.
                error_log(
                    sprintf(
                        '[For the Bots] HTML conversion failed for post ID %d: %s',
                        $post->ID,
                        $e->getMessage()
                    )
                );
            }
            $body = apply_filters(
                'for_the_bots_conversion_error_fallback',
                $this->get_fallback_plain_text($content),
                $content,
                $post,
                $e
            );
        }

        $output = $frontmatter . "\n\n";
        $output .= '# ' . $this->decode_entities($title) . "\n\n";
        $output .= $body;

        if ($use_cache) {
            $this->cache->set($post, $output);
        }

        return $output;
    }

    /**
     * @param WP_Post $post
     * @return string
     */
    private function generate_frontmatter(WP_Post $post): string {
        $lines = ['---'];

        $title = get_the_title($post);
        $lines[] = 'title: "' . $this->escape_yaml($title) . '"';

        $date = get_the_date('Y-m-d', $post);
        $lines[] = 'date: ' . $date;

        $author = get_the_author_meta('display_name', $post->post_author);
        $lines[] = 'author: "' . $this->escape_yaml($author) . '"';

        $featured_image = get_the_post_thumbnail_url($post->ID, 'full');
        if ($featured_image) {
            $lines[] = 'featured_image: "' . $this->escape_yaml($featured_image) . '"';
        }

        $categories = get_the_terms($post->ID, 'category');
        if ($categories && !is_wp_error($categories)) {
            $lines[] = 'categories:';
            foreach ($categories as $category) {
                $lines[] = '  - name: "' . $this->escape_yaml($category->name) . '"';
                $lines[] = '    url: "' . $this->get_term_markdown_url($category) . '"';
            }
        }

        $tags = get_the_terms($post->ID, 'post_tag');
        if ($tags && !is_wp_error($tags)) {
            $lines[] = 'tags:';
            foreach ($tags as $tag) {
                $lines[] = '  - name: "' . $this->escape_yaml($tag->name) . '"';
                $lines[] = '    url: "' . $this->get_term_markdown_url($tag) . '"';
            }
        }

        $lines[] = '---';

        return implode("\n", $lines);
    }

    /**
     * @param \WP_Term $term
     * @return string
     */
    private function get_term_markdown_url(\WP_Term $term): string {
        $url = get_term_link($term);
        if (is_wp_error($url)) {
            return '';
        }
        $path = wp_parse_url($url, PHP_URL_PATH);
        return rtrim($path, '/') . '.md';
    }

    /**
     * @param string $value
     * @return string
     */
    private function escape_yaml(string $value): string {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('"', '\\"', $value);
        return $value;
    }

    /**
     * @param string $html
     * @return string
     */
    private function get_fallback_plain_text(string $html): string {
        $text = wp_strip_all_tags($html);
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param string $value
     * @return string
     */
    private function decode_entities(string $value): string {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param string $content
     * @return string
     */
    private function strip_code_block_markup(string $content): string {
        return preg_replace_callback(
            '/<pre([^>]*)>(.*?)<\/pre>/is',
            function ($matches) {
                $inner = $matches[2];

                $lang = '';
                if (preg_match('/<code[^>]*class="[^"]*language-(\w+)[^"]*"[^>]*>/i', $inner, $lang_match)) {
                    $lang = $lang_match[1];
                } elseif (preg_match('/<code[^>]*class="[^"]*hljs[^"]*language-(\w+)[^"]*"[^>]*>/i', $inner, $lang_match)) {
                    $lang = $lang_match[1];
                }

                $clean = wp_strip_all_tags($inner);
                $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $code_class = $lang ? ' class="language-' . $lang . '"' : '';
                return '<pre><code' . $code_class . '>' . htmlspecialchars($clean, ENT_NOQUOTES, 'UTF-8') . '</code></pre>';
            },
            $content
        );
    }
}
