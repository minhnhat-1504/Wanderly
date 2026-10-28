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
            'note' => 'Thủ đô Hà Nội',
        ],
        [
            'name' => 'Hải Phòng',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Hải Dương – Hải Phòng',
        ],
        [
            'name' => 'Quảng Ninh',
            'region' => 'Miền Bắc',
            'note' => 'Vịnh Hạ Long, Vân Đồn, Cô Tô',
        ],
        [
            'name' => 'Ninh Bình',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Hà Nam – Nam Định – Ninh Bình',
        ],
        [
            'name' => 'Hưng Yên',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Thái Bình – Hưng Yên',
        ],
        [
            'name' => 'Bắc Ninh',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Bắc Giang – Bắc Ninh',
        ],
        [
            'name' => 'Phú Thọ',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Vĩnh Phúc – Hòa Bình – Phú Thọ',
        ],
        [
            'name' => 'Thái Nguyên',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Bắc Kạn – Thái Nguyên',
        ],
        [
            'name' => 'Lào Cai',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Yên Bái – Lào Cai (Sa Pa, Fansipan)',
        ],
        [
            'name' => 'Tuyên Quang',
            'region' => 'Miền Bắc',
            'note' => 'Sáp nhập Hà Giang – Tuyên Quang (Cao nguyên đá Đồng Văn, Hẻm Tu Sản)',
        ],
        [
            'name' => 'Cao Bằng',
            'region' => 'Miền Bắc',
            'note' => 'Thác Bản Giốc, Pác Bó',
        ],
        [
            'name' => 'Lạng Sơn',
            'region' => 'Miền Bắc',
            'note' => 'Cửa khẩu Hữu Nghị, Mẫu Sơn',
        ],
        [
            'name' => 'Điện Biên',
            'region' => 'Miền Bắc',
            'note' => 'Chiến trường Điện Biên Phủ, Đèo Pha Đin',
        ],
        [
            'name' => 'Lai Châu',
            'region' => 'Miền Bắc',
            'note' => 'Đèo Ô Quy Hồ, Pu Si Lung',
        ],
        [
            'name' => 'Sơn La',
            'region' => 'Miền Bắc',
            'note' => 'Cao nguyên Mộc Châu, Tà Xùa',
        ],

        // ─── Miền Trung & Tây Nguyên (Central Region & Highlands) ───
        [
            'name' => 'Thanh Hóa',
            'region' => 'Miền Trung',
            'note' => 'Sầm Sơn, Pù Luông',
        ],
        [
            'name' => 'Nghệ An',
            'region' => 'Miền Trung',
            'note' => 'Cửa Lò, Quê Bác Kim Liên',
        ],
        [
            'name' => 'Hà Tĩnh',
            'region' => 'Miền Trung',
            'note' => 'Biển Thiên Cầm, Ngã ba Đồng Lộc',
        ],
        [
            'name' => 'Quảng Trị',
            'region' => 'Miền Trung',
            'note' => 'Sáp nhập Quảng Bình – Quảng Trị (Phong Nha - Kẻ Bàng, Sơn Đoòng)',
        ],
        [
            'name' => 'TP. Huế',
            'region' => 'Miền Trung',
            'note' => 'Cố đô Huế, Sông Hương',
        ],
        [
            'name' => 'Đà Nẵng',
            'region' => 'Miền Trung',
            'note' => 'Sáp nhập Quảng Nam – Đà Nẵng (Bà Nà Hills, Phố cổ Hội An, Mỹ Sơn)',
        ],
        [
            'name' => 'Quảng Ngãi',
            'region' => 'Miền Trung',
            'note' => 'Sáp nhập Kon Tum – Quảng Ngãi (Đảo Lý Sơn, Măng Đen)',
        ],
        [
            'name' => 'Gia Lai',
            'region' => 'Miền Trung',
            'note' => 'Sáp nhập Bình Định – Gia Lai (Quy Nhơn, Eo Gió, Biển Hồ)',
        ],
        [
            'name' => 'Khánh Hòa',
            'region' => 'Miền Trung',
            'note' => 'Sáp nhập Ninh Thuận – Khánh Hòa (Nha Trang, Vịnh Cam Ranh, Phan Rang)',
        ],
        [
            'name' => 'Đắk Lắk',
            'region' => 'Miền Trung',
            'note' => 'Sáp nhập Phú Yên – Đắk Lắk (Buôn Ma Thuột, Gành Đá Đĩa)',
        ],
        [
            'name' => 'Lâm Đồng',
            'region' => 'Miền Trung',
            'note' => 'Sáp nhập Đắk Nông – Bình Thuận – Lâm Đồng (Đà Lạt, Mũi Né, Tà Đùng)',
        ],

        // ─── Miền Nam (Southern Region) ───
        [
            'name' => 'TP.HCM',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Bà Rịa – Vũng Tàu – Bình Dương – TP.HCM',
        ],
        [
            'name' => 'Đồng Nai',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Bình Phước – Đồng Nai (VQG Cát Tiên)',
        ],
        [
            'name' => 'Tây Ninh',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Long An – Tây Ninh (Núi Bà Đen)',
        ],
        [
            'name' => 'Cần Thơ',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Sóc Trăng – Hậu Giang – Cần Thơ (Chợ nổi Cái Răng)',
        ],
        [
            'name' => 'Vĩnh Long',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Bến Tre – Trà Vinh – Vĩnh Long',
        ],
        [
            'name' => 'Đồng Tháp',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Tiền Giang – Đồng Tháp (Làng hoa Sa Đéc, Tràm Chim)',
        ],
        [
            'name' => 'An Giang',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Kiên Giang – An Giang (Đảo Phú Quốc, Rừng tràm Trà Sư, Châu Đốc)',
        ],
        [
            'name' => 'Cà Mau',
            'region' => 'Miền Nam',
            'note' => 'Sáp nhập Bạc Liêu – Cà Mau (Mũi Cà Mau, Nhà thờ Tắc Sậy)',
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
