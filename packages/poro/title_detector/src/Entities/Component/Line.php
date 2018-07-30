<?php
/**
 * Created by PhpStorm.
 * User: tinyporo
 * Date: 09/04/2018
 * Time: 15:34
 */

namespace Poro\TitleDetector\Entities\Component;

use Poro\TitleDetector\Entities\Page;
use Symfony\Component\DomCrawler\Crawler;

class Line extends Component
{
    public $font_size;

    public $bold = false;

    const BOLD_PATTERN = '/(\s*(<b>)(\s*[^\s]\s*)+(<\/b>)\s*)+/ui';

    public function __construct($content, $html_content, $top, $left, $right, $bottom, $font_size = 0, $page) {
        $this->text_content = $content;
        $this->html_content = $html_content;

        if(preg_match(self::BOLD_PATTERN, $html_content)) $this->bold = true;

        parent::__construct($top, $left, $right, $bottom, $page);

        $this->font_size = $font_size;
    }

    public static function fromNode(Crawler $node, Page $page = null){
        $html_content = self::standardContent($node->html());
        $content = self::standardContent($node->text());

        if(!$content) return false;
        if(preg_match('/^\s*$/',$content)) return false;

        $top = intval($node->attr('top'));
        $left = intval($node->attr('left'));
        $bottom = $top + intval($node->attr('height'));
        $right = $left + intval($node->attr('width'));
        $font = $page->document->getFontSize(intval($node->attr('font')));

        $line = new Line( $content, $html_content, $top, $left, $right, $bottom, $font, $page);

        return $line;
    }

    public static function standardContent($content){
        $content = str_replace('Ƣ', 'Ư', $content);
        $content = str_replace('ƣ', 'ư', $content);

        $content = preg_replace('/^\s+/', '', $content);
        $content = preg_replace('/\s+$/', '', $content);

        return $content;
    }
}