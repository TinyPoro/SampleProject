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
    {--file= : Lựa chọn file để chạy }';

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
        $file = $this->option('file');
        $file = storage_path('input/id12.pdf');

        $title_detector = new TitleDetector($file);
        $title_detector->detectTitle();
    }
}
