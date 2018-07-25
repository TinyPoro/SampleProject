<?php
/**
 * Created by PhpStorm.
 * User: tuanp
 * Date: 15/3/18
 * Time: 10:41
 */

namespace Poro\TitleDetector\TitleDetector;


class BrDetector  extends AbstractDetector  {
    protected $denies_config = [
        'preg_match' => [
            [
                'deny' => '/(?<!^)(Capítulo|Seção|Lección)\s+[IVX0-9]{1,2}\s*(\:|\.)?\s*/u',
                'type' => 1
            ]
        ],
        'starts' => [ // tìm từ vị trí bắt đầu
        ],
        'ends' => [ // tìm từ vị trí kết thúc
        ],
        'exact' => [ // tìm vị trí bất ký, dùng để remove chuỗi không cần thiết hoặc lý tự đặc biệt
        ]
    ];

}