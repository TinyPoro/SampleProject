<?php
/**
 * Created by PhpStorm.
 * User: tinyporo
 * Date: 09/04/2018
 * Time: 15:34
 */

namespace Poro\TitleDetector\Entities;

use LanguageDetection\Language;
use Poro\Tf_Idf\TF_IDF;
use Poro\TitleDetector\Entities\Component\Box;
use Poro\TitleDetector\Entities\Component\Line;
use Symfony\Component\DomCrawler\Crawler;

class Document
{
    public $path;
    public $language;

    public $margin_top;
    public $margin_bottom;

    /** @var Page */
    public $pages;

    /** @var Crawler */
    public $xml;

    public $detector;

    public $font_sizes;
    public $normal_font_size;
    public $max_font_size;

    public $docId;

    public function __construct($path)
    {
        $this->path = $path;
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

        try{
            $this->loadFontSizeFromXml();
            $this->importPagesFromXml();
            $this->detectLanguage();
            $this->detectNormalFontSize();
            $this->alignBox();
            $this->detectMaxFontSize();
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    private function loadFontSizeFromXml(){
        $this->xml->filter('page')->each( function ( Crawler $node ) {
            $node->filter( 'fontspec')->each( function ( Crawler $node){
                $font_id = intval($node->attr('id'));
                $font_size = intval($node->attr('size'));

                $this->font_sizes[] = [
                    'id' => $font_id,
                    'size' => $font_size
                ];
            });
        });

        if(count($this->font_sizes) == 0) throw new \Exception('Can not get document font size');
    }

    public function getFontSize($fond_id){
        foreach($this->font_sizes as $font_size){
            if($font_size['id'] == $fond_id) return $font_size['size'];
        }

        return 0;
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

    public function alignBox(){
        foreach ($this->pages as $page){
            foreach($page->boxes as $k => $box){
                $_left = $box->left - $page->margin_left;
                $_right = $page->margin_right - $box->right;

                if(abs( $_left - $_right) <= 10){// nêu lề 2 bên có sai số cho phép
                    if(max($_left, $_right) > 10){
                        $box->center = true;
                    }
                }
            }
        }
    }

    private function detectLanguage(){
        $text = '';

        foreach($this->pages as $page){
            foreach($page->lines as $line){
                $text .= $line->text_content.' ';
            }
        }

        $language = new Language();
        $language->setMaxNgrams(9000);
        $result = array_keys($language->detect($text)->bestResults()->close());

        if(count($result) == 0) throw new \Exception("Can not detect language");
        $this->language = $result[0];

        try{
            $this->detector = $this->resoleDetectorClass($this->language);
        }catch (\Exception $e){
            throw new \Exception("resolve detector class::" . $e->getMessage());
        }
    }

    private function detectNormalFontSize(){
        $font_sizes = [];

        foreach($this->pages as $page){
            foreach($page->lines as $line){
                if(!$line->font_size) continue;

                $font_sizes[] = $line->font_size;
            }
        }

        $font_sizes = array_count_values($font_sizes);
        if(count($font_sizes) > 0) $max = max($font_sizes);
        else return;

        foreach($font_sizes as $font => $value){
            if($value == $max) $this->normal_font_size[] = $font;
        }

        $this->normal_font_size = max($this->normal_font_size);
    }

    public function detectMaxFontSize(){
        if(count($this->pages) == 0) throw new \Exception('Can not get max font size');

        $box_font_sizes = [];


        foreach($this->pages as $page){
            foreach($page->boxes as $box){
                $box_font_sizes[] = $box->average_font_size;
            }
        }

        if(count($box_font_sizes) == 0) throw new \Exception("Document can not get box!");

        //lấy chiều cao lớn nhất
        $this->max_font_size = max($box_font_sizes);
    }

    public function push(Page $page){
        $this->pages[] = $page;
    }

    public function detectTitle(){
        $this->calTfIdf();

        $max_box = null;

        foreach ($this->pages as $page){
            if($page->number > 2) break;

            $max_box = $this->findTitleBox($page);

            if($max_box) break;
        }

        if($max_box) dump($max_box->text_content."\n");
        else echo "Can not detect title!\n";
    }

    protected function calTfIdf(){
        $tf_idf = new TF_IDF($this->language);

        $text = '';

        foreach ($this->pages as $page){
            foreach($page->lines as $line){
                /** @var $line Line */
                $text .= $line->text_content.' ';
            }
        }

        $this->docId = $tf_idf->addDocText($text);

        foreach ($this->pages as $page){
            if($page->number > 2) break;

            foreach($page->boxes as $box){
                /** @var $box Box */
                $terms = explode(' ', $this->standardText($box->text_content));
                $terms = array_filter($terms);

                foreach($terms as $term){
                    $box->tf_idf += $tf_idf->getTfIdf($term, $this->docId);
                }

                $box->tf_idf /= str_word_count($box->text_content);
            }
        }
    }

    public function standardText($text){
        $text = mb_strtolower($text);
        $text = trim($text);

        $text = str_replace(['-', '.', ';', ',', '?', ':', '"', '!', '(', ')', '[', ']', '_', '-', '\'', '{', '}', '/'], '', $text);
        $text = preg_replace('/\d+/', '', $text);
        $text = preg_replace('/\s{2,}/', ' ', $text);

        return $text;
    }

    public function findTitleBox(Page $page){
        $detector = $this->detector;

        //lọc các box không hợp lệ
        $boxes = array_filter($page->boxes, function($box) use($detector) {return $detector->check($box->text_content)['success'] == 'true';});

        $max_tf_idf = null;

        foreach($boxes as $box) {
            if(!$max_tf_idf || $box->tf_idf > $max_tf_idf) $max_tf_idf = $box->tf_idf;
        }

        if(!$max_tf_idf) throw new \Exception('Can not get max tf idf!');

        //lấy các box có chiều cao lớn nhất
        $boxes = array_filter($boxes, function($box) use($max_tf_idf) {return $this->filterBox($box, $max_tf_idf);});

        usort($boxes, array($this, 'sortBox'));

        $max_box = null;

        foreach($boxes as $box){
            if(!$max_box || $box->tf_idf > $max_box->tf_idf) $max_box = $box;
        }

        return $max_box;
    }

    protected function filterBox(Box $box, $max_tf_idf){
        if(str_word_count($box->text_content) < 2) return false;

        if(preg_match('/^[a-záàãảạăắằẵẳặâấầẫảạđéèẻẽẹêểếềệễíìĩỉịôốổồỗộơớờởỡợóòỏõọuúùũủụưứừửựữýỳỷỹỵ]/u', $box->text_content)) return false;

        if($box->tf_idf >= $max_tf_idf - 0.5) return true;

        if($box->average_font_size > ($this->max_font_size*0.7)){
            if($box->center) return true;

            if($box->bold) return true;

            if($box->average_font_size > $this->normal_font_size) return true;
        }

        return false;
    }

    public function sortBox(Box $b1, Box $b2) {
        $average_font_size1 = $b1->average_font_size;
        $average_font_size2 = $b2->average_font_size;

        if($average_font_size1 == $average_font_size2) return ($b1->bottom < $b2->bottom) ? -1 : 1;

        return ($average_font_size1 < $average_font_size2) ? 1 : -1;
    }
}