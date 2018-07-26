<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/7/17
 * Time: 09:40
 */

namespace Poro\TitleDetector\Converter;


use Carbon\Carbon;

class PdfInfo extends CanRunCommand {
	
	/**
	 * PdfInfo constructor.
	 *
	 * @param string $bin
	 */
	public function __construct($bin = 'pdfinfo') {
		parent::__construct($bin);
	}
	
	public function read($path){
		$command = $this->buildCommand($path);dd($command);
		$this->run($command);
		return $this->parseResult();
	}
	
	private function parseResult(){
		$output = $this->output();
		$lines = preg_split("/\n/", $output);
		$output = [];
		foreach ($lines as $line){
			$exploded = preg_split("/\:\s+/", $line);
			if(count($exploded) == 2){
				if(stripos($exploded[0], 'date')){
					$value = Carbon::parse($exploded[1]);
				}else{
					$value = $exploded[1];
				}
				$key = mb_strtolower(str_replace(" ", "_", $exploded[0]));
				$output[$key] = $value;
			}
		}
		
		return $output;
	}
	
}