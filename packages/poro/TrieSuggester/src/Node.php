<?php
/**
 * Created by PhpStorm.
 * User: tun
 * Date: 7/7/18
 * Time: 11:46 PM
 */

namespace Poro\TrieSuggester;

class Node
{
    public $value;

    public $children = [];

    //đánh dấu là kết thúc 1 string
    public $end = false;

    public function __construct($value){
        $this->value = $value;
    }

    public function addChild(Node $child){
        $this->children[] = $child;
    }

    public function findChild($value){
        foreach ($this->children as $child){
            if(strcasecmp($child->value, $value) === 0) return $child;
        }

        return null;
    }

    public function getAllString(&$result, $data){
        if($this->end) $result[] = $data;

        foreach ($this->children as $child){
            $child->getAllString($result, $data.$child->value);
        }
    }
}