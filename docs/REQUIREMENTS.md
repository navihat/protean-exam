# Pros Test – Bài test (bản Markdown)

> Chép nguyên văn từ `Pros Test - Bài test .pdf`. Chỉ thêm mã task `T01…T22` để tham chiếu trong code, commit, PR.
> Phân tích và quyết định kỹ thuật nằm ở [DECISIONS.md](DECISIONS.md), kế hoạch ở [ROADMAP.md](ROADMAP.md).

## Mục lục

| Mã | Phần | Task |
|---|---|---|
| [T01](#t01--khởi-tạo-project-laravel-11) | Setup | Khởi tạo project Laravel 11 |
| [T02](#t02--docker-hoá-môi-trường) | Setup | Docker hoá môi trường |
| [T03](#t03--kết-nối-mysql) | Setup | Kết nối MySQL |
| [T04](#t04--kết-nối-mongodb) | Setup | Kết nối MongoDB |
| [T05](#t05--command-kiểm-tra-kết-nối) | Setup | Command kiểm tra kết nối |
| [T06](#t06--readme) | Setup | README |
| [T07](#t07--schema-mysql) | Thiết kế DB | Schema MySQL |
| [T08](#t08--collection-mongodb) | Thiết kế DB | Collection MongoDB |
| [T09](#t09--đăng-kýđăng-nhập--phân-quyền) | Tính năng | Đăng ký/đăng nhập + phân quyền |
| [T10](#t10--crud-đề-thi-và-câu-hỏi-admin) | Tính năng | CRUD đề thi và câu hỏi (admin) |
| [T11](#t11--bắt-đầu-làm-bài-student) | Tính năng | Bắt đầu làm bài (student) |
| [T12](#t12--nộp-bài-và-chấm-điểm) | Tính năng | Nộp bài và chấm điểm |
| [T13](#t13--trang-kết-quả) | Tính năng | Trang kết quả |
| [T14](#t14--thống-kê-cho-admin) | Tính năng | Thống kê cho admin |
| [T15](#t15--activity-log) | Kỹ thuật | Activity log |
| [T16](#t16--api-rest-làm-bài) | Kỹ thuật | API REST làm bài |
| [T17](#t17--tổ-chức-code) | Kỹ thuật | Tổ chức code |
| [T18](#t18--feature-test) | Test | Feature test |
| [T19](#t19--bonus) | Bonus | Bonus |
| [T20](#t20--tiếp-tục-bài-đang-làm-autosave) | Nâng cao | Tiếp tục bài đang làm (autosave) |
| [T21](#t21--gói-tài-liệu-ielts-mindset-for-ielts--backend) | Nâng cao – BE | Gói tài liệu IELTS – Backend |
| [T22](#t22--gói-tài-liệu-ielts-mindset-for-ielts--frontend) | Nâng cao – FE | Gói tài liệu IELTS – Frontend |

---

## Setup

### T01 – Khởi tạo project Laravel 11
- **Mô tả:** Tạo project Laravel 11 (PHP >= 8.2), đẩy lên Git repo, commit theo từng bước.
- **Tiêu chí hoàn thành:** Repo có lịch sử commit rõ ràng.

### T02 – Docker hoá môi trường
- **Mô tả:** docker-compose gồm php-fpm, nginx, MySQL 8, MongoDB 7 (cài tay cũng được nhưng phải ghi rõ trong README).
- **Tiêu chí hoàn thành:** `docker compose up` là chạy được app.

### T03 – Kết nối MySQL
- **Mô tả:** Cấu hình `.env`, viết migration và seeder (tài khoản admin, student, đề thi mẫu).
- **Tiêu chí hoàn thành:** `php artisan migrate --seed` chạy không lỗi.

### T04 – Kết nối MongoDB
- **Mô tả:** Cài package `mongodb/laravel-mongodb`, khai báo connection `mongodb` trong `config/database.php`.
- **Tiêu chí hoàn thành:** Model Mongo đọc/ghi được dữ liệu.

### T05 – Command kiểm tra kết nối
- **Mô tả:** Tạo artisan command `app:check-connections` in trạng thái kết nối MySQL và MongoDB.
- **Tiêu chí hoàn thành:** Chạy command thấy OK cho cả 2 DB.

### T06 – README
- **Mô tả:** Hướng dẫn chạy dự án từ đầu bằng tối đa khoảng 5 lệnh; giải thích ngắn các quyết định kỹ thuật (vì sao dữ liệu nào để MySQL, dữ liệu nào để MongoDB...); có mục "Những gì chưa làm được và vì sao".
- **Tiêu chí hoàn thành:** Người khác clone về chạy được ngay; giải thích được lựa chọn của mình; trung thực về phần còn thiếu.

## Thiết kế DB

### T07 – Schema MySQL
- **Mô tả:** Bảng `users`, `exams` (tên, thời gian làm bài), `questions`, `options` (đáp án, cờ đúng/sai); có foreign key và index.
- **Tiêu chí hoàn thành:** Migration hợp lý, quan hệ đúng.

### T08 – Collection MongoDB
- **Mô tả:** `attempts` (snapshot câu hỏi, câu trả lời, điểm, thời gian bắt đầu/nộp) và `activity_logs` (ai làm gì, lúc nào).
- **Tiêu chí hoàn thành:** Có index cho `user_id`, `exam_id`, `created_at`.

## Tính năng

### T09 – Đăng ký/đăng nhập + phân quyền
- **Mô tả:** Dùng Sanctum hoặc Breeze, 2 role admin/student, middleware chặn quyền.
- **Tiêu chí hoàn thành:** Student không vào được trang admin.

### T10 – CRUD đề thi và câu hỏi (admin)
- **Mô tả:** Validate bằng FormRequest, có phân trang, tìm kiếm theo tên.
- **Tiêu chí hoàn thành:** Tạo/sửa/xoá/tìm kiếm hoạt động đúng.

### T11 – Bắt đầu làm bài (student)
- **Mô tả:** Tạo attempt trong MongoDB, trộn thứ tự câu hỏi.
- **Tiêu chí hoàn thành:** Mỗi lần làm có attempt riêng, thứ tự câu khác nhau.

### T12 – Nộp bài và chấm điểm
- **Mô tả:** Chấm điểm phía server trong Service class; nộp quá giờ thì từ chối hoặc chấm theo phần đã làm.
- **Tiêu chí hoàn thành:** Điểm đúng, không gian lận được từ client.

### T13 – Trang kết quả
- **Mô tả:** Hiển thị điểm, các câu sai và đáp án đúng.
- **Tiêu chí hoàn thành:** Hiển thị đúng dữ liệu của attempt.

### T14 – Thống kê cho admin
- **Mô tả:** Dùng MongoDB aggregation: điểm trung bình theo đề, top 10 học sinh, câu hỏi bị sai nhiều nhất.
- **Tiêu chí hoàn thành:** Số liệu khớp với dữ liệu thực tế.

## Kỹ thuật

### T15 – Activity log
- **Mô tả:** Ghi log các hành động quan trọng qua Event/Listener hoặc Observer.
- **Tiêu chí hoàn thành:** Không ghi log rải rác trong controller.

### T16 – API REST làm bài
- **Mô tả:** `GET /api/exams`, `POST /api/attempts/{id}/submit`; response thống nhất qua API Resource.
- **Tiêu chí hoàn thành:** Response đúng format và đúng status code.

### T17 – Tổ chức code
- **Mô tả:** Logic nằm trong Service, validate qua FormRequest, eager loading tránh N+1.
- **Tiêu chí hoàn thành:** Controller mỏng, không có N+1 query.

## Test

### T18 – Feature test
- **Mô tả:** Ít nhất 5 Feature test, bắt buộc có test chấm điểm và test nộp quá giờ.
- **Tiêu chí hoàn thành:** `php artisan test` pass toàn bộ.

## Bonus

### T19 – Bonus
- **Mô tả:** Cache danh sách đề, Swagger hoặc Postman collection.
- **Tiêu chí hoàn thành:** Làm được mục nào tính mục đó.

---

## NÂNG CAO

> **Chọn làm ít nhất 1 trong 3 task bên dưới** (task 21 và 22 là BE và FE của cùng một tính năng, làm cả hai được đánh giá cao).

### T20 – Tiếp tục bài đang làm (autosave)
- **Mô tả:** Lưu đáp án từng câu vào attempt trong MongoDB ngay khi chọn; reload hoặc mở lại trình duyệt vẫn tiếp tục đúng attempt cũ với thời gian còn lại tính theo server; mỗi student chỉ có 1 attempt đang mở cho mỗi đề.
- **Tiêu chí hoàn thành:** Reload không mất đáp án, không tạo được 2 attempt song song, đồng hồ không bị reset.

### T21 – Gói tài liệu IELTS (Mindset for IELTS) – Backend
- **Mô tả:**
  - MySQL: bảng `packages`, `units`, `documents` (loại file, số trang, số trang được xem thử).
  - Seeder gói Mindset for IELTS level 1/2/3 với file PDF/audio mẫu tự tạo, không dùng bản scan sách thật.
  - API:
    - `GET /api/packages` (lọc level, phân trang)
    - `GET /api/packages/{slug}` (chi tiết + mục lục theo Unit)
    - `POST /api/documents/{id}/preview` trả signed URL có thời hạn.
  - File gốc để ở storage private; server chỉ trả bản xem thử giới hạn số trang PDF và đoạn audio ngắn.
  - MongoDB lưu `preview_logs` (người xem/IP, tài liệu, thời gian) và có API thống kê gói/tài liệu được xem thử nhiều nhất.
  - Có rate limit cho API xem thử.
- **Tiêu chí hoàn thành:** Không truy cập được file gốc qua URL; signed URL hết hạn trả 403; xem thử trả đúng số trang giới hạn; có Feature test cho API preview; số liệu thống kê khớp dữ liệu.

### T22 – Gói tài liệu IELTS (Mindset for IELTS) – Frontend
- **Mô tả:**
  1. Trang danh sách gói dạng card: ảnh bìa, tên gói, level, số tài liệu, giá; lọc theo level, tìm kiếm.
  2. Trang chi tiết gói: mô tả, mục lục theo Unit dạng accordion, danh sách tài liệu PDF/audio có icon loại file, tài liệu chưa mở khoá hiện icon khoá.
  3. Nút Xem thử mở modal: xem PDF bằng pdf.js (chuyển trang, zoom), nghe thử audio; vượt giới hạn trang thì hiện thông báo mua gói.
  4. Màn thống kê lượt xem thử cho admin.

  Tự chọn Blade + Alpine.js/Livewire hoặc Vue/React (Inertia), dữ liệu lấy từ API phía BE.
- **Tiêu chí hoàn thành:** Giao diện đẹp, chạy tốt trên desktop và mobile; mở xem thử không reload trang; có trạng thái loading/lỗi/rỗng; component tách hợp lý, tái sử dụng được.

---

## LƯU Ý

- Được dùng tài liệu và công cụ AI, nhưng bạn phải hiểu và giải thích được toàn bộ code mình nộp. Sau khi nộp bài sẽ có buổi review 20–30 phút: bạn trình bày bài làm, giải thích một số đoạn code và chỉnh sửa nhỏ ngay tại chỗ.
- Nộp bài: link Git repo (public hoặc mời quyền xem) kèm README. Làm chưa xong vẫn nộp, ghi rõ phần còn thiếu trong README.
