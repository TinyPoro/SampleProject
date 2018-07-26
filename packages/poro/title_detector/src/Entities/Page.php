<?php
/**
 * Created by PhpStorm.
 * User: tinyporo
 * Date: 09/04/2018
 * Time: 15:34
 */

namespace Poro\TitleDetector\Entities;

use Poro\TitleDetector\Entities\Component\Box;
use Poro\TitleDetector\Entities\Component\Component;
use Poro\TitleDetector\Entities\Component\Line;
use Symfony\Component\DomCrawler\Crawler;

class Page
{
    public $top;
    public $right;
    public $bottom;
    public $left;

    public $width;
    public $height;

    public $margin_top;
    public $margin_right;
    public $margin_bottom;
    public $margin_left;

    public $main_left;
    /** @var Line[] */
    public $lines=[];
    public $boxes=[];

    public $document;

    public $number;

    const LIST_SIGN_PATTERN = '/^\s*(|||\*||(&bull;)|(&ndash;)|-|\+)+\s*$/u';

    public function __construct($top, $width, $height, $left) {
        $this->width = $width;
        $this->height = $height;

        $this->top = $top;
        $this->right = $left + $width;
        $this->bottom = $top + $height;
        $this->left = $left;
    }

    public function setNumberPage($page_number){
        $this->number = $page_number;
    }

    public function setDocument(Document $document) {
        $this->document = $document;
    }

    /** @var Box */
    public $cur_box;

    /** @var Line  */
    public $last_line = false;

    public function loadLinesFromNode(Crawler $node){
        $this->cur_box = new Box($this);

        $node->filter( 'text')->each( function ( Crawler $node ){
            $temp = true;

            $line = Line::fromNode( $node, $this);

            if($line) {
                if($this->endBox($line)){
                    $this->addBox();

                    $this->addLine($line);
                }else{
                    $this->addLine($line);
                }
            }else{
                $bottom = intval($node->attr('top')) + intval($node->attr('height'));



                if(count($this->cur_box->lines) > 0) {
                    if($bottom == last($this->cur_box->lines)->bottom) $temp = false;
                    else $this->addBox();
                }
            }

            if($temp) $this->last_line = $line;
        });

        if(count($this->cur_box->lines) > 0) $this->addBox();

        //sắp xếp lại câu theo thứ tự
        if(count($this->main_left) == 1) uasort($this->lines, array($this, 'sortComponent'));
        $this->lines = array_values($this->lines);
    }

    public function endBox(Line $line){
        if(!$this->last_line) return false;

        if($line->top >= $this->last_line->top - 1 && $line->top <= $this->last_line->top + 1) return false;
        if($line->bottom >= $this->last_line->bottom - 1 && $line->bottom <= $this->last_line->bottom + 1) return false;

        if($line->font_id == $this->last_line->font_id) return false;

        if($this->cur_box->same_left){
            if($line->left != $this->cur_box->same_left) return true;
        }

        if($this->cur_box->average_height) {
            if($line->height <= ($this->cur_box->average_height - 2) || $line->height >= ($this->cur_box->average_height + 2)) return true;
        }

        $distance  = $line->bottom - $this->last_line->bottom;
        if($distance > $line->height + 10) return true;

        if(!$this->cur_box->distance) return false;

        if($distance >= ($this->cur_box->distance - 5) && $distance <= ($this->cur_box->distance + 5)) return false;

        return true;
    }

    public function addLine(Line $line){
        $this->appendLine($line);
        $this->cur_box->addLine($line);

        if(count($this->cur_box->lines) > 1){
            if($this->last_line){
                if(!$this->cur_box->distance){
                    $this->cur_box->setDistance($line->bottom - $this->last_line->bottom);
                }
            }

            if($line->left == $this->last_line->left) $this->cur_box->setSameLeft($line->left);
        }
    }

    public function appendLine(Line $line){
        $this->lines[] = $line;
    }

    public function addBox(){
        $this->appendBox($this->cur_box);
        $this->cur_box = new Box($this);
    }

    public function appendBox(Box $box){
        $this->boxes[] = $box;
    }

    public function sortComponent(Component $p1, Component $p2) {
        $bottom1 = $p1->bottom;
        $bottom2 = $p2->bottom;

        if($bottom1 == $bottom2) return ($p1->left < $p2->left) ? -1 : 1;

        return ($bottom1 < $bottom2) ? -1 : 1;
    }
}