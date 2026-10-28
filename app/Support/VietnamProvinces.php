<?php

namespace App\Support;

class VietnamProvinces
{
    /**
     * The official 34 province/city administrative units of Vietnam.
     * Includes region grouping, code, and merged background note.
     *
     * @var array<int, array{name: string, region: string, note: string}>
     */
    public const PROVINCES = [
        // ─── Miền Bắc (Northern Region) ───
        [
            'name' => 'TP. Hà Nội',
            'region' => 'Miền Bắc',
            'note' => '',
        ],
        [
            'name' => 'Hải Phòng',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Hải Dương – Hải Phòng',
        ],
        [
            'name' => 'Quảng Ninh',
            'region' => 'Miền Bắc',
            'note' => '',
        ],
        [
            'name' => 'Ninh Bình',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Hà Nam – Nam Định – Ninh Bình',
        ],
        [
            'name' => 'Hưng Yên',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Thái Bình – Hưng Yên',
        ],
        [
            'name' => 'Bắc Ninh',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Bắc Giang – Bắc Ninh',
        ],
        [
            'name' => 'Phú Thọ',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Vĩnh Phúc – Hòa Bình – Phú Thọ',
        ],
        [
            'name' => 'Thái Nguyên',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Bắc Kạn – Thái Nguyên',
        ],
        [
            'name' => 'Lào Cai',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Yên Bái – Lào Cai',
        ],
        [
            'name' => 'Tuyên Quang',
            'region' => 'Miền Bắc',
            'note' => 'sáp nhập Hà Giang – Tuyên Quang',
        ],
        [
            'name' => 'Cao Bằng',
            'region' => 'Miền Bắc',
            'note' => '',
        ],
        [
            'name' => 'Lạng Sơn',
            'region' => 'Miền Bắc',
            'note' => '',
        ],
        [
            'name' => 'Điện Biên',
            'region' => 'Miền Bắc',
            'note' => '',
        ],
        [
            'name' => 'Lai Châu',
            'region' => 'Miền Bắc',
            'note' => '',
        ],
        [
            'name' => 'Sơn La',
            'region' => 'Miền Bắc',
            'note' => '',
        ],

        // ─── Miền Trung & Tây Nguyên (Central Region & Highlands) ───
        [
            'name' => 'Thanh Hóa',
            'region' => 'Miền Trung',
            'note' => '',
        ],
        [
            'name' => 'Nghệ An',
            'region' => 'Miền Trung',
            'note' => '',
        ],
        [
            'name' => 'Hà Tĩnh',
            'region' => 'Miền Trung',
            'note' => '',
        ],
        [
            'name' => 'Quảng Trị',
            'region' => 'Miền Trung',
            'note' => 'sáp nhập Quảng Bình – Quảng Trị',
        ],
        [
            'name' => 'TP. Huế',
            'region' => 'Miền Trung',
            'note' => '',
        ],
        [
            'name' => 'Đà Nẵng',
            'region' => 'Miền Trung',
            'note' => 'sáp nhập Quảng Nam – Đà Nẵng',
        ],
        [
            'name' => 'Quảng Ngãi',
            'region' => 'Miền Trung',
            'note' => 'sáp nhập Kon Tum – Quảng Ngãi',
        ],
        [
            'name' => 'Gia Lai',
            'region' => 'Miền Trung',
            'note' => 'sáp nhập Bình Định – Gia Lai',
        ],
        [
            'name' => 'Khánh Hòa',
            'region' => 'Miền Trung',
            'note' => 'sáp nhập Ninh Thuận – Khánh Hòa',
        ],
        [
            'name' => 'Đắk Lắk',
            'region' => 'Miền Trung',
            'note' => 'sáp nhập Phú Yên – Đắk Lắk',
        ],
        [
            'name' => 'Lâm Đồng',
            'region' => 'Miền Trung',
            'note' => 'sáp nhập Đắk Nông – Bình Thuận – Lâm Đồng',
        ],

        // ─── Miền Nam (Southern Region) ───
        [
            'name' => 'TP.HCM',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Bà Rịa – Vũng Tàu – Bình Dương – TP.HCM',
        ],
        [
            'name' => 'Đồng Nai',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Bình Phước – Đồng Nai',
        ],
        [
            'name' => 'Tây Ninh',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Long An – Tây Ninh',
        ],
        [
            'name' => 'Cần Thơ',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Sóc Trăng – Hậu Giang – Cần Thơ',
        ],
        [
            'name' => 'Vĩnh Long',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Bến Tre – Trà Vinh – Vĩnh Long',
        ],
        [
            'name' => 'Đồng Tháp',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Tiền Giang – Đồng Tháp',
        ],
        [
            'name' => 'An Giang',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Kiên Giang – An Giang',
        ],
        [
            'name' => 'Cà Mau',
            'region' => 'Miền Nam',
            'note' => 'sáp nhập Bạc Liêu – Cà Mau',
        ],
    ];

    /**
     * Get all province names as array of strings.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::PROVINCES, 'name');
    }

    /**
     * Group provinces by region.
     *
     * @return array<string, array<int, array{name: string, region: string, note: string}>>
     */
    public static function groupedByRegion(): array
    {
        $grouped = [];
        foreach (self::PROVINCES as $item) {
            $grouped[$item['region']][] = $item;
        }

        return $grouped;
    }
}
