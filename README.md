<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

# Wanderly Verified Itinerary Architecture (34 Provinces of Vietnam)

Wanderly AI Travel Assistant implements a verified, hallucination-free itinerary planning pipeline for Vietnam according to the **Resolution 202/2025/QH15** (34 official provinces and cities).

### Core Principle
> **The LLM never recalls facts from memory.** It only selects and orders places from a reviewed, verified dataset (`data/places/*.json`), and every output is checked by deterministic code before it is rendered to the user.

### Key Components

1. **Province Resolver (`App\Services\Itinerary\ProvinceResolver`)**
   - Exact & accent-insensitive fuzzy matching for 34 official provincial units.
   - Comprehensive alias map for historical merged units (e.g. Hà Tây -> Hà Nội, Quảng Nam -> Đà Nẵng).
   - Administrative units cleaner stripping out `quận/huyện/thị xã`.
   - Never guesses; prompts for clarification if the input is ambiguous.

2. **Verified Place Dataset (`data/places/*.json` & `PlaceRepository`)**
   - 150+ rigorously reviewed tourist destinations across key provinces (Đà Nẵng, Lâm Đồng, Quảng Ninh, Hà Nội, Khánh Hòa).
   - Strict schema: coordinates, category, opening hours, ticket price, duration, best/avoid months, indoor flag.
   - Excludes unverified and draft entries by default.
   - Legal attributions: © OpenStreetMap contributors (ODbL) and Wikivoyage (CC BY-SA 4.0).

3. **Travel Time & Distance Matrix (`TravelTimeService`)**
   - Haversine great-circle formula with realistic road detour factors (1.38x).
   - Region-specific terrain speeds: highlands/mountainous (1.35x - 1.45x) vs plains/coasts (1.20x - 1.25x).
   - Island ferry/crossing penalties (Cù Lao Chàm, Phú Quốc, Cô Tô).
   - Precomputation command: `php artisan travel:matrix`.

4. **Weather & Season Service (`OpenMeteoWeatherService`)**
   - Live 16-day forecast from Open-Meteo API with disk cache.
   - Graceful fallback to regional climate hazard rules (Central storm season in Sep-Nov, North winter cold, Mekong flooding).
   - Attribution: *Weather data by Open-Meteo.com (CC BY 4.0)*.

5. **Planner with Repair Loop (`ItineraryPlannerService`)**
   - LLM functions strictly as a selector and orderer over provided verified candidates.
   - Repair loop retries up to 2 times with targeted validator feedback.
   - Guaranteed deterministic greedy fallback if LLM fails or is offline.

6. **Deterministic Validator (`ItineraryValidator`)**
   - 9 deterministic rules: place existence, no duplicates, operating hours & closed days, daily travel time <= 180 min, consecutive distance <= 60 km, avoid months, weather outdoor safety, item bounds, and meal slots.

7. **One-Click Trip Creation**
   - Users can review the verified itinerary card in the AI drawer (with Google Maps links, ticket prices, hours) and click **"Tạo chuyến đi từ lịch trình này"** to generate an actual `Trip` and daily `Activity` records in Wanderly.

### Artisan Commands
```bash
# Validate places dataset schema
php artisan places:validate

# Offline crawler to build place dataset
php artisan places:build {province}

# Precompute travel time matrix
php artisan travel:matrix

# Run evaluation harness
php eval/run_eval.php
```

