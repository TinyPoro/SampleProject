<?php
/**
 * Created by PhpStorm.
 * User: tinyporo
 * Date: 09/04/2018
 * Time: 15:34
 */

namespace Poro\TitleDetector\Entities\Component;

class Box extends Component
{
    public $lines;

    public $distance=false;
    public $average_height=false;
    public $average_font_size=false;

    public $same_left=false;

    public $bold=false;

    public $tf_idf=0;

    public $center = false;

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

        if(!$this->bold && count($this->lines)==1){
            if($line->bold) $this->bold = true;
        }

        if(!$this->average_height) $this->average_height = $line->height;
        if(!$this->average_font_size) $this->average_font_size = $line->font_size;
    }

    public function setDistance($distance){
        $this->distance = $distance;
    }

    public function setSameLeft($left){
        $this->same_left = $left;
    }
}