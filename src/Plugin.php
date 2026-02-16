<?php
/**
 * Core plugin orchestrator.
 *
 * @package ForTheBots
 */

namespace ForTheBots;

if (!defined('ABSPATH')) {
    exit;
}

use ForTheBots\Discovery\AlternateLinkHandler;
use ForTheBots\Router\RewriteHandler;

/**
 * Main plugin class implementing singleton pattern.
 */
class Plugin {

    /**
     * Plugin instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Router instance.
     *
     * @var RewriteHandler
     */
    private $router;

    /**
     * Discovery handler instance.
     *
     * @var AlternateLinkHandler
     */
    private $discovery;

    /**
     * Get plugin instance.
     *
     * @return Plugin
     */
    public static function instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct() {
        $this->router = new RewriteHandler();
        $this->router->register();

        $this->discovery = new AlternateLinkHandler();
        $this->discovery->register();
    }

    /**
     * Plugin activation callback.
     *
     * @return void
     */
    public static function activate(): void {
        RewriteHandler::register_rules();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation callback.
     *
     * @return void
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * Get supported post types for markdown output.
     *
     * @return array List of supported post type names.
     */
    public static function get_supported_post_types(): array {
        $default_types = ['post', 'page'];
        return apply_filters('for_the_bots_supported_post_types', $default_types);
    }

    /**
     * Check if a post type is supported for markdown output.
     *
     * @param string $post_type The post type to check.
     * @return bool True if supported, false otherwise.
     */
    public static function is_supported_post_type(string $post_type): bool {
        return in_array($post_type, self::get_supported_post_types(), true);
    }
}
