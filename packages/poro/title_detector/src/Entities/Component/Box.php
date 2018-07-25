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

class Box extends Component
{
    public $lines;

    public $distance=false;
    public $average_height=false;

    public $same_left=false;

    public function __construct($page) {
        parent::__construct(0, 0, 0, 0, $page);
    }

    public function addLine(Line $line){
        $this->lines[] = $line;

        $this->text_content .= $line->text_content.' ';

        $this->top = min($this->top, $line->top);
        $this->left = min($this->left, $line->left);
        $this->right = max($this->right, $line->right);
        $this->bottom = max($this->bottom, $line->bottom);

        if(!$this->average_height) $this->average_height = $line->height;
    }

    public function setDistance($distance){
        $this->distance = $distance;
    }

    public function setSameLeft($left){
        $this->same_left = $left;
    }
}