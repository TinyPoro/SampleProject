<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/6/17
 * Time: 15:01
 */

namespace Poro\TitleDetector\Converter;

use Symfony\Component\Process\Process;

abstract class CanRunCommand {
	
	protected $process;
	protected $process_options = [];
	private $command;
	protected $bin;
	protected $timeout = 300;

	public function __construct($bin = '') {
		$this->bin($bin);
		$this->process = new Process('');
		$this->process->setTimeout($this->timeout);
	}
	
	
	protected function validateRun()
	{
		$status = $this->process->getExitCode();
		$error  = $this->process->getErrorOutput();
		
		if ($status !== 0 and $error !== '') {
			throw new \RuntimeException(
				sprintf(
					"The exit status code %s says something went wrong:\n stderr: %s\n stdout: %s\ncommand: %s.",
					$status,
					$error,
					$this->process->getOutput(),
					$this->command
				)
			);
		}
	}
	protected function buildCommand($append = ''){
		$command = $this->bin;
		$command .= " " . $this->buildOptions();
		return $command . $append;
	}
	
	protected function buildOptions(){
		$options = ' ';
		foreach($this->process_options as $k => $v){
			if($v !== false){
				$options .= $k . " ";
			}else{
				continue;
			}
			if($v !== true){
				$options .= $v . " ";
			}
		}
		
		return $options;
	}
	
	public function run($command)
	{
//		$this->command = escapeshellcmd($command);
        $this->command = $command;
		$this->process->setCommandLine($this->command);
		$this->process->run();
		$this->validateRun();
		
		return $this;
	}
	public function bin($bin = ''){
		if(!empty($bin)){
			$this->bin = $bin;
		}
		return $this->bin;
	}
	public function timeout($timeout = ''){
		if(!empty($timeout)){
			$this->timeout = $timeout;
		}
		return $this->timeout;
	}
	public function options($key = null, $value = null){
		if(is_array($key)){
			if($value === true){
				$this->process_options = $key;
			}else{
				$this->process_options = array_merge($this->process_options, $key);
			}
		}elseif ($key != null){
			if($value !== null){
				$this->process_options[$key] = $value;
			}
			return $this->process_options;
		}
		return $this->process_options;
	}
	public function output()
	{
		return $this->process->getOutput();
	}

    function detect()
    {
        // TODO: Implement detect() method.
    }
	
}