# Wanderly — Base Code Preparation Guide

[Mock Repo: wanderly-demo](https://github.com/mock-repo/wanderly)

> Tài liệu chuẩn bị nền tảng code trước khi bắt đầu phát triển tính năng.
> **Nguồn yêu cầu:** OSP_SRS v2.0 — Wanderly
> **Mục tiêu:** Tạo codebase Laravel ổn định, module rõ ràng cho Phase 1 (ưu tiên Auth, Trip, Timeline, Budget, Room, Checklist, Public Share).

---

## 1. Stack Công nghệ
- **Backend:** PHP 8.2+, Laravel 11
- **Frontend:** Blade + Tailwind CSS 3 + Alpine.js 3, Vite, Node 20
- **Database:** MySQL 8.0 (InnoDB, `utf8mb4`, `utf8mb4_unicode_ci`)
- **Libraries:** SortableJS (Drag & Drop), Chart.js
- **Đa ngôn ngữ (Localization):** Tiếng Anh (`en`) là ngôn ngữ chính. Toàn bộ UI và thông báo bọc trong `__('...')`, cấu trúc sẵn sàng mở rộng sang Tiếng Việt (`vi`) qua JSON translation.
- **Khác:** Nginx + PHP-FPM, PHPUnit/Pest, Laravel Pint, Git

---

## 2. Nguyên tắc Kiến trúc & Request Flow
**Controller không chứa business logic.** Logic nằm ở `app/Services/`.
Mọi request đi qua flow chuẩn:
`Browser → Route → Middleware → FormRequest → Controller → Policy → Service → Model/DB → Event → Response`

---

## 3. Cấu trúc Repository Đề xuất
```text
Wanderly/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Policies/
│   └── Services/ (BudgetService, ItineraryService, TripService...)
├── config/
├── database/ (migrations, seeders, factories)
├── docs/          <-- Thư mục tài liệu dự án
├── public/
├── resources/ (css, js, views)
├── routes/ (web.php, ajax.php)
├── tests/
└── .env.example
```
*Các module phân định rõ ràng ranh giới nghiệp vụ (Trip, Auth, Budget...)*

---

## 4. Database & Model Foundation
- Dùng `DECIMAL` cho tiền tệ, dùng transaction cho các nghiệp vụ thanh toán.
- Các Model chính: `User`, `Trip`, `TripMember`, `Activity`, `RoomBooking`, `ChecklistItem`.
- Chuẩn bị sẵn Seed data (Admin, Demo traveler, Destinations, Zones, POIs) phục vụ demo và test.

---

## 5. Authentication & Authorization
- **Auth:** Hash password bcrypt, throttle login (5 lần/phút).
- **Phân quyền hệ thống:** `traveler`, `provider`, `admin`.
- **Phân quyền Trip:** `leader`, `editor`, `viewer`. Phải dùng `TripPolicy` kiểm tra ở phía server, không chỉ ẩn UI frontend.

---

## 6. Services & Events
- **BudgetService:** Tính toán total cost, budget còn lại, xử lý threshold (warning/danger).
- **ItineraryService:** Xử lý kéo thả timeline, update atomic slot và vị trí trong transaction.
- **Events:** Dùng event (vd: `ActivityCostChanged` -> `RecomputeBudget`) để hệ thống decouple và dễ bảo trì.

---

## 7. Frontend & AJAX Foundation
- **Layout:** `app.blade.php` chứa navigation, flash/toast, global modals.
- **Alpine.js:** Tránh viết file JS khổng lồ, chia thành các module nhỏ (timeline.js, budget.js) ở `resources/js/modules/`.
- **AJAX:** Mọi route cần CSRF, auth middleware, validate bằng FormRequest, trả về JSON chuẩn hóa.

---

## 8. Các Tính Năng Nền Tảng Chính (Phase 1)
- **Timeline / Drag & Drop (SortableJS):** 4 slots (Sáng, Trưa, Chiều, Tối). Cho phép đổi thứ tự, đổi ngày, đổi slot (Frontend optimistic - Backend authoritative).
- **Budget:** Cảnh báo các ngưỡng (80% warning, 95% danger). Role `viewer` không được nhận raw budget từ server API.
- **Room Booking:** Là một entity độc lập. Cho phép kéo thả vào timeline, tuỳ chọn tính hoặc không tính vào tổng budget.
- **Public Share:** URL public dạng `/s/{slug}` (Read-only, có thể revoke, ẩn budget theo tuỳ chọn).

---

## 9. Security, Testing & Error Handling
- **Security:** Tránh SQL Injection (dùng Eloquent), XSS (Blade escape), xác thực kỹ File Upload. Không bao giờ commit file `.env`.
- **Testing:** Unit test các logic tính toán (Budget, Itinerary). Feature test các API quan trọng. Target coverage > 80%.
- **Error Handling:** Trả về response HTTP chuẩn (422, 403, 404, 500) và format JSON nhất quán cho AJAX.

---

## 10. Checklist Acceptance Criteria
- [ ] Code pass linting `php artisan pint`.
- [ ] Đạt Unit/Feature Test, coverage hợp lý.
- [ ] Pass Health check (`/healthz`).
- [ ] API Response và Authorization đảm bảo phân quyền chặt chẽ.
- [ ] Hoạt động demo xuyên suốt: Tạo Trip -> Kéo thả activity -> Budget update -> Link Share hoạt động.
- [ ] File README.md đầy đủ hướng dẫn setup repo cho người mới.
