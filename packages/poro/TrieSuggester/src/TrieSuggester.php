<?php
/**
 * Created by PhpStorm.
 * User: tun
 * Date: 7/7/18
 * Time: 11:37 PM
 */

namespace Poro\TrieSuggester;

class TrieSuggester
{
    public $root;

    public function __construct(){
        $this->root = new Node('root');
    }

    public function loadDict($array){
        foreach($array as $item){
            $this->addItem($item);
        }
    }

    public function addItem($item){
        $cur_node = $this->root;

        for($i = 0; $i < strlen($item); $i++){
            $value = $item[$i];

            $child = $cur_node->findChild($value);
            if(!$child) {
                $child = new Node($value);
                $cur_node->addChild($child);
            }

            $cur_node = $child;
        }

        $cur_node->end = true;
    }

    public function suggest($item){
        $result = [];

        $cur_node = $this->root;

        for($i = 0; $i < strlen($item); $i++){
            $value = $item[$i];

            $child = $cur_node->findChild($value);
            if(!$child) return $result;

            $cur_node = $child;
        }

        $cur_node->getAllString($result, $item);
        return $result;
    }
}