<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 7/24/18
 * Time: 10:16 AM
 */

namespace Poro\TitleDetector;

use Poro\TitleDetector\Converter\PdfInfo;
use Poro\TitleDetector\Converter\PdftotextConverter;
use Poro\TitleDetector\Entities\Document;

class TitleDetector
{
    public $converter;

    public $file_path;
    public $file_name;

    public $document;

    public $error = false;

    public function __construct($file){
        $this->file_path = $file;

        try{
            $this->document = new Document($file);
        }catch (\Exception $e){
            echo "Resolve detector class::" . $e->getMessage()."\n";
        }

        $this->getFileName();

        try{
            $this->handleDocument();
        }catch (\Exception $e){
            $this->error = true;
            echo "Handle document error::" . $e->getMessage()."\n";
        }
    }

    public function getFileName(){
//        $pdf_info = new PdfInfo();
//        dd($pdf_info->read($this->file_path));
    }

    public function handleDocument(){
        try {
            //chuyển từ pdf thành xml
            $xml = $this->convertPdfTohtml();

            //đọc file xml
            $this->genEntities($xml);

            if($this->canNotGenText()) return;
        }catch (\Exception $exception){
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
        if($this->error){
            echo "Document can not convert!\n";
            return;
        }

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
        $pages = $this->converter->getXmlPages($this->file_path);

        return $pages;
    }
}