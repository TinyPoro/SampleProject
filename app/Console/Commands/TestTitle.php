<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
    {--folder= : Lựa chọn folder để chạy }
    {--max= : Số file tối đa sẽ chạy }';

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
        $max = $this->option('max');

        if($folder){
            $folder = storage_path($folder);
            $files = scandir($folder);
            $files = array_diff($files, array('..', '.', '.gitignore'));
        }else{
            $folder = storage_path('06_04');
            $files=[$file.'.pdf'];
            $max = 1;
        }


        foreach($files as $file){
            if($max == 0) break;

            $file_name = $folder.'/'.$file;
            echo "File: $file_name \n";
//            $this->postDocument($file_name);
            $title_detector = new TitleDetector($file_name);
            $title = $title_detector->detectTitle();
            dump($title);
            if($title){
                \DB::table('document')->insert([
                    'path' => $file_name,
                    'title' => $title
                ]);
            }
            $max--;
        }
    }

    public function postDocument($file_name){
        $text = '';

        $lines = shell_exec('pdftotext "'.$file_name.'" -');

        $del = array("\n", "\f");

        $lines = explode( $del[0], str_replace($del, $del[0], $lines) );
        foreach ($lines as $line){
            if($line) $text.=$line .' ';
        }

        try{
            $client = new Client();
            $response = $client->request('POST', 'http://127.0.0.1:5002/document_weighting', [
                'form_params' => [
                    'document' => $text,
                    'language' => 'id'
                ]
            ]);

            $res = trim($response->getBody()->getContents());
            if(!$res == '"ok"') dd($file_name);
        }catch (GuzzleException $e){
            dd($e->getMessage());
        }
        catch (Exception $e){
            dd($e->getMessage());
        }
    }
}
