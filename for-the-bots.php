<?php
/**
 * Plugin Name: For the Bots
 * Plugin URI: https://github.com/mathetos/for-the-bots
 * Description: Provides markdown versions of posts and pages via .md URLs. Fork of Markdown Alternate by Joost de Valk.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Matt Cromwell
 * Author URI: https://www.mattcromwell.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: for-the-bots
 *
 * Fork of Markdown Alternate (https://github.com/ProgressPlanner/markdown-alternate) by Joost de Valk.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FOR_THE_BOTS_FILE', __FILE__);
define('FOR_THE_BOTS_VERSION', '1.0.0');

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook(__FILE__, ['ForTheBots\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['ForTheBots\Plugin', 'deactivate']);

\ForTheBots\Plugin::instance();
