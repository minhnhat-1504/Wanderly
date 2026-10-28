<?php

namespace Tests\Unit;

use App\Services\Itinerary\ProvinceResolver;
use Tests\TestCase;

class ProvinceResolverTest extends TestCase
{
    protected ProvinceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ProvinceResolver;
    }

    public function test_loads_all_34_official_provinces(): void
    {
        $all = $this->resolver->getAll();
        $this->assertCount(34, $all);

        $codes = array_column($all, 'code');
        $this->assertContains('hanoi', $codes);
        $this->assertContains('danang', $codes);
        $this->assertContains('tphcm', $codes);
        $this->assertContains('lamdong', $codes);
        $this->assertContains('khanhhoa', $codes);
        $this->assertContains('angiang', $codes);
    }

    public function test_resolves_exact_official_names_with_various_casing_and_prefixes(): void
    {
        $res1 = $this->resolver->resolve('TP. Hà Nội');
        $this->assertTrue($res1['resolved']);
        $this->assertSame('hanoi', $res1['province']['code']);

        $res2 = $this->resolver->resolve('tinh quang ninh');
        $this->assertTrue($res2['resolved']);
        $this->assertSame('quangninh', $res2['province']['code']);

        $res3 = $this->resolver->resolve('DA NANG');
        $this->assertTrue($res3['resolved']);
        $this->assertSame('danang', $res3['province']['code']);
    }

    public function test_resolves_old_province_names_and_merged_aliases(): void
    {
        // "Vũng Tàu", "Bà Rịa", "Sài Gòn" -> TP. Hồ Chí Minh
        $vungTau = $this->resolver->resolve('Vũng Tàu');
        $this->assertTrue($vungTau['resolved']);
        $this->assertSame('tphcm', $vungTau['province']['code']);

        $baRia = $this->resolver->resolve('ba ria');
        $this->assertTrue($baRia['resolved']);
        $this->assertSame('tphcm', $baRia['province']['code']);

        $saiGon = $this->resolver->resolve('Sài Gòn');
        $this->assertTrue($saiGon['resolved']);
        $this->assertSame('tphcm', $saiGon['province']['code']);

        // "Hà Giang" -> Tuyên Quang
        $haGiang = $this->resolver->resolve('Ha Giang');
        $this->assertTrue($haGiang['resolved']);
        $this->assertSame('tuyenquang', $haGiang['province']['code']);

        // "Phú Quốc" -> An Giang
        $phuQuoc = $this->resolver->resolve('Phú Quốc');
        $this->assertTrue($phuQuoc['resolved']);
        $this->assertSame('angiang', $phuQuoc['province']['code']);

        // "Đà Lạt" -> Lâm Đồng
        $daLat = $this->resolver->resolve('Đà Lạt');
        $this->assertTrue($daLat['resolved']);
        $this->assertSame('lamdong', $daLat['province']['code']);

        // "Nha Trang" -> Khánh Hòa
        $nhaTrang = $this->resolver->resolve('Nha Trang');
        $this->assertTrue($nhaTrang['resolved']);
        $this->assertSame('khanhhoa', $nhaTrang['province']['code']);

        // "Sa Pa" -> Lào Cai
        $sapa = $this->resolver->resolve('Sapa');
        $this->assertTrue($sapa['resolved']);
        $this->assertSame('laocai', $sapa['province']['code']);

        // "Hội An" -> Đà Nẵng
        $hoiAn = $this->resolver->resolve('Hoi An');
        $this->assertTrue($hoiAn['resolved']);
        $this->assertSame('danang', $hoiAn['province']['code']);
    }

    public function test_resolves_accent_insensitive_and_slight_typo(): void
    {
        $res = $this->resolver->resolve('quang nynh');
        $this->assertTrue($res['resolved']);
        $this->assertSame('quangninh', $res['province']['code']);

        $res2 = $this->resolver->resolve('thanh hoa');
        $this->assertTrue($res2['resolved']);
        $this->assertSame('thanhhoa', $res2['province']['code']);
    }

    public function test_returns_clarification_for_unknown_place(): void
    {
        $res = $this->resolver->resolve('Tokyo Japan');
        $this->assertFalse($res['resolved']);
        $this->assertNull($res['province']);
        $this->assertNotNull($res['clarification']);
    }

    public function test_cleans_administrative_units_huyen_quan_thi_xa(): void
    {
        $dirty1 = 'Thăm Chợ Bến Thành, quận 1, TP.HCM';
        $clean1 = $this->resolver->cleanAdministrativeUnits($dirty1);
        $this->assertStringNotContainsString('quận 1', $clean1);

        $dirty2 = 'Khám phá Đèo Ô Quy Hồ, huyện Tam Đường, Lai Châu';
        $clean2 = $this->resolver->cleanAdministrativeUnits($dirty2);
        $this->assertStringNotContainsString('huyện Tam Đường', $clean2);

        $dirty3 = 'Ghé thăm Làng hoa Sa Đéc, thị xã Sa Đéc, Đồng Tháp';
        $clean3 = $this->resolver->cleanAdministrativeUnits($dirty3);
        $this->assertStringNotContainsString('thị xã Sa Đéc', $clean3);
    }
}
