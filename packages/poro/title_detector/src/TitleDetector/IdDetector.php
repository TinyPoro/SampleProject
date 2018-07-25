<?php
/**
 * Created by PhpStorm.
 * User: tuanp
 * Date: 15/3/18
 * Time: 10:41
 */

namespace Poro\TitleDetector\TitleDetector;


class IdDetector  extends AbstractDetector  {
    protected $denies_config = [
        'preg_match' => [
            [
                'deny' => '/^(Bab|Bagian|Pelajaran|Pasal)\s+[IVX0-9]{1,2}\s*(\:|\.|–)?\s*/ui',
                'type' => 2,
            ]
        ],
        'starts' => [ // tìm từ vị trí bắt đầu
        ],
        'ends' => [ // tìm từ vị trí kết thúc
        ],
        'exact' => [ // tìm vị trí bất ký, dùng để remove chuỗi không cần thiết hoặc lý tự đặc biệt
            [
                'deny' => 'REPUBLIK INDONESIA',
                'type' => 2,
            ],
            [
                'deny' => 'UNIVERSITAS',
                'type' => 3,
            ]
        ]
    ];
}