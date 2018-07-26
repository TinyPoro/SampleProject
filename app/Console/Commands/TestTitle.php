<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Poro\TitleDetector\TitleDetector;

class TestTitle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:title
    {--file= : Lựa chọn file để chạy }
    {--folder= : Lựa chọn folder để chạy }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $folder = $this->option('folder');
        $file = $this->option('file');

        if($folder){
            $folder = storage_path($folder);
            $files = scandir($folder);
            $files = array_diff($files, array('..', '.', '.gitignore'));
        }else{
            $folder = storage_path('input');
            $files=[$file.'.pdf'];
        }


        foreach($files as $file){
            $file_name = $folder.'/'.$file;
            echo "File: $file_name \n";
            $title_detector = new TitleDetector($file_name);
            $title_detector->detectTitle();
        }
    }
}
