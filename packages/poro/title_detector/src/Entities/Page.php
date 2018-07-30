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
                //từ trang 3 trở đi không thêm box
                if($this->number > 2) $this->appendLine($line);
                else{
                    if($this->endBox($line)){
                        $this->addBox();

                        $this->addLine($line);
                    }else{
                        $this->addLine($line);
                    }
                }
            }else{
                if($this->number > 2) return;

                $bottom = intval($node->attr('top')) + intval($node->attr('height'));

                if(count($this->cur_box->lines) > 0) {
                    if($bottom == last($this->cur_box->lines)->bottom) $temp = false;
                    else $this->addBox();
                }
            }

            if($temp) $this->last_line = $line;
        });

        if(count($this->cur_box->lines) > 0) $this->addBox();

        //tính toàn margin page
        $this->computeMargin();

        //lọc các box không có từ
        $this->boxes = array_filter($this->boxes, function($box){return str_word_count($box->text_content) > 1;});

        //sắp xếp lại câu theo thứ tự
        if(count($this->main_left) == 1) uasort($this->lines, array($this, 'sortComponent'));
        $this->lines = array_values($this->lines);
    }

    public function endBox(Line $line){
        if(!$this->last_line) return false;

        //nếu in đậm thì ghép
        if(!$line->bold && $this->cur_box->bold) return true;

        //nếu top/bottom chênh không quá 1 đơn vị thì ghép
        if($this->approxiateIn($line->top, $this->last_line->top, 1)) return false;
        if($this->approxiateIn($line->bottom, $this->last_line->bottom, 1)) return false;

        //nếu fontsize chênh không quá 2 đơn vị thì ghép
        if($this->approxiateIn($line->font_size, $this->last_line->font_size, 2)) return false;

        //nếu box hiện tại đang cùng left mà dòng tiếp lệch thì dừng
        if($this->cur_box->same_left){
            if($line->left != $this->cur_box->same_left) return true;
        }

        //nếu chênh quá 2 đơn vị chiều cao thì dừng
        if($this->cur_box->average_height) {
            if($this->approxiateOut($line->height, $this->cur_box->average_height, 2)) return true;
        }

        //nếu chênh quá 2 đơn vị font size thì dừng
        if($this->cur_box->average_font_size) {
            if($this->approxiateOut($line->font_size, $this->cur_box->average_font_size, 2)) return true;
        }

        $distance  = $line->bottom - $this->last_line->bottom;
        //nếu dòng quả nhỏ so với khoảng cách với dòng trước thì dừng
        if($distance > $line->height + 10) return true;

        //nếu box hiện tại không có khoảng cách thì tiếp
        if(!$this->cur_box->distance) return false;

        //nếu khoảng cách chênh không quá khoảng cách trung bình box 5 đơn vị thì tiếp
        if($this->approxiateIn($distance, $this->cur_box->distance, 5)) return false;

        //nếu in đậm thì ghép
        if($line->bold && $this->cur_box->bold) return false;

        return true;
    }

    public function approxiateIn($a, $b, $d){
        if($a >= $b - $d && $a <= $b + $d) return true;
        return false;
    }

    public function approxiateOut($a, $b, $d){
        if($a <= $b - $d || $a >= $b + $d) return true;
        return false;
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

    public function computeMargin(){
        foreach($this->lines as $key => $line) {
            if($this->margin_top > $line->top || $key == 0){
                $this->margin_top = $line->top;
            }
            if($this->margin_right < $line->right){
                $this->margin_right = $line->right;
            }
            if($this->margin_bottom < $line->bottom){
                $this->margin_bottom = $line->bottom;
            }
            if($this->margin_left > $line->left || $key == 0){
                $this->margin_left = $line->left;
            }
        };
    }

    public function sortComponent(Component $p1, Component $p2) {
        $bottom1 = $p1->bottom;
        $bottom2 = $p2->bottom;

        if($bottom1 == $bottom2) return ($p1->left < $p2->left) ? -1 : 1;

        return ($bottom1 < $bottom2) ? -1 : 1;
    }
}