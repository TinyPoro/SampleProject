<?php
/**
 * Created by PhpStorm.
 * User: tuanp
 * Date: 15/3/18
 * Time: 10:41
 */

namespace Poro\TitleDetector\TitleDetector;

abstract class AbstractDetector {
    protected $denies = [
        'preg_match' => [
            [
                'deny' => '/(?<!^)(Chapter|Section|Lesson)\s+[IVX0-9]{1,2}\s*(\:|\.)?\s*/u',
                'type' => 1
            ]
        ],
        'starts' => [ // tìm từ vị trí bắt đầu
        ],
        'ends' => [ // tìm từ vị trí kết thúc
        ],
        'exact' => [ // tìm vị trí bất ký, dùng để remove chuỗi không cần thiết hoặc lý tự đặc biệt
            [
                'deny' => 'UNIVERSITY',
                'type' => 1,
            ]
        ]
    ];

    protected $denies_config = [
        'preg_match' => [
        ],
        'starts' => [ // tìm từ vị trí bắt đầu
        ],
        'ends' => [ // tìm từ vị trí kết thúc
        ],
        'exact' => [ // tìm vị trí bất ký, dùng để remove chuỗi không cần thiết hoặc lý tự đặc biệt
        ]
    ];

    public function __construct() {
        foreach ([
                     'preg_match',
                     'exact',
                     'starts',
                     'ends' ] as $type){
            try{
                $this->addConfig('denies', $type, $this->denies_config[$type]);
            }catch (\Exception $e){
                \Log::error("Add language config error::" . $e->getMessage());
            }
        }
    }

    protected function trim($string){
        return trim($string);
    }

    protected function addConfig($mode, $type, array $appends, $replace = false){
        if(!isset($this->$mode[$type])){
            throw new \Exception("Mode $mode type $type is not supported");
        }

        if($replace) {
            $this->$mode[$type] = $appends;
        }
        else {
            $this->$mode[$type] = array_merge($appends, $this->$mode[$type]);
        }
    }

    public function check($string){
        if($result = $this->deny($string)) return $result;

        return ['success'=>'true'];
    }

    public function deny($string){
        if($type = $this->startDeny($string)) return ['success'=>'false', 'reason' => 'start', 'type' => "$type"];
        if($type = $this->endDeny($string)) return ['success'=>'false', 'reason' => 'end', 'type' => "$type"];
        if($type = $this->exactDeny($string)) return ['success'=>'false', 'reason' => 'exact', 'type' => "$type"];
        if($type = $this->pregDeny($string)) return ['success'=>'false', 'reason' => 'preg', 'type' => "$type"];

        return false;
    }

    protected function pregDeny($string){
        foreach ($this->denies['preg_match'] as $preg_match) {
            $pattern = $preg_match['deny'];
            $type = $preg_match['type'];

            if(preg_match($pattern, $string, $matches)) {
                return $type;
            }
        }

        return false;
    }
    protected function exactDeny($string){
        foreach ($this->denies['exact'] as $contains){
            $contain = $contains['deny'];
            $type = $contains['type'];

            if($contain!='' && mb_stripos($string, $contain) !== false){
                return $type;
            }
        }

        return false;
    }
    protected function startDeny($string){
        foreach ($this->denies['starts'] as $starts){
            $start = $starts['deny'];
            $type = $starts['type'];

            if($start!='' && mb_stripos($string, $start) === 0){
                return $type;
            }
        }

        return false;
    }
    protected function endDeny($string){
        foreach ($this->denies['ends'] as $ends){
            $end = $ends['deny'];
            $type = $ends['type'];

            $right_pos = mb_strlen($string) - mb_strlen($end);
            if($end!='' && mb_stripos($string, $end) === $right_pos){
                return $type;
            }
        }

        return false;
    }
}