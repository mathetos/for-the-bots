<?php
/**
 * HTML to Markdown converter wrapper.
 *
 * @package ForTheBots
 */

namespace ForTheBots\Converter;

if (!defined('ABSPATH')) {
    exit;
}

use League\HTMLToMarkdown\HtmlConverter;

/**
 * Wrapper class for converting HTML content to Markdown.
 */
class MarkdownConverter {

    /**
     * @var MarkdownConverter|null
     */
    private static $instance = null;

    /**
     * @var HtmlConverter
     */
    private $converter;

    /**
     * @return self
     */
    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->converter = new HtmlConverter([
            'header_style'    => 'atx',
            'strip_tags'      => true,
            'remove_nodes'    => 'script style iframe',
            'hard_break'      => false,
            'list_item_style' => '-',
        ]);
    }

    /**
     * @param string $html
     * @return string
     */
    public function convert(string $html): string {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        return $this->converter->convert($html);
    }
}
