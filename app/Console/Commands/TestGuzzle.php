<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Poro\TrieSuggester\TrieSuggester;
use Symfony\Component\DomCrawler\Crawler;

class TestGuzzle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:trie';

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

    private $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new Client();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //read site list
        $lang_file = fopen('languages.txt', 'r');
        $lang_list = [];

        while(! feof($lang_file))
        {
            $lang = fgets($lang_file);
            $lang = trim($lang);

            if($lang) $lang_list[] = $lang;
        }

        $trie_suggester = new TrieSuggester();
        $trie_suggester->loadDict($lang_list);

        foreach($trie_suggester->suggest('b') as $l){
            dump($l);
        }

        fclose($lang_file);

        return null;
    }

    public function getLanguageArray(){
        //read site list
        $lang_file = fopen(storage_path('languages.txt'), 'a') or die('Can not open file!');

        try{
            $res = $this->client->request('GET', 'https://en.wikipedia.org/wiki/List_of_programming_languages');
            $response = $res->getBody()->getContents();

            $crawler = new Crawler($response);
            $crawler->filter('.columns a')->each( function ( Crawler $node ) use($lang_file){
                fwrite($lang_file,$node->text()."\n");
            });
        }catch (GuzzleException $e){
        }

        fclose($lang_file);
    }
}
