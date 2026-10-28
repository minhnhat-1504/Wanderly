# Wanderly — Smart Travel Planning & Reverse Marketplace Platform

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="220" alt="Wanderly Platform" style="margin-bottom: 12px;">
  <br>
  <strong>Nền tảng lập kế hoạch du lịch cộng tác, quản lý ngân sách nhóm & sàn dịch vụ du lịch thông minh tại Việt Nam</strong>
</p>

---

## 🌟 Giới thiệu tổng quan (About Wanderly)

**Wanderly** là nền tảng quản lý chuyến đi toàn diện, kết hợp giữa lập kế hoạch cộng tác theo nhóm (collaborative itinerary planning), quản lý ngân sách trực tiếp (live budget tracking), chia tiền minh bạch tích hợp mã thanh toán VietQR (split-bill & settlements), và sàn dịch vụ du lịch đấu thầu ngược (Reverse Marketplace). 

Đặc biệt, hệ thống tích hợp **Trợ lý du lịch AI (Wanderly AI Travel Assistant & Co-pilot)** với cơ chế lập lịch trình xác thực (Verified Itinerary) chống ảo giác (zero hallucination) trên **34 tỉnh thành Việt Nam** (theo Nghị quyết 202/2025/QH15).

---

## 🚀 Các tính năng nổi bật (Core Features)

1. **Lập lịch trình linh hoạt theo khung giờ (Timeline Planner):**
   - Phân bổ hoạt động trực quan theo các khung giờ cố định: Sáng (06:00–11:00), Trưa (11:00–14:00), Chiều (14:00–18:00), Tối (18:00–23:59).
   - Kéo thả sắp xếp lại thứ tự hoạt động mượt mà (SortableJS).
   - Tự động kiểm tra xung đột thời gian và cảnh báo lộ trình di chuyển.

2. **Quản lý ngân sách (Live Budget) & Chia tiền nhóm (Split-Bill VietQR):**
   - Theo dõi chi phí trực tiếp theo thời gian thực (Live Budget Status).
   - Cơ chế chọn tham gia hoạt động (opt-in attendance): ngân sách chỉ tính toán dựa trên số lượng người thực tế chọn đi hoạt động đó.
   - Thuật toán tối ưu hóa số giao dịch trả tiền (Greedy Minimum Cash Flow Algorithm).
   - Tạo mã chuyển khoản ngân hàng **VietQR** động kèm số tiền và nội dung chuẩn hóa.

3. **Sàn dịch vụ du lịch đấu thầu ngược (Reverse Marketplace):**
   - Du khách đăng yêu cầu dịch vụ (Travel Request: xe đưa đón, tour, phòng ở, ngân sách mong muốn).
   - Các nhà cung cấp dịch vụ địa phương (Local Providers) tại điểm đến gửi đề xuất báo giá cạnh tranh (Proposals).
   - Ma trận so sánh báo giá (Comparison Matrix) và phân tích đề xuất thông minh bằng AI.
   - Hỗ trợ cả hai hình thức: **Chốt trọn gói (Accept Full Bundle)** hoặc **Nhặt lẻ từng dịch vụ (Pick Single Item)** từ các nhà cung cấp khác nhau.
   - Kênh trao đổi trực tiếp 1-1 (Direct Booking Chat) giữa Du khách và Nhà cung cấp dịch vụ.

4. **Trợ lý AI & Lịch trình xác thực 34 tỉnh thành (Verified Itinerary Co-pilot):**
   - Ứng dụng mô hình Gemini API kết hợp cùng kho dữ liệu địa điểm thực tế (`data/places/*.json`).
   - Tuyệt đối không sinh địa điểm ảo; tự động tính toán thời gian di chuyển thực tế (Travel Time Matrix), kiểm tra giờ mở cửa và điều kiện thời tiết (Open-Meteo API).
   - Cho phép tạo ngay chuyến đi và các hoạt động chi tiết vào hệ thống chỉ với 1 cú click.

5. **Xuất lịch trình PDF (PDF Export):**
   - Xuất toàn bộ kế hoạch chuyến đi, thông tin lưu trú, danh sách chuẩn bị và bản tóm tắt tài chính ra file PDF chuyên nghiệp.

---

## 🛠 Yêu cầu hệ thống (Prerequisites)

Trước khi cài đặt, hãy đảm bảo máy tính của bạn đã cài đặt các công cụ sau:
- **PHP:** `>= 8.2` (Bật các extension: `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `gd`, `openssl`, `xml`)
- **Composer:** `>= 2.x`
- **Node.js:** `>= 18.x` & **NPM:** `>= 9.x`
- **Cơ sở dữ liệu:** MySQL hoặc MariaDB `>= 8.0` (Khuyên dùng môi trường **Laragon** trên Windows hoặc XAMPP / Docker)

---

## 💻 Hướng dẫn cài đặt & Khởi chạy dự án (Installation & Setup)

Thực hiện theo các bước chi tiết dưới đây để thiết lập dự án từ đầu:

### Bước 1: Clone mã nguồn
Mở terminal/PowerShell và clone repository về máy:
```bash
git clone https://github.com/minhnhat-1504/Wanderly.git
cd Wanderly
```

### Bước 2: Cài đặt các gói phụ thuộc PHP (Composer)
```bash
composer install
```

### Bước 3: Cài đặt các gói phụ thuộc Frontend (NPM)
```bash
npm install
```

### Bước 4: Thiết lập file môi trường (.env)
Tạo bản sao từ file mẫu `.env.example`:
- **Trên Windows (cmd / PowerShell):**
  ```bash
  copy .env.example .env
  ```
- **Trên Linux / macOS:**
  ```bash
  cp .env.example .env
  ```

Tạo khóa mã hóa ứng dụng (Application Key):
```bash
php artisan key:generate
```

Mở file `.env` và cấu hình các thông số phù hợp:
```env
APP_NAME=Wanderly
APP_URL=http://127.0.0.1:8000

# Cấu hình Cơ sở dữ liệu MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wanderly
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database

# Cấu hình Google Gemini AI
AI_PROVIDER=gemini
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-3.5-flash

# Cấu hình Mail (Gửi email xác thực tài khoản)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@wanderly.test"
MAIL_FROM_NAME="${APP_NAME}"
```
*(Lưu ý: Tạo database rỗng có tên `wanderly` trong MySQL trước khi chạy bước tiếp theo).*

### Bước 5: Chạy Database Migrations & Seeders
Khởi tạo toàn bộ cấu trúc bảng và nạp dữ liệu mẫu (các điểm đến, tài khoản demo, dịch vụ, chuyến đi mẫu):
```bash
php artisan migrate --seed
```

### Bước 6: Tạo liên kết Storage (Public Storage Link)
Để hiển thị hình ảnh đại diện, banner chuyến đi và tài liệu tải lên:
```bash
php artisan storage:link
```

### Bước 7: Khởi chạy dự án
Chạy 2 tiến trình sau song song trong 2 cửa sổ terminal:

- **Terminal 1 — Backend Server:**
  ```bash
  php artisan serve
  ```
  Ứng dụng sẽ hoạt động tại: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

- **Terminal 2 — Frontend Asset Bundler (Vite):**
  ```bash
  npm run dev
  ```

---

## 🔑 Tài khoản Demo sẵn có (Pre-seeded Accounts)

Sau khi chạy lệnh `php artisan migrate --seed`, hệ thống đã có sẵn các tài khoản sau để bạn đăng nhập thử nghiệm ngay mà không cần tạo mới:

| Vai trò (Role) | Email | Mật khẩu | Mô tả |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@wanderly.test` | `password` | Quản trị viên hệ thống, quản lý danh mục và duyệt dịch vụ |
| **Traveler** | `demo@wanderly.test` | `password` | Khách du lịch mẫu (chủ chuyến đi Đà Lạt 4 ngày 3 đêm) |
| **Service Provider** | `provider@wanderly.test` | `password` | Nhà cung cấp dịch vụ địa phương (Dalat Pine Explorer Travel) |

Đường dẫn trang quản trị nhà cung cấp: **[http://127.0.0.1:8000/provider/dashboard](http://127.0.0.1:8000/provider/dashboard)**

---

## 🧪 Chạy kiểm thử tự động (Automated Testing)

Hệ thống Wanderly được bao phủ bởi bộ kiểm thử tự động toàn diện (Feature & Unit Tests) cho các tính năng cốt lõi:
```bash
# Chạy toàn bộ test suite
php artisan test

# Chạy kiểm thử theo từng tính năng chuyên biệt
php artisan test --filter="BudgetTest"
php artisan test --filter="TimelineTest"
php artisan test --filter="SplitBillTest"
php artisan test --filter="MarketplaceTest"
php artisan test --filter="ProposalOptimizerTest"
php artisan test --filter="VerifiedItineraryAiCopilotTest"
```

---

## 🗺 Kiến trúc Lịch trình AI Xác thực 34 Tỉnh Thành (Verified Itinerary)

Hệ thống AI Travel Assistant tuân thủ nghiêm ngặt nguyên tắc **Zero-Hallucination**:
> **LLM không tự nhớ hay bịa địa điểm từ bộ nhớ.** Model chỉ đóng vai trò phân loại, chọn lọc và sắp xếp các địa điểm từ tập dữ liệu đã qua rà soát thủ công (`data/places/*.json`), và mọi kết quả đều được kiểm tra qua bộ quy tắc xác thực (Deterministic Validator) trước khi hiển thị cho người dùng.

### Các lệnh Artisan hỗ trợ:
```bash
# Kiểm tra tính hợp lệ của schema dữ liệu địa điểm
php artisan places:validate

# Thu thập dữ liệu địa điểm ngoại tuyến cho một tỉnh thành
php artisan places:build {province}

# Tính toán ma trận khoảng cách và thời gian di chuyển
php artisan travel:matrix

# Chạy harness đánh giá chất lượng AI
php eval/run_eval.php
```

---

## 📄 Bản quyền (License)

Dự án được phát triển trên nền tảng mã nguồn mở [Laravel Framework](https://laravel.com) theo giấy phép [MIT license](https://opensource.org/licenses/MIT).
