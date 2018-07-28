<?php
/**
 * Created by PhpStorm.
 * User: poro
 * Date: 7/25/16
 * Time: 09:40
 */

namespace Poro\TitleDetector\Converter;

trait FileUtils {

    private $tmp_files = [];
    /**
     * Tạo 1 file tạm tự động xóa
     *
     * @param null|string|resource $input
     *
     * @param bool $is_content xác định input là content hay path
     *
     * @param string $wm
     *
     * @return string
     */
    protected function newTmp($input = null, $is_content = true, $wm = 'w+'){

        $filename = tempnam(storage_path('tmp'), 'GldocToc');
        $this->tmp_files[] = $filename;
        if($input != null){
            if(is_resource($input)){
                $ft = fopen($filename, $wm);
                while($block = fread($input, 4096)){
                    fwrite($ft, $block);
                }
                fclose($ft);
            }elseif($is_content){
                file_put_contents($filename, $input);
            }else{
                $fi = fopen($input, 'rb');
                $ft = fopen($filename, 'wb');
                while($block = fread($fi, 4096)){
                    fwrite($ft, $block);
                }
                fclose($fi);
                fclose($ft);
            }
        }
        return $filename;
    }

    /**
     * @return string
     * @throws ConvertException
     */
    private function newTmpFolder($name = 'GldocConverter'){
        $filename = tempnam(storage_path('tmp'), $name);

        if (file_exists($filename)) { \File::delete($filename); }
        $filename = dirname($filename) . DIRECTORY_SEPARATOR . preg_replace('/\./', '_', basename($filename));
        if(\File::makeDirectory($filename, 0777, true) === false){
            mkdir($filename, '0777', true);
        }
        if (!is_dir($filename)) {
            throw new ConvertException("Can not create tmp folder");
        }
        $this->tmp_files[] = $filename;
        return $filename;
    }

    /**
     * Danh sách file tạm đã tạo
     * @return array
     */
    private function listTmp(){
        return $this->tmp_files;
    }

    /**
     * Xóa các file tạm được tạo bởi class hiện tại
     * @return array
     */
    private function clearTmp(){
        foreach($this->tmp_files as $file){
            if(\File::isDirectory($file)){
                \File::deleteDirectory($file, true);
            }else{
                \File::delete($file);
            }
        }
        $this->tmp_files = [];
        return $this->tmp_files;
    }


    function __destruct() {
        $this->clearTmp();
    }
}