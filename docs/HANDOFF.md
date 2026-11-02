# Bàn giao: trạng thái sau T10

Tài liệu này giúp bạn đọc hiểu phần đã làm (T01–T10) và tự điều khiển agent làm tiếp từ T11.
Cập nhật: 02/11/2026.

## 1. Chạy và đăng nhập

```bash
docker compose up -d --build                     # http://localhost:8000
docker compose exec app php artisan app:check-connections
docker compose exec app php artisan test
```

| Tài khoản | Mật khẩu | Role |
|---|---|---|
| admin@example.com | password | admin → menu "Quản trị" → Quản lý đề thi |
| student@example.com, student2@example.com | password | student |

## 2. Đã làm gì, nằm ở đâu

| Task | Nội dung | File nên đọc |
|---|---|---|
| T01 | Laravel **11.57.1**, chỉ bỏ qua 5 advisory có lý do | `composer.json` (`config.policy`), `docs/DECISIONS.md` D1 |
| T02 | Docker: app (php-fpm 8.3 + ext mongodb), nginx, MySQL 8, Mongo 7, node | `docker-compose.yml`, `docker/php/Dockerfile`, `docker/php/entrypoint.sh` |
| T03 | `.env.example` cho Docker; seeder idempotent | `database/seeders/*` |
| T04 | Connection `mongodb`, model `ActivityLog`, trait `RefreshMongo` cho test | `config/database.php`, `app/Models/Mongo/ActivityLog.php`, `tests/Concerns/RefreshMongo.php` |
| T05 | `php artisan app:check-connections` | `app/Console/Commands/CheckConnections.php` |
| T07 | Bảng `users.role`, `exams` (soft delete), `questions`, `options` | `database/migrations/2026_11_02_00000{1..4}_*`, `app/Models/*` |
| T08 | Index Mongo cho `attempts`, `activity_logs` | `database/migrations/2026_11_02_000005_create_mongo_indexes.php` |
| T09 | Breeze (web) + Sanctum (API token), middleware `role`, khu `/admin` | `app/Http/Middleware/EnsureUserHasRole.php`, `bootstrap/app.php`, `routes/web.php`, `routes/api.php`, `app/Services/Auth/TokenAuthService.php` |
| T10 | CRUD đề và câu hỏi: FormRequest, phân trang 10/trang, tìm theo tên | `app/Services/Exam/*`, `app/Http/Controllers/Admin/*`, `app/Http/Requests/Admin/*`, `resources/views/admin/**` |
| (T17) | `preventLazyLoading` bật ngoài production | `app/Providers/AppServiceProvider.php` |

**Thứ tự đọc gợi ý** (theo đường đi của một request):
`routes/web.php` → `EnsureUserHasRole` → `StoreQuestionRequest` (rule + `after()`) → `Admin/QuestionController` → `QuestionService` → `Question`/`Option` → `admin/questions/_form.blade.php` → `tests/Feature/Admin/QuestionCrudTest.php`.

## 3. Những điểm dễ bị hỏi trong buổi review

- **Vì sao `role` không nằm trong `$fillable`?** Để không ai tự gán `role=admin` khi đăng ký (có test `test_self_registered_user_is_always_a_student`).
- **Vì sao exam soft delete còn question thì xoá cứng?** Attempt sau này lưu snapshot, còn tên đề vẫn cần cho thống kê (D11).
- **Vì sao update câu hỏi lại xoá và tạo lại options?** Đơn giản, chạy trong transaction, và attempt không phụ thuộc id option vì đã có snapshot (D5).
- **Checkbox `is_correct` + hidden input "0":** checkbox không được tick thì trình duyệt không gửi gì, nên hidden "0" đứng trước đảm bảo luôn có giá trị. Khi tick thì "1" gửi sau và ghi đè.
- **Route câu hỏi lồng `scoped()`** (`/admin/exams/{exam}/questions/{question}`): câu hỏi không thuộc đề thì trả 404, đề đã soft delete thì câu hỏi của nó cũng trả 404. Bản đầu dùng `shallow()` và bị lỗi 500 ở trường hợp này; reviewer agent phát hiện ra.
- **Tìm kiếm:** `SearchExamRequest` chặn `?q[]=x` (trước đây trả 500), và `scopeSearch` escape `%`, `_` để "50%" được hiểu đúng nghĩa đen.
- **`RefreshMongo`:** `RefreshDatabase` chỉ reset MySQL. Trait này xoá document (giữ lại index) trong DB `exam_testing`, và tự từ chối chạy nếu tên DB không kết thúc bằng `_testing`.
- **Mongo index tạo bằng migration** (C4.5). `createIndex` idempotent nên `migrate:fresh` chạy lại không lỗi.

## 4. Lưu ý kỹ thuật đã gặp

- `migrate:fresh` **chỉ xoá MySQL**, collection Mongo vẫn còn. Khi cần làm sạch Mongo ở dev:
  `docker compose exec mongo mongosh exam --eval 'db.dropDatabase()'` rồi chạy `php artisan migrate` để tạo lại index.
- Cổng 8080 trên máy này đã bị dịch vụ khác dùng nên app chạy ở **8000** (`APP_PORT` trong `.env`).
- Service `node` build lại asset mỗi lần `docker compose up`. Thêm class Tailwind mới mà không thấy style thì chạy `docker compose up node`.
- Code scaffold của Breeze (reset password…) vẫn dùng `$request->validate()`. Đây là ngoại lệ đã ghi ở D3.
- Lỗi API hiện vẫn theo format mặc định của Laravel (`{"message": ...}`). Phần chuẩn hoá `{message, code}` (D12) thuộc PR6/T16.

## 5. Làm tiếp từ đâu

Còn lại của **PR5** (tách khỏi T10): **T15** activity log (Event/Listener/Observer → `RecordActivity`) và **T19** cache danh sách đề. Sau đó là PR6 trở đi trong `docs/ROADMAP.md`.

### Cách giao việc cho agent (Claude Code)
Giao **từng phần nhỏ**, đọc diff trước khi cho commit:

```text
Dùng agent laravel-implementer: làm T15 cho exam/question (PR5 phần còn lại).
- Chỉ dùng ExamObserver + QuestionObserver và listener RecordActivity, action theo C6.3.
- Không commit; dừng lại để tôi đọc diff.
```
```text
Dùng agent test-engineer: viết test cho T12 chấm điểm và nộp quá giờ theo D6, D7. Chưa viết code production.
```
```text
Dùng agent contract-reviewer review git diff main...HEAD.
```

Mẹo kiểm soát:
- Yêu cầu agent **giải thích** trước khi code ("trình bày kế hoạch và file sẽ sửa, chờ tôi duyệt").
- Mỗi lần chỉ làm một commit nhỏ, tự đọc `git diff`, chạy test rồi mới commit.
- Thấy thay đổi đụng tới quyết định trong `DECISIONS.md` thì quyết lại trước rồi mới cho agent code.
