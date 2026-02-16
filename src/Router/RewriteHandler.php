<?php
/**
 * URL rewrite handler for markdown requests.
 *
 * @package ForTheBots
 */

namespace ForTheBots\Router;

if (!defined('ABSPATH')) {
    exit;
}

use WP_Post;
use ForTheBots\Output\ContentRenderer;
use ForTheBots\Plugin;

/**
 * Handles URL rewriting and markdown request processing.
 */
class RewriteHandler {

    /**
     * Cached post for markdown request (Nginx compatibility).
     *
     * @var WP_Post|null
     */
    private ?WP_Post $markdown_post = null;

    /**
     * Register all hooks for URL routing.
     *
     * @return void
     */
    public function register(): void {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('parse_request', [$this, 'parse_markdown_url']);
        add_filter('redirect_canonical', [$this, 'prevent_markdown_redirect'], 10, 2);
        add_action('template_redirect', [$this, 'handle_format_parameter'], 1);
        add_action('template_redirect', [$this, 'handle_accept_negotiation'], 1);
        add_action('template_redirect', [$this, 'handle_markdown_request'], 1);
    }

    /**
     * Add rewrite rules for .md URLs.
     *
     * @return void
     */
    public function add_rewrite_rules(): void {
        add_rewrite_rule(
            '(.+?)\.md$',
            'index.php?pagename=$matches[1]&for_the_bots_request=1',
            'top'
        );
    }

    /**
     * Prevent canonical redirect for .md URLs.
     *
     * @param string $redirect_url  The redirect URL.
     * @param string $requested_url The requested URL.
     * @return string|false The redirect URL or false to prevent redirect.
     */
    public function prevent_markdown_redirect($redirect_url, $requested_url) {
        if (get_query_var('for_the_bots_request')) {
            return false;
        }
        return $redirect_url;
    }

    /**
     * Parse markdown URLs directly from REQUEST_URI.
     *
     * @param \WP $wp WordPress environment instance.
     * @return void
     */
    public function parse_markdown_url(\WP $wp): void {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if ($path === null || $path === '') {
            return;
        }

        if (!preg_match('/^\/(.+)\.md$/', $path, $matches)) {
            return;
        }

        $path = $matches[1];
        $clean_url = home_url('/' . $path);
        $post_id   = url_to_postid($clean_url);

        $post = null;
        if ($post_id) {
            $post = get_post($post_id);
        } else {
            $post = $this->resolve_post_by_path($path);
        }

        if (!$post || $post->post_status !== 'publish') {
            return;
        }

        if (!Plugin::is_supported_post_type($post->post_type)) {
            return;
        }

        $this->markdown_post = $post;

        $wp->query_vars['p'] = $post->ID;
        $wp->query_vars['for_the_bots_request'] = '1';
        unset($wp->query_vars['pagename']);
    }

    /**
     * Add custom query vars.
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public function add_query_vars(array $vars): array {
        $vars[] = 'for_the_bots_request';
        $vars[] = 'format';
        return $vars;
    }

    /**
     * Resolve post by path when url_to_postid returns 0.
     *
     * @param string $path URL path without leading slash or .md extension.
     * @return WP_Post|null The post object or null if not found.
     */
    private function resolve_post_by_path(string $path): ?WP_Post {
        $post = null;

        if (in_array('page', Plugin::get_supported_post_types(), true)) {
            $page = get_page_by_path($path, OBJECT, 'page');
            if ($page instanceof WP_Post) {
                $post = $page;
            }
        }

        $post = apply_filters('for_the_bots_resolve_post_by_path', $post, $path);

        return $post instanceof WP_Post ? $post : null;
    }

    /**
     * Handle format query parameter fallback.
     *
     * @return void
     */
    public function handle_format_parameter(): void {
        if (get_query_var('for_the_bots_request')) {
            return;
        }

        $format = get_query_var('format');
        if ($format !== 'markdown') {
            return;
        }

        if (!is_singular()) {
            return;
        }

        $post = get_queried_object();

        if (!$post instanceof WP_Post) {
            return;
        }

        if (!Plugin::is_supported_post_type($post->post_type)) {
            return;
        }

        if (get_post_status($post) !== 'publish') {
            return;
        }

        if (post_password_required($post)) {
            status_header(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'This content is password protected.';
            exit;
        }

        $renderer = new ContentRenderer();
        $markdown = $renderer->render($post);

        $this->set_response_headers($post, $markdown);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markdown output; Content-Type is text/markdown, not HTML.
        echo $markdown;
        exit;
    }

    /**
     * Handle markdown requests.
     *
     * @return void
     */
    public function handle_markdown_request(): void {
        if (!get_query_var('for_the_bots_request')) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        if (!preg_match('/\.md$/', $request_uri) && preg_match('/\.md$/i', $request_uri)) {
            return;
        }

        if (preg_match('/\.md\/$/', $request_uri)) {
            $path = wp_parse_url($request_uri, PHP_URL_PATH);
            $path = $path !== null && $path !== false ? rtrim($path, '/') : '';
            $query = wp_parse_url($request_uri, PHP_URL_QUERY);
            $redirect_url = home_url($path);
            if (!empty($query)) {
                $redirect_url .= '?' . $query;
            }
            wp_safe_redirect(esc_url_raw($redirect_url), 301);
            exit;
        }

        $post = $this->markdown_post ?? get_queried_object();

        if (!$post instanceof WP_Post) {
            return;
        }

        if (!Plugin::is_supported_post_type($post->post_type)) {
            return;
        }

        if (get_post_status($post) !== 'publish') {
            return;
        }

        if (post_password_required($post)) {
            status_header(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'This content is password protected.';
            exit;
        }

        $renderer = new ContentRenderer();
        $markdown = $renderer->render($post);

        $this->set_response_headers($post, $markdown);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markdown output; Content-Type is text/markdown, not HTML.
        echo $markdown;
        exit;
    }

    /**
     * Set all required HTTP headers for markdown response.
     *
     * @param WP_Post $post     The post being served.
     * @param string  $markdown The rendered markdown content.
     * @return void
     */
    private function set_response_headers(WP_Post $post, string $markdown): void {
        status_header(200);
        header('Content-Type: text/markdown; charset=UTF-8');
        header('Vary: Accept');
        $canonical_url = get_permalink($post);
        header('Link: <' . $canonical_url . '>; rel="canonical"');
        header('X-Content-Type-Options: nosniff');
        header('X-Markdown-Tokens: ' . (int) (strlen($markdown) / 4));
    }

    /**
     * Handle Accept header content negotiation.
     *
     * @return void
     */
    public function handle_accept_negotiation(): void {
        if (get_query_var('for_the_bots_request')) {
            return;
        }

        if (!is_singular()) {
            return;
        }

        $accept = isset($_SERVER['HTTP_ACCEPT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT'])) : '';
        if (strpos($accept, 'text/markdown') === false) {
            return;
        }

        $canonical = $this->get_current_canonical_url();
        if (!$canonical) {
            return;
        }

        $md_url = esc_url_raw(rtrim($canonical, '/') . '.md');
        if ($md_url === '') {
            return;
        }
        header('Vary: Accept');
        wp_safe_redirect($md_url, 303);
        exit;
    }

    /**
     * Get canonical URL for current content.
     *
     * @return string|null The canonical URL or null if not determinable.
     */
    private function get_current_canonical_url(): ?string {
        if (is_singular()) {
            $post = get_queried_object();
            return $post ? get_permalink($post) : null;
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if (!$term) {
                return null;
            }
            $link = get_term_link($term);
            return is_wp_error($link) ? null : $link;
        }

        if (is_author()) {
            return get_author_posts_url(get_queried_object_id());
        }

        if (is_date()) {
            if (is_year()) {
                return get_year_link(get_query_var('year'));
            }
            if (is_month()) {
                return get_month_link(get_query_var('year'), get_query_var('monthnum'));
            }
            if (is_day()) {
                return get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
            }
        }

        return null;
    }

    /**
     * Register rewrite rules statically.
     *
     * @return void
     */
    public static function register_rules(): void {
        $handler = new self();
        $handler->add_rewrite_rules();
    }
}
