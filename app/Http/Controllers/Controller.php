<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Poro\TrieSuggester\TrieSuggester;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $trie_suggester;

    public function __construct()
    {
        //read site list
        $lang_file = fopen(storage_path('languages.txt'), 'r');
        $lang_list = [];

        while(! feof($lang_file))
        {
            $lang = fgets($lang_file);
            $lang = trim($lang);

            if($lang) $lang_list[] = $lang;
        }

        $this->trie_suggester = new TrieSuggester();
        $this->trie_suggester->loadDict($lang_list);

        fclose($lang_file);
    }

    public function show(){
        return view('trie');
    }

    public function run(Request $request){
        $input = $request->get('input');
        $name = $request->get('name');

        $start = microtime(true);

        $data = [];
        if($name == 'suggest') $data = $this->trie_suggester->suggest($input);
        if($name == 'alternateSuggest') $data = $this->trie_suggester->alternateSuggest($input);
        $end = microtime(true);

        return [
            'time' => ($end - $start) * 1000000 . " ( μs)",
            'data' => $data
        ];
    }
}
