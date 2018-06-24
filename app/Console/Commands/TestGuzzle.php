<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class TestGuzzle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:guzzle';

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
        $site_file = fopen('sites.txt', 'r');
        $site_list = [];

        while(! feof($site_file))
        {
            $site = fgets($site_file);
            $site = trim($site);
            $site_list[] = $site;
        }

        fclose($site_file);

        //run no async
        echo "No Async\n";
        $time_start = microtime(true);

        $this->noAsync($site_list);

        $time_end = microtime(true);
        echo ($time_end-$time_start) . "\n";

        //run async
        echo "Async (5 concurrency)\n";
        $time_start = microtime(true);

        $this->async($site_list,5);

        $time_end = microtime(true);
        echo ($time_end-$time_start) . "\n";

        echo "Async (10 concurrency)\n";
        $time_start = microtime(true);

        $this->async($site_list,10);

        $time_end = microtime(true);
        echo ($time_end-$time_start) . "\n";
    }

    public function noAsync($site_list){
        foreach($site_list as $site){
            try{
                $res = $this->client->get($site);
                $this->getHeaderTag($res->getBody()->getContents());
            }catch (\Exception $e){
                \Log::info($e->getMessage());
            }
        }
    }

    public function async($site_list, $concurrency){
        $pool = new Pool($this->client, $this->genRequest($site_list), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($res, $index) {
                $this->getHeaderTag($res->getBody()->getContents());
            },
            'rejected' => function ($reason, $index) {
                \Log::info($reason);
            },
        ]);

        $promise = $pool->promise();

        $promise->wait();
    }

    public function genRequest($site_list){
        foreach($site_list as $site){
            yield new Request('GET', $site);
        }
    }

    public function getHeaderTag($content){
        $crawler = new Crawler($content);
        $result = [];

        $level = 1;

        while(count($result) == 0 && $level <= 6){
            $crawler->filter('h'.$level)->each( function ( Crawler $node ) use($result){
                $result[] = $node;
            });

            $level++;
        }

        return $result;
    }
}
