# 🎙️ KỊCH BẢN THUYẾT TRÌNH DEMO HỆ THỐNG WANDERLY
**Hình thức trình bày:** Văn nói trực tiếp kết hợp thao tác hệ thống (Talk-Track with Live Demo)  
**Thời lượng chuẩn:** 12 – 15 phút  
**Quy ước nhân vật:** Người dùng A (Trưởng nhóm), Người dùng B (Thành viên), Đối tác dịch vụ X  
**Phong cách:** Khách quan, nhã nhặn, tự nhiên, tập trung vào giải pháp kỹ thuật và thao tác trực quan  
**Tài liệu tham chiếu:** [docs/SRS.md](file:///d:/laragon/www/Wanderly/docs/SRS.md)

---

## 🧭 BẢNG PHÂN VAI & CỬA SỔ HIỂN THỊ TRÌNH DIỄN

| Cửa sổ hiển thị | Tài khoản đăng nhập | Vai trò đại diện | Màn hình khởi đầu |
| :--- | :--- | :--- | :--- |
| **Cửa sổ 1 (Trình duyệt chính)** | `demo@wanderly.test` (pass: `password`) | **Người dùng A (Trưởng nhóm du lịch)** | `/trips` (Bảng điều khiển chuyến đi) |
| **Cửa sổ 2 (Trình duyệt ẩn danh 1)** | `provider@wanderly.test` (pass: `password`) | **Đối tác dịch vụ X (Đơn vị cung ứng)** | `/provider/dashboard` (Cổng đối tác) |
| **Cửa sổ 3 (Trình duyệt ẩn danh 2)** | `member@wanderly.test` (pass: `password`) | **Người dùng B (Thành viên trong nhóm)** | `/social` hoặc `/trips` |
| *Cửa sổ dự phòng (nếu cần xem Admin)* | `admin@wanderly.test` (pass: `password`) | **Quản trị viên hệ thống (Admin)** | `/admin/destinations` |


---

## ⏱️ DÒNG THỜI GIAN VÀ LỜI THOẠI CHI TIẾT (12 – 15 PHÚT)

---

### PHẦN 1: MỞ ĐẦU & CỔNG KHÁM PHÁ (0:00 - 1:15)
*Mục tiêu: Đặt vấn đề bài toán thực tế, giới thiệu Cổng khám phá dành cho khách vãng lai.*

> 🗣️ **Lời thoại thuyết trình:**
>
> *"Kính thưa quý thầy cô trong hội đồng và các bạn!*  
> *Khi lên kế hoạch cho một chuyến đi du lịch tự túc theo nhóm từ 3 đến 5 người, chúng ta thường gặp phải các vấn đề quen thuộc: bất đồng sở thích giữa các thành viên, sắp xếp lịch trình di chuyển dễ bị ngược đường, khó khăn khi tìm kiếm đối tác dịch vụ tin cậy và sự phức tạp khi quyết toán các khoản chi tiêu chung sau chuyến đi.*
>
> *Hệ thống **Wanderly** được phát triển nhằm kết nối toàn bộ các giai đoạn này trên một nền tảng thống nhất, từ khâu tìm cảm hứng, cộng tác nhóm, làm việc với nhà cung cấp dịch vụ cho tới quyết toán tài chính.*
>
> *[Thao tác: Mở trang Cổng khám phá `/discovery`]*  
> *Đầu tiên, đối với người dùng vãng lai chưa cần tài khoản, hệ thống cung cấp **Cổng khám phá Du lịch Việt Nam**, tổng hợp thông tin văn hóa, ẩm thực đặc trưng theo 3 miền.*
>
> *[Thao tác: Nhấp vào một bài viết về điểm đến, chỉ vào nút 'Thêm 1-click vào chuyến đi']*  
> *Tại mỗi điểm đến, người dùng có thể bấm nút thêm nhanh vào kế hoạch của mình mà không cần nhập lại dữ liệu thủ công. Tiếp theo, em xin phép đăng nhập vào tài khoản của **Người dùng A trong vai trò Trưởng nhóm** để bắt đầu quy trình tổ chức chuyến đi thực tế."*

---

### PHẦN 2: THIẾT LẬP CHUYẾN ĐI, GIÁM SÁT NGÂN SÁCH 3 NGƯỠNG & TIỀN PHÒNG ĐỘC LẬP (1:15 - 3:00)
*Mục tiêu: Lướt nhanh CRUD cơ bản, tập trung vào Giám sát ngân sách thời gian thực và quản lý phòng lưu trú.*

> 🗣️ **Lời thoại thuyết trình:**
>
> *[Thao tác: Đăng nhập Người dùng A, mở chuyến đi mẫu 4 ngày 3 đêm tại `/trips/{id}`]*  
> *"Dạ đây là giao diện chi tiết chuyến đi của nhóm gồm Người dùng A, Người dùng B và Người dùng C với tổng ngân sách dự kiến là 10.000.000 VNĐ. Các thao tác cơ bản như sửa ngày hay quản lý checklist hành trang hệ thống đều xử lý mượt mà. Em xin phép tập trung vào cơ chế quản lý ngân sách:*
>
> *[Thao tác: Rê chuột vào widget Ngân sách màu vàng cam ở góc trên giao diện]*  
> *Thầy cô có thể quan sát widget ngân sách ở góc trên. Thuật toán giám sát tốc độ tiêu hao chi phí phân bổ thành 3 ngưỡng trạng thái trực quan: màu xanh lá khi chi tiêu an toàn dưới 80%, màu vàng cam cảnh báo khi ở mức 80% đến 95%, và màu đỏ khi vượt quá 95%. Hiện tại, mức chi tiêu đang ở mức **82%** (tương đương 8.200.000 VNĐ), do đó thanh trạng thái tự động chuyển sang màu vàng cam để nhắc nhở nhóm.*
>
> *[Thao tác: Cuộn xuống mục Phòng lưu trú, chỉ vào phòng khách sạn 2.500.000 VNĐ]*  
> *Đặc biệt, chi phí phòng lưu trú được thiết kế độc lập. Trong trường hợp tiền phòng đã được thanh toán riêng hoặc có thỏa thuận khác, người dùng có thể sử dụng công tắc **'Tính vào ngân sách'**:*
>
> *[Thao tác: Gạt tắt công tắc 'Tính vào ngân sách']*  
> *Khi gạt tắt, tổng chi tiêu lập tức giảm trừ 2.500.000 VNĐ, đưa mức chi tiêu về 57% và thanh trạng thái tự động chuyển về **màu xanh an toàn**. Khi cần theo dõi lại, chúng ta chỉ cần bật công tắc trở lại mà không cần tải lại trang."*

---

### PHẦN 3: LỊCH TRÌNH KÉO THẢ TƯƠNG TÁC & TRỢ LÝ AI CO-PILOT CÓ ACTION ENGINE (3:00 - 5:15)
*Mục tiêu: Trình diễn Kéo-thả SortableJS, Kiểm tra trùng giờ thực tế và Cơ chế đề xuất hành động của AI.*

> 🗣️ **Lời thoại thuyết trình:**
>
> *[Thao tác: Cuộn xuống khu vực Timeline 4 khung giờ: Sáng, Trưa, Chiều, Tối]*  
> *"Tiếp theo là khu vực Lịch trình tương tác. Mỗi ngày được chia thành 4 khung thời gian rõ ràng.*
>
> *[Thao tác: Nhấp giữ và kéo một thẻ hoạt động từ Buổi sáng sang Buổi chiều]*  
> *Người dùng có thể kéo thả hoạt động giữa các khung giờ hoặc chuyển sang ngày khác. Nhờ việc áp dụng SortableJS kết hợp Optimistic UI, vị trí được cập nhật tức thì trên giao diện và máy chủ lưu ngầm với độ trễ dưới 100ms. Nếu có hai hoạt động bị xếp trùng khung giờ thực tế, thuật toán Interval Overlap Test sẽ gắn cờ cảnh báo màu cam để nhóm lưu ý.*
>
> *[Thao tác: Nhấp vào biểu tượng Trợ lý AI ở góc dưới bên phải, cửa sổ chat mở ra]*  
> *Bên cạnh đó, hệ thống tích hợp **Trợ lý AI Co-pilot** với cơ chế **Action Engine**:*
>
> *[Thao tác: Gửi tin nhắn: 'Gợi ý quán lẩu bò ngon cho buổi trưa Ngày 2 với ngân sách dưới 500k']*  
> *Khi người dùng đặt câu hỏi, hệ thống tự động thu thập ngữ cảnh thực tế của chuyến đi: điểm đến ở đâu, ngân sách khả dụng còn lại bao nhiêu, và khung giờ nào đang trống để nạp vào prompt.*
>
> *[Thao tác: Chỉ vào Thẻ hành động (Action Card) có nút bấm xuất hiện trong khung chat]*  
> *Kết quả phản hồi của AI không chỉ là văn bản tư vấn thông thường, mà được cấu trúc thành một **Thẻ hành động cụ thể**: chỉ rõ tên địa điểm, thời gian đề xuất là 11:30 Ngày 2, chi phí dự kiến 350.000 VNĐ, đi kèm nút bấm **'Chấp nhận thêm vào lịch trình'**.*
>
> *[Thao tác: Bấm nút 'Chấp nhận thêm vào lịch trình']*  
> *Sau khi người dùng bấm xác nhận, hoạt động này được tự động ghi nhận vào cơ sở dữ liệu và hiển thị ngay trên cột Buổi trưa của Ngày 2, đồng thời tổng ngân sách chuyến đi cũng được tính toán tăng thêm tương ứng."*

---

### PHẦN 4: GÓC NHÌN THÀNH VIÊN – GU NHÓM (GROUP MOOD) & GỢI Ý MATCH ENGINE (5:15 - 7:15)
*Mục tiêu: Trình diễn vai trò Thành viên (Người dùng B), thanh đo hòa hợp và thuật toán gợi ý thuận tuyến đường.*

> 🗣️ **Lời thoại thuyết trình:**
>
> *[Thao tác: Chuyển sang Cửa sổ 3 - Tài khoản Người dùng B (Thành viên nhóm)]*  
> *"Tiếp theo, em xin chuyển sang góc nhìn của **Người dùng B - thành viên được mời tham gia nhóm** qua mã token bảo mật 7 ngày.*
>
> *[Thao tác: Nhấp vào tab 'Gu của nhóm' (Group Mood)]*  
> *Để giải quyết vấn đề bất đồng ý kiến giữa các thành viên, Wanderly xây dựng tính năng **Phân tích độ hòa hợp nhóm (Group Mood Harmony)**. Hệ thống phân tích nhịp độ di chuyển và các thẻ sở thích của từng người, sau đó tính toán chỉ số hòa hợp tập thể – hiện tại đang ghi nhận mức **85% Harmony**.*
>
> *[Thao tác: Mở danh sách ứng viên, thực hiện Upvote (+1) và Downvote (-1) vào một số địa điểm]*  
> *Mỗi thành viên có thể thực hiện bình chọn tán thành (+1) hoặc không tán thành (-1) trên danh sách địa điểm đề xuất, điểm số đồng thuận ròng được cập nhật liên tục.*
>
> *[Thao tác: Nhấn nút 'Tạo lịch trình gợi ý từ Gu nhóm' (Match Engine)]*  
> *Khi kích hoạt tính năng **Match Engine**, thuật toán phân tích đa tiêu chí (MCDA) kết hợp với kỹ thuật gom cụm địa lý (Zone Clustering) sẽ đánh giá điểm bình chọn, độ hòa hợp và khoảng cách thực tế giữa các điểm. Nhờ đó, các địa điểm cùng một khu vực sẽ được xếp vào cùng một ngày, giúp lộ trình di chuyển của nhóm được hợp lý hơn và hạn chế việc chạy xe ngược đường."*

---

### PHẦN 5: SÀN DỊCH VỤ ĐẢO, ĐỐI TÁC X & CHAT TRỰC TIẾP 1-1 (7:15 - 9:45)
*Mục tiêu: Mô hình đấu thầu ngược, AI phát hiện phụ phí ẩn, Đối tác X chặn lịch bận, và Chat trực tiếp giữa Khách và Đối tác.*

> 🗣️ **Lời thoại thuyết trình:**
>
> *[Thao tác: Quay lại Cửa sổ 1 của Trưởng nhóm, nhấp vào tab 'Sàn dịch vụ' (Marketplace)]*  
> *"Một điểm đổi mới khác là mô hình **Sàn dịch vụ đảo (Reverse Marketplace)**. Trưởng nhóm chỉ cần đăng một yêu cầu dịch vụ cụ thể: nhóm cần thuê xe 7 chỗ và tour trải nghiệm trong 2 ngày với ngân sách trần 3.500.000 VNĐ. Các đơn vị cung ứng địa phương sẽ tiếp nhận và gửi báo giá cạnh tranh.*
>
> *[Thao tác: Nhấp vào nút 'So sánh báo giá' (Compare Proposals)]*  
> *Tại đây, **Đối tác dịch vụ X** đã gửi một đề xuất trọn gói trị giá 2.500.000 VNĐ. Để hỗ trợ người dùng đánh giá, hệ thống cung cấp tính năng phân tích bằng AI:*
>
> *[Thao tác: Chỉ vào khu vực nhận xét của AI và huy hiệu Best Value]*  
> *1. AI đọc các thông số kỹ thuật chi tiết và đưa ra cảnh báo: **'Lưu ý: Báo giá chưa bao gồm vé tham quan cổng 50.000 VNĐ'**, giúp người dùng nhận diện sớm các chi phí phát sinh ngoài hợp đồng.*  
> *2. Công cụ Cố vấn ngân sách cung cấp thêm tùy chọn **Mua lẻ (Unbundled Optimization)**: nếu nhóm chỉ thuê riêng xe với giá 1.600.000 VNĐ, nhóm có thể tiết kiệm được 900.000 VNĐ so với việc chọn trọn gói.*  
>
> *[Thao tác: Bấm nút 'Chấp nhận báo giá' (Accept Bundle)]*  
> *Khi Trưởng nhóm đồng ý chốt dịch vụ, một đơn đặt chỗ (Booking) được tạo lập kèm **Mã Voucher QR xác nhận**.*
>
> *[Thao tác: Chuyển sang Cửa sổ 2 - Cổng đối tác của Đối tác dịch vụ X]*  
> *Ở phía đối tác, **Đối tác dịch vụ X** nhận được đơn đặt chỗ mới trên trang quản lý.  
> *[Thao tác: Mở trang Lịch đối tác `/provider/calendar`, nhấp chọn ngày mai và bấm 'Chặn ngày bận']*  
> *Đối tác cũng có thể chủ động đánh dấu chặn các ngày bận bảo dưỡng xe. Bộ lọc lịch của hệ thống sẽ tự động vô hiệu hóa dịch vụ vào những ngày này trên sàn, tránh việc nhận lịch trùng lặp.*
>
> *[Thao tác: Mở khung Chat trực tiếp 1-1 tại đơn Booking]*  
> *Và đây là tính năng **Trao đổi trực tiếp 1-1 (Direct Booking Chat)**: Người dùng A và Đối tác dịch vụ X có thể nhắn tin trao đổi trực tiếp vị trí đón, thời gian xuất phát và biển số xe ngay trong đơn đặt dịch vụ. Hệ thống áp dụng cơ chế Incremental Polling và hiển thị thông báo chưa đọc (badge unread) theo thời gian thực."*

---

### PHẦN 6: CHAT NHÓM CHUYẾN ĐI & MẠNG XÃ HỘI DU LỊCH (CLONE TRIP) (9:45 - 11:30)
*Mục tiêu: Trình diễn Chat nhóm chuyến đi gửi ảnh, Mạng xã hội du lịch `/social`, Tương tác Like/Comment và tính năng Nhân bản chuyến đi (Clone Trip).*

> 🗣️ **Lời thoại thuyết trình:**
>
> *[Thao tác: Tại Cửa sổ 1, mở ngăn Chat nhóm của chuyến đi]*  
> *"Bên cạnh kênh chat 1-1 với đối tác, hệ thống cung cấp **Kênh Chat nhóm nội bộ chuyến đi**. Tại đây, Người dùng A và Người dùng B có thể nhắn tin trao đổi và chia sẻ hình ảnh địa điểm.*
>
> *[Thao tác: Chuyển sang Cửa sổ 3 của Người dùng B, truy cập trang Mạng xã hội du lịch `/social`]*  
> *Sau khi chuyến đi hoàn thành, các thành viên có thể chia sẻ trải nghiệm lên **Mạng xã hội du lịch cộng đồng (Social Feed)**.*  
> *Trên màn hình là bài viết chia sẻ về hành trình 4 ngày 3 đêm tại Đà Lạt do Người dùng A đăng tải, có gắn kèm thẻ tóm tắt chuyến đi (Trip Card).*
>
> *[Thao tác: Người dùng B nhấn nút 'Thích' (Like) và nhập một bình luận: 'Lịch trình rất chi tiết, cảm ơn bạn đã chia sẻ!']*  
> *Lượt thích và bình luận được đồng bộ ngay lập tức qua AJAX mà không cần tải lại trang.*
>
> *[Thao tác: Rê chuột vào nút 'Nhân bản chuyến đi' (Clone Trip) trên bài viết]*  
> *Và điểm rất hữu ích trên mạng xã hội này là tính năng **'Nhân bản chuyến đi' (Clone Trip)**: Nếu Người dùng B thấy hành trình này phù hợp với kế hoạch sắp tới của mình, chỉ cần nhấn một nút duy nhất:*
>
> *[Thao tác: Nhấn nút 'Nhân bản chuyến đi']*  
> *Toàn bộ khung lịch trình, danh sách hoạt động và danh mục chuẩn bị đều được sao chép nguyên vẹn sang tài khoản của Người dùng B thành một chuyến đi mới. Người dùng B có thể tiếp tục tùy chỉnh mà không mất công lên kế hoạch lại từ đầu."*

---

### PHẦN 7: QUYẾT TOÁN CÔNG NỢ THÔNG MINH & THANH TOÁN VIETQR NAPAS 247 (11:30 - 13:45)
*Mục tiêu: Thuật toán Greedy Min-Cash-Flow tối ưu hóa luồng nợ chéo và sinh mã thanh toán VietQR động.*

> 🗣️ **Lời thoại thuyết trình:**
>
> *[Thao tác: Quay lại Cửa sổ 1, bấm mở ngăn trượt 'Quyết toán công nợ & VietQR']*  
> *"Khi chuyến đi kết thúc, bài toán thường gặp nhất là việc tổng hợp và thanh toán các khoản tiền đã chi tiêu chung.*
>
> *Trong chuyến đi của nhóm:*  
> *- Người dùng A đã thanh toán trước tiền phòng 2.500.000 VNĐ và một bữa ăn 600.000 VNĐ.*  
> *- Người dùng B đã ứng trước tiền vé hoạt động 950.000 VNĐ.*  
> *- Người dùng C đã chi trả tiền nước uống 400.000 VNĐ.*  
> *- Trong đó, có một bữa ăn Người dùng C không tham gia nên chỉ chia đều cho A và B.*
>
> *Nếu tính toán theo cách thông thường, giữa 3 người sẽ hình thành nhiều khoản nợ chéo qua lại.*
>
> *[Thao tác: Chỉ vào bảng phương án quyết toán hiển thị trên màn hình]*  
> *Để tối ưu hóa quy trình này, Wanderly áp dụng giải thuật **Greedy Min-Cash-Flow (Tối thiểu hóa dòng tiền)**: hệ thống tính toán số dư ròng của từng người (khoản đã chi trước trừ đi phần tiêu dùng thực tế), sau đó sắp xếp và ghép cặp bù trừ giữa người thiếu nhiều nhất và người thừa nhiều nhất. Kết quả là toàn bộ mạng lưới nợ được rút gọn lại thành **đúng 2 giao dịch trực tiếp tối thiểu**:*  
> *- Người dùng C chuyển cho Người dùng A: 1.150.000 VNĐ.*  
> *- Người dùng B chuyển cho Người dùng A: 425.000 VNĐ.*  
> *Tất cả các thành viên đều đạt trạng thái cân bằng tài chính sau 2 bước này.*
>
> *[Thao tác: Nhấp vào nút 'VietQR' tại giao dịch của Người dùng C]*  
> *Đồng thời, hệ thống tạo sẵn **Mã VietQR động theo chuẩn Napas 247**:*  
> *- Tự động ánh xạ đúng số tài khoản và ngân hàng thụ hưởng của Người dùng A.*  
> *- Điền chính xác số tiền cần chuyển khoản.*  
> *- Định dạng sẵn nội dung giao dịch có mã nhận diện: `WND_SETTLE_1_U2_TO_U1`.*  
> *Người nợ chỉ cần sử dụng ứng dụng ngân hàng quét mã để thực hiện chuyển tiền nhanh chóng, hạn chế tối đa việc nhầm lẫn thông tin."*

---

### PHẦN 8: HỘ CHIẾU NGOẠI TUYẾN, ALBUM KỶ NIỆM & KẾT LUẬN (13:45 - 15:00)
*Mục tiêu: Tính năng Offline Passport (giả lập ngắt mạng), Album kỷ niệm số, Xuất PDF và tổng kết bài báo cáo.*

> 🗣️ **Lời thoại thuyết trình:**
>
> *[Thao tác: Mở trang Hộ chiếu ngoại tuyến `/trips/{id}/offline-passport`]*  
> *"Trước khi kết thúc phần trình bày, em xin giới thiệu thêm tính năng **Hộ chiếu du lịch ngoại tuyến (Offline Passport)**.*  
> *[Thao tác: Nhấn F12, chuyển mục Network sang chế độ 'Offline' để ngắt mạng, sau đó bấm F5 tải lại trang]*  
> *Trong các chuyến đi thực tế, du khách có thể gặp tình huống mất sóng điện thoại hoặc gián đoạn mạng tại vùng núi, biển đảo. Nhờ cơ chế lưu trữ đệm dữ liệu (LocalStorage Caching), toàn bộ thông tin lịch trình, địa chỉ lưu trú, hotline liên hệ khẩn cấp và mã QR voucher vẫn hiển thị đầy đủ ngay cả khi thiết bị hoàn toàn không có kết nối Internet.*
>
> *[Thao tác: Tắt F12 khôi phục mạng, mở xem trang Album kỷ niệm số và link chia sẻ công khai `/s/{slug}`]*  
> *Bên cạnh đó, hệ thống còn hỗ trợ **Album kỷ niệm số** tự động gom hình ảnh gửi trong khung chat phân loại theo từng ngày hành trình, và tính năng **Xuất tài liệu PDF khổ A4** để lưu trữ hoặc in ấn thuận tiện.*
>
> *Kính thưa quý thầy cô, tổng thể dự án **Wanderly** hướng tới việc kết hợp công nghệ hỗ trợ du khách từ bước lập kế hoạch, phối hợp nhóm, trao đổi với đối tác dịch vụ cho tới khâu quyết toán tài chính cuối cùng và chia sẻ cộng đồng một cách liền mạch và minh bạch.*
>
> *Em xin chân thành cảm ơn quý thầy cô đã lắng nghe và rất mong nhận được những góp ý để nhóm hoàn thiện đề tài hơn nữa ạ!"*

---

## 📌 BẢNG ĐỐI CHIẾU MỐC THỜI GIAN THEO DÕI

| Mốc thời gian | Tên phân đoạn | Nội dung trọng tâm cần làm nổi bật |
| :---: | :--- | :--- |
| **0:00 - 1:15** | Mở đầu & Khách vãng lai | Giới thiệu bài toán thực tế, Cổng khám phá `/discovery`, tính năng thêm nhanh 1-click. |
| **1:15 - 3:00** | Trưởng nhóm A: Ngân sách | Lướt nhanh CRUD; Widget ngân sách 3 ngưỡng màu, bật/tắt công tắc tiền phòng độc lập. |
| **3:00 - 5:15** | Lịch trình & AI Co-pilot | Kéo thả Timeline SortableJS, cảnh báo trùng giờ, AI sinh Action Card tự thêm vào lịch. |
| **5:15 - 7:15** | Thành viên B: Gu nhóm | Mời qua token, chỉ số hòa hợp Group Mood 85%, biểu quyết và gợi ý Match Engine thuận tuyến. |
| **7:15 - 9:45** | Sàn đảo, Đối tác X & Chat 1-1 | Đấu thầu báo giá ngược, AI cảnh báo phụ phí ẩn, tư vấn Unbundle, Đối tác X chặn lịch bận, **Chat trực tiếp 1-1 theo Booking**. |
| **9:45 - 11:30** | Chat nhóm & Mạng xã hội | **Chat nhóm gửi ảnh**, Bảng tin `/social`, tương tác Like, Comment và tính năng **Nhân bản chuyến đi (Clone Trip)**. |
| **11:30 - 13:45** | Quyết toán nợ & VietQR | Thuật toán Greedy Min-Cash-Flow rút gọn nợ chéo, mã VietQR Napas 247 động điền sẵn nội dung. |
| **13:45 - 15:00** | Ngoại tuyến & Kết luận | Giả lập F12 ngắt mạng vẫn dùng Offline Passport, Album kỷ niệm số, xuất PDF và lời cảm ơn. |
