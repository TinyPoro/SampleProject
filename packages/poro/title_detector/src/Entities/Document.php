<?php
/**
 * Created by PhpStorm.
 * User: tinyporo
 * Date: 09/04/2018
 * Time: 15:34
 */

namespace Poro\TitleDetector\Entities;

use Symfony\Component\DomCrawler\Crawler;

class Document
{
    public $path;
    public $language;

    public $margin_top;
    public $margin_bottom;

    /** @var Page */
    public $pages;

    public $xml;

    public $detector;

    public function __construct($path, $language = 'id')
    {
        $this->path = $path;
        $this->language = $language;

        try{
            $this->detector = $this->resoleDetectorClass($language);
        }catch (\Exception $e){
            \Log::error("resolve detector class::" . $e->getMessage());
            throw new \Exception("resolve detector class::" . $e->getMessage());
        }
    }

    protected function resoleDetectorClass($language){
        $class = '\\Poro\\TitleDetector\\TitleDetector\\' . ucfirst( $language) . 'Detector';

        if(!class_exists( $class)){
            throw new \Exception("Not support detector for language " . $language);
        }
        $detector = new $class;
        return $detector;
    }

    public function loadXML($xml){
        $this->xml = new Crawler();
        $this->xml->addXmlContent($xml);

        $this->importPagesFromXml();
    }

    private function importPagesFromXml(){

        $this->xml->filter('page')->each( function ( Crawler $node ) {
            $top = intval($node->attr( 'top'));
            $left = intval($node->attr( 'left'));
            $height = intval($node->attr( 'height'));
            $width = intval($node->attr( 'width'));

            $page = new Page( $top, $width, $height, $left);
            $page->setDocument($this);
            $page->setNumberPage($node->attr('number'));
            $page->loadLinesFromNode($node);

            $this->push($page);
        });
    }

    public function push(Page $page){
        $this->pages[] = $page;
    }

    public function detectTitle(){
        $max_box = null;

        foreach ($this->pages as $page){
            $max_box = $this->findMaxHeightBox($page);

            if($max_box) break;
        }

        if($max_box) dd($max_box->text_content);
        else throw new \Exception('Can not detect title!');
    }

    public function findMaxHeightBox(Page $page){
        $box_heights = [];

        foreach($page->boxes as $box){
            $box_heights[] = $box->average_height;
        }

        //lấy chiều cao lớn nhất
        $max_height = max($box_heights);

        //lấy chiều cao thấp nhất
        $count_height = array_count_values($box_heights);
        if(count($count_height) > 1) $min_height = min($box_heights);
        else $min_height = 0;

        //lấy các box có chiều cao lớn nhất
        $boxes = array_filter($page->boxes, function($box) use($max_height, $min_height) {return $box->average_height > ($max_height*0.8) && $box->average_height > $min_height;});

        foreach($boxes as $box){
            $check = $this->detector->check($box->text_content);
            if($check['success'] == 'false') continue;

            if(str_word_count($box->text_content) < 5) continue;
            if(strlen($box->text_content) > 70) continue;

            if(preg_match('/^[a-záàãảạăắằẵẳặâấầẫảạđéèẻẽẹêểếềệễíìĩỉịôốổồỗộơớờởỡợóòỏõọuúùũủụưứừửựữýỳỷỹỵ]/u', $box->text_content)) continue;

            return $box;
        }

        return null;
    }
}