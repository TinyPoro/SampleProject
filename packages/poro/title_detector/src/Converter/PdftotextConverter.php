<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/6/17
 * Time: 14:45
 */

namespace Poro\TitleDetector\Converter;

class PdftotextConverter extends CanRunCommand {
    use FileUtils;
	
	public $tmp_folder;
	public $tmp_path;
	protected $process;
	public $prefix_file = 'pdftohtml_';
	protected $process_options = [
		'-nodrm' => true,
		'-zoom' => 1,
		'-i' => true,
		'-c' => true,
        '-stdout' => true,
        '-xml' => true,
	];
	
	public function __construct($bin = 'pdfinfo', $cache = '') {
		$this->tmp_folder = $this->newTmpFolder();
		parent::__construct($bin);
	}
	
	private function checkWritable(){
		if(!file_exists($this->tmp_folder)){
			@mkdir($this->tmp_folder);
            chmod($this->tmp_folder, 0777);
        }

		if(!file_exists($this->tmp_folder)){
			throw new \Exception("Can not read/write tmp folder");
		}
	}

    public function getXmlPages($path, $page = 0){
        /** @var $file \File*/
        $file_name = \File::name($path);
        $this->checkWritable();
        if ($page > 0){
            $this->options('-l', $page);
        }
        $file_name = str_slug($file_name);
        $this->tmp_path = $this->tmp_folder . DIRECTORY_SEPARATOR . $file_name . ".pdf";
        copy($path, $this->tmp_path);
        $command = $this->buildCommand($this->tmp_path);
        $this->run($command);

        $xml_path = $this->tmp_folder . DIRECTORY_SEPARATOR . $file_name . ".xml";
        $this->correctXml($xml_path);

        $content = file_get_contents($xml_path);
        $content = $this->utf8_for_xml($content);
        return $content;
    }

    function utf8_for_xml($string)  {
        return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    }

    function correctXml($xml_path){
        libxml_use_internal_errors(true);
        $sxe = simplexml_load_file($xml_path);
        if (false === $sxe) {
            $delete_lines = [];

            foreach (libxml_get_errors() as $error) {
                if (preg_match('/(?<=line\s)\d+/', $error->message, $matches)) $delete_lines[] = $matches[0];
            }

            $delete_lines = array_unique($delete_lines);

            $this->deleteLines($xml_path, $delete_lines);
        }
    }

    public function deleteLines($fname, $delete_lines){
        $line_no = 0;
        $out = '';

        $lines = file($fname);
        foreach($lines as $line) {
            $line_no++;
            if(in_array($line_no, $delete_lines)) $out .= "\n";
            else $out .= $line;
        }

        $f = fopen($fname, "w");
        fwrite($f, $out);
        fclose($f);
    }

	/** Helper functions */

	/**
	 * Delete cache file before this time
	 *
	 * @param PdftotextConverter $instant
	 * @param $time
	 */
	public static function clean(PdftotextConverter $instant = null, $time = null){
		$instant = $instant == null ? new self() : $instant;
		$folder = glob($instant->tmp_folder . DIRECTORY_SEPARATOR . "*");
		foreach ($folder as $file){
			@unlink($file);
		}
		@rmdir($instant->tmp_folder);
	}

	function __destruct() {
//		self::clean($this);
	}


}