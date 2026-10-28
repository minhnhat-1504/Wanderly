<?php

namespace App\Services\Itinerary;

use Illuminate\Support\Facades\File;

class ProvinceResolver
{
    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $provinces = null;

    public function __construct(?string $jsonPath = null)
    {
        $path = $jsonPath ?? base_path('data/provinces.json');
        if (File::exists($path)) {
            $this->provinces = json_decode(File::get($path), true);
        } else {
            $this->provinces = [];
        }
    }

    /**
     * Get all loaded provinces.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->provinces ?? [];
    }

    /**
     * Find a province by exact code.
     *
     * @return array<string, mixed>|null
     */
    public function getByCode(string $code): ?array
    {
        $normalized = strtolower(trim($code));
        foreach ($this->getAll() as $p) {
            if (strtolower($p['code']) === $normalized) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Resolve user input to a verified 34-province unit.
     *
     * @return array{
     *     resolved: bool,
     *     province: array<string, mixed>|null,
     *     clarification: string|null,
     *     candidates: array<int, string>,
     *     matched_by: string|null
     * }
     */
    public function resolve(string $input): array
    {
        $raw = trim($input);
        if (empty($raw)) {
            return [
                'resolved' => false,
                'province' => null,
                'clarification' => 'Vui lòng nhập tên tỉnh hoặc thành phố bạn muốn đi du lịch.',
                'candidates' => [],
                'matched_by' => null,
            ];
        }

        $normalized = $this->normalizeString($raw);

        // 1. Exact match on province code
        foreach ($this->getAll() as $p) {
            if ($this->normalizeString($p['code']) === $normalized) {
                return [
                    'resolved' => true,
                    'province' => $p,
                    'clarification' => null,
                    'candidates' => [],
                    'matched_by' => 'code',
                ];
            }
        }

        // 2. Exact match on official name or aliases
        $exactMatches = [];
        foreach ($this->getAll() as $p) {
            // Check official name
            if ($this->normalizeString($p['name']) === $normalized) {
                $exactMatches[$p['code']] = [
                    'province' => $p,
                    'matched_by' => 'name',
                ];
                continue;
            }

            // Check aliases
            foreach ($p['aliases'] ?? [] as $alias) {
                if ($this->normalizeString($alias) === $normalized) {
                    $exactMatches[$p['code']] = [
                        'province' => $p,
                        'matched_by' => 'alias',
                    ];
                    break;
                }
            }
        }

        if (count($exactMatches) === 1) {
            $match = reset($exactMatches);
            return [
                'resolved' => true,
                'province' => $match['province'],
                'clarification' => null,
                'candidates' => [],
                'matched_by' => $match['matched_by'],
            ];
        }

        if (count($exactMatches) > 1) {
            $names = array_map(fn ($m) => $m['province']['name'], $exactMatches);
            return [
                'resolved' => false,
                'province' => null,
                'clarification' => 'Tên địa danh "' . $raw . '" có thể thuộc về các tỉnh: ' . implode(', ', $names) . '. Bạn muốn chọn tỉnh/thành phố nào?',
                'candidates' => array_values($names),
                'matched_by' => null,
            ];
        }

        // 3. Substring match (input contained in name/alias or vice versa)
        $substringMatches = [];
        foreach ($this->getAll() as $p) {
            $normName = $this->normalizeString($p['name']);
            if (str_contains($normalized, $normName) || (strlen($normName) >= 4 && str_contains($normName, $normalized))) {
                $substringMatches[$p['code']] = $p;
                continue;
            }

            foreach ($p['aliases'] ?? [] as $alias) {
                $normAlias = $this->normalizeString($alias);
                if (strlen($normAlias) >= 4 && (str_contains($normalized, $normAlias) || str_contains($normAlias, $normalized))) {
                    $substringMatches[$p['code']] = $p;
                    break;
                }
            }
        }

        if (count($substringMatches) === 1) {
            $p = reset($substringMatches);
            return [
                'resolved' => true,
                'province' => $p,
                'clarification' => null,
                'candidates' => [],
                'matched_by' => 'substring',
            ];
        }

        if (count($substringMatches) > 1) {
            $names = array_map(fn ($p) => $p['name'], $substringMatches);
            return [
                'resolved' => false,
                'province' => null,
                'clarification' => 'Từ khóa "' . $raw . '" khớp với nhiều tỉnh thành: ' . implode(', ', $names) . '. Hãy làm rõ điểm đến mong muốn của bạn.',
                'candidates' => array_values($names),
                'matched_by' => null,
            ];
        }

        // 4. Fuzzy match (Levenshtein / Similarity ratio)
        $bestMatch = null;
        $highestPercent = 0.0;
        $closeCandidates = [];

        foreach ($this->getAll() as $p) {
            $allTerms = array_merge([$p['name']], $p['aliases'] ?? []);
            foreach ($allTerms as $term) {
                $normTerm = $this->normalizeString($term);
                similar_text($normalized, $normTerm, $percent);

                if ($percent > 78.0) {
                    $closeCandidates[$p['name']] = max($closeCandidates[$p['name']] ?? 0.0, $percent);
                    if ($percent > $highestPercent) {
                        $highestPercent = $percent;
                        $bestMatch = $p;
                    }
                }
            }
        }

        // Check if there are multiple close candidates within 5% of highest
        $ambiguous = [];
        foreach ($closeCandidates as $name => $pct) {
            if ($highestPercent - $pct <= 5.0) {
                $ambiguous[] = $name;
            }
        }

        if (count($ambiguous) > 1) {
            return [
                'resolved' => false,
                'province' => null,
                'clarification' => 'Tên bạn nhập gần giống với: ' . implode(', ', $ambiguous) . '. Vui lòng nhập rõ hơn tên tỉnh/thành phố bạn muốn đi.',
                'candidates' => $ambiguous,
                'matched_by' => null,
            ];
        }

        if ($bestMatch && $highestPercent >= 82.0) {
            return [
                'resolved' => true,
                'province' => $bestMatch,
                'clarification' => null,
                'candidates' => [],
                'matched_by' => 'fuzzy',
            ];
        }

        // Unknown - never guess!
        return [
            'resolved' => false,
            'province' => null,
            'clarification' => 'Hệ thống không tìm thấy tỉnh/thành phố phù hợp với "' . $raw . '". Hiện tại Việt Nam có 34 tỉnh/thành phố theo Nghị quyết 202/2025/QH15. Vui lòng kiểm tra lại.',
            'candidates' => [],
            'matched_by' => null,
        ];
    }

    /**
     * Clean district/county (quận/huyện/thị xã) administrative units from text.
     * Rule: Never output "huyện/quận" administrative units in itineraries.
     */
    public function cleanAdministrativeUnits(string $text): string
    {
        // Strip out "quận X", "huyện Y", "thị xã Z" patterns
        $cleaned = preg_replace('/\b(quận|huyện|thị xã)\s+[\p{L}0-9\s-]+(?=,|$|\.|\n)/iu', '', $text);
        // Clean leftover double commas or spaces
        $cleaned = preg_replace('/\s*,\s*,\s*/', ', ', $cleaned ?? $text);
        return trim(preg_replace('/\s{2,}/', ' ', $cleaned));
    }

    /**
     * Normalize Vietnamese string: lowercase, remove accents, strip prefixes.
     */
    public function normalizeString(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');

        // Remove administrative prefixes
        $prefixes = [
            '/^(thành phố|tỉnh thành|tp\.|t\.|tỉnh)\s+/iu',
            '/^(thị xã|quận|huyện)\s+/iu',
        ];
        $str = preg_replace($prefixes, '', $str);

        // Remove accents
        $str = $this->removeVietnameseAccents($str);

        // Remove non-alphanumeric except space
        $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);

        // Collapse multiple spaces
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /**
     * Convert accented Vietnamese characters to ASCII plain letters.
     */
    public function removeVietnameseAccents(string $str): string
    {
        $accents = [
            'a' => ['á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ'],
            'd' => ['đ'],
            'e' => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ'],
            'i' => ['í', 'ì', 'ỉ', 'ĩ', 'ị'],
            'o' => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ'],
            'u' => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự'],
            'y' => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'],
        ];

        foreach ($accents as $plain => $letters) {
            $str = str_replace($letters, $plain, $str);
        }

        return $str;
    }
}
