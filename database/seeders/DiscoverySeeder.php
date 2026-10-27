<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\DiscoveryArticle;
use App\Models\DiscoveryRegion;
use Illuminate\Database\Seeder;

class DiscoverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            [
                'name' => 'Northern Vietnam',
                'slug' => 'northern-vietnam',
                'display_order' => 1,
                'description' => 'Towering limestone karsts, emerald mountain valleys, thousand-year-old capital heritage, and the legendary serpentine passes of the northern frontier.',
                'cover_image' => 'images/destinations/hero.jpg',
            ],
            [
                'name' => 'Central Vietnam',
                'slug' => 'central-vietnam',
                'display_order' => 2,
                'description' => 'UNESCO World Heritage towns, royal imperial gastronomy in Hue, vibrant lantern-lit streets in Hoi An, and world-renowned coastline beaches in Da Nang.',
                'cover_image' => 'images/destinations/hoian.jpg',
            ],
            [
                'name' => 'Central Highlands',
                'slug' => 'central-highlands',
                'display_order' => 3,
                'description' => 'Pine-covered rolling hills, eternal spring climate, crystal-clear volcanic lakes, and deep-rooted ethnic gong cultures in Lam Dong and Dak Lak.',
                'cover_image' => 'images/destinations/dalat.jpg',
            ],
            [
                'name' => 'Southern Vietnam',
                'slug' => 'southern-vietnam',
                'display_order' => 4,
                'description' => 'Sun-kissed tropical archipelagos, emerald waters in Phu Quoc, vibrant floating river markets, and generous culinary hospitality.',
                'cover_image' => 'images/destinations/phuquoc.jpg',
            ],
        ];

        $regionModels = [];
        foreach ($regions as $r) {
            $regionModels[$r['slug']] = DiscoveryRegion::updateOrCreate(
                ['slug' => $r['slug']],
                [
                    'name' => $r['name'],
                    'display_order' => $r['display_order'],
                    'description' => $r['description'],
                    'cover_image' => $r['cover_image'],
                ]
            );
        }

        // Ensure key destinations exist
        $destinations = [
            'Ha Giang' => Destination::firstOrCreate(
                ['name' => 'Ha Giang'],
                ['description' => 'Dong Van Karst Plateau and the majestic Ma Pi Leng Pass.']
            ),
            'Ha Long' => Destination::firstOrCreate(
                ['name' => 'Ha Long'],
                ['description' => 'UNESCO World Natural Heritage bay of limestone islands.']
            ),
            'Hoi An' => Destination::firstOrCreate(
                ['name' => 'Hoi An'],
                ['description' => 'Ancient trading port and UNESCO Cultural Heritage town.']
            ),
            'Da Nang' => Destination::firstOrCreate(
                ['name' => 'Da Nang'],
                ['description' => 'Modern coastal city famous for iconic bridges and My Khe beach.']
            ),
            'Da Lat' => Destination::firstOrCreate(
                ['name' => 'Da Lat'],
                ['description' => 'Highland city of flowers, pine forests, and cool mountain air.']
            ),
            'Phu Quoc' => Destination::firstOrCreate(
                ['name' => 'Phu Quoc'],
                ['description' => 'Tropical paradise island in the Gulf of Thailand.']
            ),
        ];

        $articles = [
            [
                'region_slug' => 'northern-vietnam',
                'destination_name' => 'Ha Giang',
                'title' => 'Ha Giang: Conquering Ma Pi Leng Pass & Tu San Canyon',
                'slug' => 'ha-giang-ma-pi-leng-pass-tu-san-canyon',
                'category' => 'nature',
                'location_name' => 'Ha Giang Province, Northern Vietnam',
                'summary' => "Vietnam's rugged northern frontier renowned for dizzying serpentine passes, the turquoise Tu San Canyon along the Nho Que River, and vibrant buckwheat blooms.",
                'best_season' => 'September – November (Buckwheat blooms & golden terraced fields)',
                'estimated_duration' => '3–4 Days',
                'featured_image' => 'images/destinations/hagiang.jpg',
                'highlights' => [
                    'Ma Pi Leng Pass scenic ride 1,500m above sea level',
                    'Nho Que River boat cruise through Tu San Canyon',
                    'Dong Van Karst Plateau UNESCO Geopark',
                    'Dong Van Sunday ethnic gathering market',
                ],
                'specialties' => [
                    'Au Tau bittersweet medicinal porridge',
                    'Thang Co traditional spiced mountain stew',
                    'Buckwheat flower cakes (Banh Tam Giac Mach)',
                    'Smoked wild boar and hill-tribe buffalo jerky',
                ],
                'views_count' => 1420,
                'content' => "Standing at the northernmost border of Vietnam, Ha Giang is a legendary wonderland for intrepid explorers. The Happiness Road winds through the dizzying limestone peaks of the Dong Van Karst Plateau UNESCO Global Geopark.

### Highlights & Must-See Sights
1. **Ma Pi Leng Pass:** Known as the king of Vietnamese mountain passes, offering jaw-dropping panoramic views 1,500m above sea level.
2. **Nho Que River Boat Cruise:** Glide along turquoise waters through Tu San Canyon, the deepest tectonic canyon in Southeast Asia with 800m vertical cliffs.
3. **Dong Van Sunday Market:** A bustling gathering place where H'Mong, Tay, and Dao ethnic communities trade colorful hand-woven textiles, honey, and local livestock.

### Gastronomy Highlights
- **Au Tau Porridge:** A warm, bittersweet medicinal porridge simmered over charcoal, perfect for chilly highland nights.
- **Thang Co:** Traditional spiced stew cooked in giant iron woks at mountain markets.
- **Buckwheat Flower Cakes:** Soft, nutty steamed and grilled cakes made from ground buckwheat seeds.",
            ],
            [
                'region_slug' => 'northern-vietnam',
                'destination_name' => 'Ha Long',
                'title' => 'Ha Long Bay: Emerald Waters, Karst Wonders & Luxury Cruising',
                'slug' => 'ha-long-bay-emerald-waters-karst-wonders',
                'category' => 'nature',
                'location_name' => 'Quang Ninh Province, Northern Vietnam',
                'summary' => 'Thousands of towering limestone pillars rising dramatically from turquoise waters. Sleep under the stars aboard a luxury cruise and kayak through mystical caves.',
                'best_season' => 'April – October (Sunny skies, calm seas & clear visibility)',
                'estimated_duration' => '2–3 Days',
                'featured_image' => 'images/destinations/hero.jpg',
                'highlights' => [
                    '5-Star overnight luxury cruise in Lan Ha & Ha Long Bay',
                    'Sung Sot (Surprise) Cave cavern tour',
                    'Kayaking through Luon Cave hidden lagoon',
                    'Ti Top Island panoramic mountain climb',
                ],
                'specialties' => [
                    'Hand-pounded squid rolls (Cha Muc Ha Long)',
                    'Steamed mantis shrimp and sea clams',
                    'Bun be be (Sea mantis noodle soup)',
                    'Grilled coastal oysters with scallion oil',
                ],
                'views_count' => 2890,
                'content' => 'Recognized twice by UNESCO as a World Natural Heritage site, Ha Long Bay spans 1,500 square kilometers studded with over 1,600 limestone pillars rising dramatically from tranquil emerald waters.

### Highlights & Experiences
1. **Overnight Boutique Cruise:** Fall asleep under starlit skies in Lan Ha or Bai Tu Long Bay, waking up to sunrise tai chi sessions on the upper deck.
2. **Sung Sot (Surprise) Cave:** Marvel at colossal subterranean chambers filled with thousands of years of sculpted stalagmites and stalactites.
3. **Kayaking Luon Cave:** Paddle beneath narrow limestone sea arches into hidden tidal lagoons surrounded by playful golden-headed langurs.

### Seafood Delights
- **Hand-Pounded Squid Rolls (Cha Muc):** Crispy, golden, bouncy squid patties freshly fried and served with sticky rice or steamed rice rolls.
- **Steamed Mantis Shrimp & Sea Clams:** Caught daily by local fishing junks and steamed with lemongrass and sea salt.',
            ],
            [
                'region_slug' => 'central-vietnam',
                'destination_name' => 'Hoi An',
                'title' => 'Hoi An Ancient Town: Lanterns, Heritage & Street Bites',
                'slug' => 'hoi-an-ancient-town-lanterns-heritage',
                'category' => 'culture',
                'location_name' => 'Quang Nam Province, Central Vietnam',
                'summary' => 'A magical riverside town steeped in centuries of trade history, glowing warmly under thousands of colorful silk lanterns every evening along the Hoai River.',
                'best_season' => 'February – July (Dry season, warm sunshine & lantern festivals)',
                'estimated_duration' => '2–3 Days',
                'featured_image' => 'images/destinations/hoian.jpg',
                'highlights' => [
                    'Japanese Covered Bridge (Chua Cau) heritage walk',
                    'Full Moon lantern floating ceremony on Hoai River',
                    'Bay Mau coconut forest bamboo basket boat spinning',
                    'Tra Que organic vegetable farming village tour',
                ],
                'specialties' => [
                    'Cao Lau chewy noodles with char siu pork',
                    'Banh Mi Phuong crispy baguettes',
                    'Ba Buoi traditional shredded chicken rice',
                    'White rose dumplings (Banh Bao Banh Vac)',
                ],
                'views_count' => 3120,
                'content' => "Once a bustling 15th-century maritime trading port where Japanese, Chinese, and European merchant ships converged, Hoi An remains one of Vietnam's best-preserved living architectural treasures.

### Cultural Highlights
1. **Japanese Covered Bridge (Chua Cau):** An iconic 400-year-old wooden bridge with weather-worn guardian dog and monkey statues.
2. **Full Moon Lantern Festival:** Once a month on the lunar 14th night, electric lights across town are extinguished, replaced entirely by thousands of glowing silk lanterns floating softly on the Hoai River.
3. **Bay Mau Coconut Forest:** Ride whimsical spinning bamboo basket boats through nipa palm groves guided by smiling local boatmen.

### Street Food Legend
- **Cao Lau Noodles:** Chewy thick noodles kneaded with ash water from Cham Island, topped with savory char siu pork, crispy cracklings, and aromatic mountain herbs.
- **Banh Mi Phuong:** World-acclaimed crispy French baguettes stuffed with pate, grilled meats, pickles, and signature chili emulsion.",
            ],
            [
                'region_slug' => 'central-vietnam',
                'destination_name' => 'Da Nang',
                'title' => 'Da Nang: Dragon Bridges, Coastal Bliss & Golden Bridge',
                'slug' => 'da-nang-dragon-bridges-coastal-bliss',
                'category' => 'festival',
                'location_name' => 'Da Nang City, Central Vietnam',
                'summary' => "Vietnam's most livable modern metropolis, famous for miles of powdery white sand, the fire-breathing Dragon Bridge, and sacred Marble Mountains.",
                'best_season' => 'March – August (Golden beach season & International Fireworks Festival)',
                'estimated_duration' => '3–4 Days',
                'featured_image' => 'images/destinations/danang.jpg',
                'highlights' => [
                    'Dragon Bridge weekend fire & water performance',
                    'Golden Bridge pedestrian walkway at Ba Na Hills',
                    'Marble Mountains (Ngu Hanh Son) cave temples',
                    'Son Tra Peninsula red-shanked douc langur sanctuary',
                ],
                'specialties' => [
                    'Mi Quang turmeric frog and shrimp noodles',
                    'Sliced pork belly rolled in fresh rice paper',
                    'Be Man fresh seafood feast on My Khe beachfront',
                    'Banh xeo crispy Central-style pancakes',
                ],
                'views_count' => 2450,
                'content' => "Voted Vietnam's most livable modern metropolis, Da Nang harmoniously blends pristine coastlines, urban excitement, and sacred mountain reserves.

### Top Attractions
1. **Dragon Bridge Fire & Water Show:** Gather along the Han River waterfront every Saturday and Sunday evening at 9 PM to watch the 666-meter steel dragon breathe roaring flames and water bursts.
2. **Golden Bridge at Sun World Ba Na Hills:** Walk along the surreal pedestrian bridge held gently aloft by giant mossy stone hands high above cloud-draped peaks.
3. **Son Tra Peninsula:** Explore coastal rainforests home to endangered red-shanked douc langurs and the towering 67-meter Lady Buddha statue at Linh Ung Pagoda.

### Must-Try Gastronomy
- **Mi Quang Frog & Shrimp Noodles:** Golden turmeric-infused noodles dressed in rich spiced broth with toasted peanuts and rice crackers.
- **Pork Rice Paper Rolls:** Fresh thinly sliced pork rolled with green herbs, mango slivers, and piquant fermented anchovy dipping sauce.",
            ],
            [
                'region_slug' => 'central-highlands',
                'destination_name' => 'Da Lat',
                'title' => 'Da Lat: Highland Mist, Pine Forests & Artisan Coffee',
                'slug' => 'da-lat-highland-mist-pine-forests-artisan-coffee',
                'category' => 'cuisine',
                'location_name' => 'Lam Dong Province, Central Highlands',
                'summary' => 'A refreshing highland sanctuary blessed with cool year-round climate, mist-cloaked pine forests by Tuyen Lam Lake, and hillside cloud-hunting cafes.',
                'best_season' => 'October – March (Wild sunflowers, cherry blossoms & early morning cloud seas)',
                'estimated_duration' => '2–3 Days',
                'featured_image' => 'images/destinations/dalat.jpg',
                'highlights' => [
                    'Sunrise cloud hunting at Cau Dat tea plantation',
                    'Stand-up paddleboarding (SUP) across Tuyen Lam Lake',
                    'Langbiang mountain peak trekking and pine camping',
                    'Artisanal Arabica coffee tasting at hillside roasteries',
                ],
                'specialties' => [
                    'Lau Ga La E (Chicken lemon-basil hotpot)',
                    'Banh Trang Nuong (Crispy Vietnamese street pizza)',
                    'Steamed chicken rice paper rolls (Banh Uot Long Ga)',
                    'Hot soy milk with fragrant French pastries',
                ],
                'views_count' => 1980,
                'content' => 'Perched 1,500 meters above sea level on the Langbiang Plateau, Da Lat is renowned for its crisp temperate climate, colonial French architecture, and thriving artisanal coffee plantations.

### Highland Experiences
1. **Cloud Hunting at Cau Dat:** Arrive before sunrise to watch billowy oceans of morning clouds drift gently across emerald tea hills and wind turbine farms.
2. **Stand-Up Paddleboarding (SUP) on Tuyen Lam Lake:** Glide along mirror-still lake waters fringed with weeping pine forests in early morning mist.
3. **Langbiang Peak Trekking:** Hike through highland evergreen forests to sweeping viewpoints overlooking the Golden and Silver streams.

### Comforting Night Bites
- **Chicken Leaf Hotpot (Lau Ga La E):** Succulent chicken simmered with aromatic lemon-basil leaves and green bamboo shoots.
- **Vietnamese Pizza (Banh Trang Nuong):** Crispy grilled rice paper topped with butter, quail eggs, scallions, dried shrimp, and cheese roasted over charcoal.
- **Warm Soy Milk & Pastries:** Enjoyed curbside at Da Lat night market on cool autumn evenings.',
            ],
            [
                'region_slug' => 'southern-vietnam',
                'destination_name' => 'Phu Quoc',
                'title' => 'Phu Quoc: Tropical Sunsets, Sao Beach & Coral Reefs',
                'slug' => 'phu-quoc-tropical-sunsets-sao-beach-coral-reefs',
                'category' => 'nature',
                'location_name' => 'Kien Giang Province, Southern Vietnam',
                'summary' => 'Crystal-clear turquoise waters, silky soft sand at Bai Sao, and legendary fiery crimson sunsets across the Southwestern sea horizon.',
                'best_season' => 'November – April (Calm turquoise waters, sunny days & gentle offshore winds)',
                'estimated_duration' => '3–4 Days',
                'featured_image' => 'images/destinations/phuquoc.jpg',
                'highlights' => [
                    'Bai Sao crescent beach relaxation and sea swing photo',
                    'Hon Thom 7.9km cross-sea cable car journey',
                    'Snorkeling shallow coral gardens in An Thoi archipelago',
                    'Sunset dining at Sunset Sanato & Chuon Chuon Bistro',
                ],
                'specialties' => [
                    'Goi Ca Trich (Raw herring salad with grated coconut)',
                    'Bun Quay Kien Xay (Freshly pressed stirred noodles)',
                    'Ham Ninh steamed blue crab with lime-pepper dip',
                    'Sim wine and traditional barrel-aged Phu Quoc fish sauce',
                ],
                'views_count' => 2680,
                'content' => "Nestled in the warm waters of the Gulf of Thailand, Phu Quoc is Vietnam's premier island escape, famed for powdery white sands, untamed national park wilderness, and fiery crimson sunsets.

### Island Highlights
1. **Sao Beach (Bai Sao):** Walk along crescent-shaped shores of powdery white sand and wade into crystal-clear turquoise waters shaded by coconut palms.
2. **Hon Thom Cable Car:** Soar across the turquoise sea on the world's longest 3-rope sea cable car spanning 7.9 kilometers to Hon Thom Nature Park.
3. **An Thoi Island Hopping & Snorkeling:** Take a wooden boat out to Gam Ghi and May Rut islands to snorkel among pristine shallow-water coral reefs.

### Fresh Island Gastronomy
- **Raw Herring Salad (Goi Ca Trich):** Freshly sliced raw silver herring tossed with shredded coconut, lime juice, mint, and crushed peanuts, wrapped in delicate rice paper.
- **Kien Xay Stirred Noodles (Bun Quay):** Self-service noodles freshly pressed into bowls with hot shrimp paste broth and customized chili-calamansi dipping sauces.",
            ],
        ];

        foreach ($articles as $art) {
            $region = $regionModels[$art['region_slug']] ?? null;
            $dest = $destinations[$art['destination_name']] ?? null;

            if ($region) {
                DiscoveryArticle::updateOrCreate(
                    ['slug' => $art['slug']],
                    [
                        'region_id' => $region->id,
                        'destination_id' => $dest?->id,
                        'title' => $art['title'],
                        'category' => $art['category'],
                        'location_name' => $art['location_name'],
                        'summary' => $art['summary'],
                        'best_season' => $art['best_season'],
                        'estimated_duration' => $art['estimated_duration'],
                        'featured_image' => $art['featured_image'],
                        'highlights' => $art['highlights'],
                        'specialties' => $art['specialties'],
                        'views_count' => $art['views_count'],
                        'content' => $art['content'],
                        'is_published' => true,
                    ]
                );
            }
        }
    }
}
