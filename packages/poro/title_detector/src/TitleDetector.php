<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 7/24/18
 * Time: 10:16 AM
 */

namespace Poro\TitleDetector;

use Poro\TitleDetector\Converter\PdftotextConverter;
use Poro\TitleDetector\Entities\Component\Line;
use Poro\TitleDetector\Entities\Document;
use Poro\TitleDetector\Entities\Page;
use Symfony\Component\DomCrawler\Crawler;

class TitleDetector
{
    public $converter;
    public $file_path;

    public $document;

    public function __construct($file, $language = 'id'){
        $this->file_path = $file;
        $this->document = new Document($file, $language);

        $this->handleDocument();
    }

    public function handleDocument(){
        try {
            //chuyển từ pdf thành xml
            $xml = $this->convertPdfTohtml();

            //đọc file xml
            $this->genEntities($xml);

            if($this->canNotGenText()) return;
        }catch (\Exception $exception){
            \Log::info($exception->getMessage());
            throw new \Exception($exception->getMessage());
        }
    }

    public function canNotGenText(){
        foreach ($this->document->pages as $page){
            if(count($page->lines) > 0) return false;
        }

        return true;
    }

    public function detectTitle(){
        $this->document->detectTitle();
    }

    public function genEntities($xml){
        $this->document->loadXML($xml);
    }

    public function convertPdfToHtml(){
        $this->converter = new PdftotextConverter('pdftohtml');
        $name_file = \File::name($this->file_path);
        $this->converter->prefix_file = str_replace(' ', '_', $name_file);
        if (!file_exists($this->file_path)){
            throw new \Exception($this->file_path . ' not exist');
        }
        $pages = $this->converter->getHtmlPages($this->file_path);

        return $pages;
    }
}