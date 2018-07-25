<?php
/**
 * Created by PhpStorm.
 * User: tinyporo
 * Date: 16/04/2018
 * Time: 17:39
 */

namespace Poro\TitleDetector\Entities\Component;


use Poro\TitleDetector\Entities\Page;

abstract class Component
{
    public $html_content;
    public $text_content;

    public $top;
    public $left;
    public $right;
    public $bottom;

    public $height;
    public $width;

    public $page;

    public function __construct($top, $left, $right, $bottom, $page) {
        $this->top = $top;
        $this->left = $left;
        $this->right = $right;
        $this->bottom = $bottom;

        $this->height = $bottom-$top;
        $this->width = $right-$left;

        if ($page instanceof Page){
            $this->page($page);
        }
    }

    public function page(Page $page = null){
        if($page != null){
            $this->page = $page;
        }
        return $this->page;
    }
}