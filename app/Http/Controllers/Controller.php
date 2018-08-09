<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function showDocument($document){
        $storage_path = env('STORAGE_PATH', storage_path('06_04'));
        $pathToFile = $storage_path."/$document.pdf";
        if(file_exists($pathToFile)) return response()->file($pathToFile);
        else return "file đã bị xóa";
    }
}
