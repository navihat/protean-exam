# Quyết định kỹ thuật (Decision log)

Mỗi quyết định có mã `D<n>`. Trạng thái: **Đề xuất** (chờ duyệt) → **Chốt** → (có thể) **Thay thế bởi D<m>**.
Khi code lệch khỏi một quyết định đã chốt thì phải cập nhật file này trước, trong cùng PR.
README tóm tắt lại các quyết định quan trọng (yêu cầu T06).

| Mã | Chủ đề | Trạng thái |
|---|---|---|
| D1 | Laravel 11 dù đã hết hạn bảo mật | **Chốt** |
| D2 | Frontend: Blade + Alpine.js | Đề xuất |
| D3 | Auth: Breeze (web) + Sanctum (API) | Đề xuất |
| D4 | Phân chia MySQL / MongoDB | Đề xuất |
| D5 | Snapshot đề thi trong attempt | Đề xuất |
| D6 | Cách chấm điểm | Đề xuất |
| D7 | Nộp quá giờ | Đề xuất |
| D8 | Một attempt đang mở cho mỗi (student, đề) | Đề xuất |
| D9 | Phạm vi phần nâng cao | Đề xuất |
| D10 | Activity log | Đề xuất |
| D11 | Tham chiếu chéo MySQL ↔ Mongo, xoá đề | Đề xuất |
| D12 | Format response API | Đề xuất |
| D13 | Xem thử tài liệu (T21) | Đề xuất |
| D14 | Cache danh sách đề | Đề xuất |

---

### D1 – Laravel 11 dù đã hết hạn bảo mật
- **Bối cảnh:** Đề bắt buộc Laravel 11. Nhánh 11.x đã hết hạn hỗ trợ bảo mật; Packagist ghi nhận advisory chưa vá trên 11.x (ví dụ CVE-2026-48019 – CRLF injection ở rule `email` mặc định). Composer 2.9+ mặc định **chặn** cài các bản có advisory.
- **Chốt (02/11/2026):** Dùng `laravel/framework` **v11.57.1**. `composer.json` → `config.policy.advisories.ignore-id` chỉ bỏ qua **đúng 5 advisory** ảnh hưởng tới bản này, mỗi ID kèm lý do. Không tắt cơ chế chặn toàn cục. README ghi rõ chỉ dùng cho bài test, production phải lên 12/13.

| Advisory | Nội dung | Ảnh hưởng tới dự án / cách giảm thiểu |
|---|---|---|
| PKSA-b41t-wbzk-9q51 (CVE-2026-114509) | TokenGuard auth bypass | Không dùng guard `token` cũ, chỉ dùng Sanctum |
| PKSA-d5tc-s1qs-h781 (CVE-2026-102279) | XSS ở trang debug | `APP_DEBUG=false` ngoài môi trường local |
| PKSA-m5cs-t1y6-qpcs | Path confusion ở Temporary Signed URL | **Liên quan T21:** route preview kiểm tra lại `document` khớp id đã ký và có test cho trường hợp sửa path (D13) |
| PKSA-3r5d-mb8f-1qw9, PKSA-mdq4-51ck-6kdq (CVE-2026-48019) | CRLF injection ở rule `email` mặc định | Dùng `email:rfc,strict`, không đưa email người dùng vào header thô |

- **Phương án đã loại:** Laravel 12, vì lệch đề T01.

### D2 – Frontend: Blade + Alpine.js
- Dùng Breeze (Blade stack). Riêng trang làm bài và trang gói IELTS dùng Alpine.js gọi **chính API REST** (Sanctum stateful cookie), nhờ vậy API được dùng thật chứ không chỉ để cho có, và mở xem thử không reload trang.
- Lý do: ít công cụ build nhất, dễ giải thích khi review, không cần SPA router.

### D3 – Auth: Breeze (web) + Sanctum (API)
- Breeze lo đăng ký/đăng nhập web. Sanctum cấp token cho Postman/API và cookie auth cho Alpine.
- Bảng `users` có cột `role` (enum PHP `UserRole`: `admin`, `student`). Đăng ký mới luôn là `student`, admin chỉ được tạo từ seeder.
- Middleware `role:admin` chặn `/admin/*` và `/api/admin/*`, trả 403.
- `role` **không** nằm trong `$fillable` của `User`, nên không thể gán từ request. Seeder dùng `forceFill`.
- Code scaffold của Breeze (reset/confirm password, verify email, profile) được giữ nguyên, kể cả chỗ dùng `$request->validate()`. Đây là ngoại lệ có chủ đích với C2.1, vì đó là code chuẩn của framework và không thuộc nghiệp vụ. Riêng đăng ký đã chuyển sang `RegisterRequest`.

### D4 – Phân chia MySQL / MongoDB
| Dữ liệu | Nơi lưu | Lý do |
|---|---|---|
| users, exams, questions, options, packages, units, documents | MySQL | Dữ liệu gốc, quan hệ chặt, cần FK, CRUD và phân trang |
| attempts | MongoDB | Document tự chứa (snapshot + câu trả lời), cấu trúc lồng nhau, ghi nhiều (autosave), phù hợp aggregation thống kê |
| activity_logs, preview_logs | MongoDB | Append-only, khối lượng lớn, schema linh hoạt (`meta`), không cần join |

### D5 – Snapshot đề thi trong attempt
- Khi bắt đầu làm bài, chép câu hỏi và đáp án (kể cả `is_correct`) vào `attempts.snapshot` theo **thứ tự đã trộn**.
- Chấm điểm và hiển thị kết quả **chỉ dựa trên snapshot**, nên admin sửa hoặc xoá đề sau đó không làm sai kết quả cũ.
- `is_correct` **không bao giờ** được trả về cho student trước khi nộp. Việc này do một Resource riêng đảm bảo (`AttemptQuestionResource`), có test kiểm tra.

### D6 – Cách chấm điểm
- Một câu có thể có nhiều đáp án đúng. Câu chỉ được tính đúng khi tập đáp án chọn **trùng khít** tập đáp án đúng (đúng hết mới được điểm).
- Lưu `correct_count`, `total_questions` và `score = round(correct_count / total * 10, 2)` (thang 10).
- Mỗi câu lưu `is_correct` trong `results[]` để thống kê "câu sai nhiều nhất" bằng aggregation.

### D7 – Nộp quá giờ
- `expires_at = started_at + duration`. Có thời gian ân hạn `config('exam.submit_grace_seconds')` = 30 giây cho độ trễ mạng.
- Nộp **trong hạn**: lấy câu trả lời từ request gộp với phần đã autosave rồi chấm. Trả **200**.
- Nộp **sau hạn**: bỏ qua câu trả lời trong request, chỉ chấm **phần đã autosave trước hạn**, đánh dấu `is_late = true`. Trả **200** kèm cờ `is_late`.
- Attempt đã nộp mà gửi lại thì trả **409** `ATTEMPT_ALREADY_SUBMITTED`.
- Mở lại một attempt đã hết giờ mà chưa nộp thì hệ thống tự chốt và chấm (lazy finalize).
- **Đã làm ở PR6:** `AttemptService::submit` và việc chốt attempt hết giờ khi student bấm "bắt đầu" lại dùng chung một hàm `close()`: chấm bằng `GradingService` rồi gọi `update()` với điều kiện `_id` + `status = in_progress`. Đây là một lệnh duy nhất, atomic trên document (driver gửi `updateMany`, nhưng filter `_id` chỉ khớp một document). Nếu không cập nhật được document nào thì attempt đã bị request khác nộp trước, trả 409. Phần lazy finalize khi **đọc** attempt (`GET /api/attempts/{id}`) để lại PR7.

### D8 – Một attempt đang mở cho mỗi (student, đề)
- Mongo có **partial unique index** trên `{user_id, exam_id}` với điều kiện `status = "in_progress"`, nên DB tự chặn race condition (bấm đúp, mở 2 tab).
- Gọi "bắt đầu" khi đang có attempt mở thì trả về attempt đó (**200**), tạo mới thì trả **201**. Nếu insert bị lỗi duplicate key thì đọc lại attempt đang mở và trả về.
- **Đã làm ở PR6:** tìm attempt đang mở rồi trả về (200) hoặc tạo mới (201; `JsonResource` tự trả 201 khi model có `wasRecentlyCreated`). **Chưa có** partial unique index nên hai request "bắt đầu" đồng thời vẫn có thể tạo hai attempt; index và xử lý duplicate key thuộc PR7.

### D9 – Phạm vi phần nâng cao
- Bắt buộc làm: **T20** (autosave), vì nó nối tiếp core và giải quyết luôn D7.
- Tiếp theo: **T21** (backend gói IELTS), phù hợp vị trí BE.
- Nếu còn thời gian: **T22** (frontend).

### D10 – Activity log
- Domain event (`AttemptStarted`, `AttemptSubmitted`, `DocumentPreviewed`…), event có sẵn của Laravel (`Login`, `Logout`, `Registered`) và Observer (`ExamObserver`, `QuestionObserver`) đều đổ về listener `RecordActivity`, listener này ghi `activity_logs`.
- Listener chạy đồng bộ (`QUEUE_CONNECTION=sync`) cho đơn giản. `RecordActivity` lấy user, IP và user agent từ `Request` của chính request đó. Vì vậy muốn chuyển sang queue thì `implements ShouldQueue` là **chưa đủ**: phải đưa actor/IP/user agent vào event ngay lúc dispatch, nếu không worker sẽ ghi user = null và IP của worker.
- **Đã làm ở PR5b:**
  - Observer không ghi log trực tiếp mà dispatch event `ContentChanged(action, subjectType, subjectId, meta)`, listener `RecordActivity::handleContentChanged` ghi log. Nhờ vậy grep `ActivityLog::` chỉ ra đúng một file (C6.1).
  - Observer `implements ShouldHandleEventsAfterCommit`: chỉ chạy sau khi transaction commit, rollback thì không ghi log và không xoá cache (C6.2).
  - `QuestionObserver` dùng event `saved` thay vì `updated`, vì sửa chỉ đáp án thì dòng `questions` không đổi cột nào nên Eloquent không bắn `updated`.
  - `RecordActivity` được Laravel tự đăng ký (event discovery theo type hint của các method `handle*`), xem bằng `php artisan event:list`.
  - Login bằng token API không đi qua session guard nên `TokenAuthService` tự dispatch `Login`/`Logout` (guard `sanctum`).
  - Action đã có: `exam.created|updated|deleted`, `question.created|updated|deleted`, `auth.registered|login|logout`.
- **Đã làm ở PR6:** `attempt.started`, `attempt.submitted` từ event `AttemptStarted`, `AttemptSubmitted`. `subject_type = exam`, `subject_id = exam_id` để `subject_id` luôn là id MySQL kiểu int (C4.2); id Mongo của attempt nằm trong `meta.attempt_id` (kèm `score`, `is_late` khi nộp). `user_id` lấy từ attempt chứ không từ request, vì attempt hết giờ có thể được chốt trong lúc chủ của nó bắt đầu lại đề.

### D11 – Tham chiếu chéo MySQL ↔ Mongo, xoá đề
- Mongo lưu `user_id`, `exam_id`, `question_id` dạng **int** (id MySQL). Không có FK chéo DB.
- `exams` dùng **SoftDeletes**, để thống kê và lịch sử vẫn lấy được tên đề. Question và option xoá cứng, vì snapshot đã giữ dữ liệu cũ.
- Khi cần ghép tên (thống kê), lấy id từ aggregation rồi gọi **một** truy vấn `whereIn` vào MySQL.

### D12 – Format response API
- Thành công: API Resource với khung `{ "data": ..., "meta"?: ..., "links"?: ... }`.
- Lỗi: `{ "message": string, "code": string, "errors"?: object }`, được chuẩn hoá ở `bootstrap/app.php` cho route `api/*`. Chi tiết xem [API_CONTRACT.md](API_CONTRACT.md).
- **Đã làm ở PR6:** `bootstrap/app.php` có 4 render callback, chỉ áp dụng cho `api/*` (trang web giữ nguyên trang lỗi của Laravel): `DomainException` (mã và status do từng exception khai báo), `ValidationException` → 422 `VALIDATION_FAILED`, `AuthenticationException` → 401 `UNAUTHENTICATED`, HttpException 403/404/429 → `FORBIDDEN`/`NOT_FOUND`/`TOO_MANY_REQUESTS`. Message của 404 được thay bằng `Not found.` để không lộ tên class model. `DomainException` không ghi vào log lỗi (`dontReport`).

### D13 – Xem thử tài liệu (T21)
- File gốc nằm trong disk `local` (`storage/app/private/documents/...`), không có symlink public.
- `POST /api/documents/{id}/preview` trả URL tạo bằng `URL::temporarySignedRoute` (TTL 5 phút). Route stream có middleware `signed`, nên hết hạn hoặc sai chữ ký trả **403**.
- Bản xem thử được tạo **lazy** lần đầu rồi cache vào `storage/app/private/previews/`. PDF cắt N trang bằng `qpdf`, audio cắt N giây bằng `ffmpeg` (cả hai cài trong image `app`).
- Rate limit `throttle:preview` là 10 request/phút theo user hoặc IP.
- Seeder tự sinh PDF (dompdf, nội dung giả) và audio (sóng sin qua ffmpeg). Không dùng tài liệu thật.

### D14 – Cache danh sách đề
- `GET /api/exams` được cache theo khoá có **version** (`exams:v{n}:page:{p}:q:{hash}`). `ExamObserver` tăng version mỗi khi đề thay đổi.
- Cách này chạy được với store `database`/`file` (không cần Redis hay cache tags).
- **Đã làm ở PR5b:** `ExamService::paginatePublished(?search, page)` (từ khoá được trim + `mb_strtolower` trước khi tạo key; `path` của paginator được đặt lại theo request hiện tại sau khi lấy từ cache) (endpoint `GET /api/exams` sẽ gọi nó ở PR6). Version nằm ở key `exams:list-version`, tăng bằng `Cache::add` + `Cache::increment` (atomic). Cả `ExamObserver` và `QuestionObserver` đều tăng version, vì danh sách có `questions_count`. TTL ở `config('exam.list_cache_seconds')` = 600 giây; entry của version cũ tự hết hạn.
- Link phân trang dùng `appends(['q' => …])` thay cho `withQueryString()`, để nội dung được cache chỉ phụ thuộc vào khoá cache (ngoại lệ có chủ đích với C3.2).
