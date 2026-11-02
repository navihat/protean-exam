# Roadmap

Mỗi dòng là **một nhánh + một PR**, làm theo thứ tự từ trên xuống. Tick `[x]` khi PR đã merge.
Test được viết **kèm từng PR** (C8.1), nên T18 hoàn thành dần.

## Core

- [x] **PR1 `chore/T01-T02-bootstrap-docker`**: Laravel 11 skeleton, Dockerfile (php-fpm + ext mongodb), nginx, MySQL 8, Mongo 7, entrypoint tự setup, CI workflow
  - Xong khi: `docker compose up -d` → mở `localhost:8000` thấy trang Laravel
- [x] **PR2 `feat/T04-T05-mongodb-connection`**: `mongodb/laravel-mongodb`, connection `mongodb`, `app:check-connections`, trait `RefreshMongo`, phpunit dùng DB `exam_testing`
  - Xong khi: command in OK cho 2 DB; có test đọc/ghi Model Mongo
- [x] **PR3 `feat/T03-T07-T08-schema-seed`**: migration users(role), exams (soft delete), questions, options; migration index Mongo (attempts, activity_logs); enum; factory; seeder idempotent (admin, student, 2–3 đề mẫu)
  - Xong khi: `migrate:fresh --seed` không lỗi, chạy lại seeder không nhân bản dữ liệu
- [x] **PR4 `feat/T09-auth-roles`**: Breeze (Blade), Sanctum, `EnsureUserHasRole`, register luôn là student, API login token
  - Test: student vào `/admin/*` bị 403, guest bị redirect hoặc 401
- [ ] **PR5 `feat/T10-T15-admin-exam-crud`**: CRUD đề và câu hỏi (FormRequest, paginate, search), `ExamService`, Observer, event, `RecordActivity`, cache danh sách đề (T19)
  - Test: tạo, sửa, xoá, tìm kiếm, validate lỗi; log được ghi qua Observer
- [ ] **PR6 `feat/T11-T12-T16-attempt-api`**: `AttemptService::startOrResume`, `GradingService`, API exams/attempts/submit/result, Resource, xử lý lỗi tập trung, `AttemptPolicy`
  - Test bắt buộc: **chấm điểm đúng**, **nộp quá giờ**, response không lộ `is_correct`, 2 lần start ra thứ tự khác nhau, nộp 2 lần trả 409
- [ ] **PR7 `feat/T20-autosave`**: partial unique index, autosave atomic, `remaining_seconds` tính theo server, lazy finalize
  - Test: autosave rồi đọc lại còn đáp án; start lần 2 trả attempt cũ; không có 2 attempt in_progress; autosave sau giờ trả 409
- [ ] **PR8 `feat/T11-T13-student-ui`**: trang danh sách đề, trang làm bài (Alpine: đồng hồ, autosave, nộp), trang kết quả
- [ ] **PR9 `feat/T14-admin-statistics`**: `ExamStatisticsService` (3 pipeline aggregation), trang và API thống kê
  - Test: seed dữ liệu attempt đã biết trước → số liệu trả về khớp

## Nâng cao

- [ ] **PR10 `feat/T21-ielts-packages-api`**: migration packages/units/documents, seeder sinh PDF/audio giả, API danh sách/chi tiết, preview signed URL, cắt PDF (qpdf) và audio (ffmpeg), `preview_logs`, thống kê, `throttle:preview`
  - Test: URL file gốc không truy cập được; signed URL hết hạn trả 403; PDF preview có đúng N trang; 429 khi vượt rate limit; thống kê khớp
- [ ] **PR11 `feat/T22-ielts-packages-ui`**: trang card + lọc + tìm kiếm, trang chi tiết accordion, modal pdf.js và audio, màn thống kê admin; có trạng thái loading/lỗi/rỗng; responsive

## Hoàn thiện

- [ ] **PR12 `docs/T06-T19-readme-postman`**: README (≤ 5 lệnh, quyết định kỹ thuật, "Những gì chưa làm được và vì sao"), Postman collection `docs/postman/`
- [ ] Clone repo ra thư mục trống, chạy lại đúng README → tag `v1.0.0` → nộp link

## Theo dõi tiêu chí chấm

| Task | PR | Trạng thái |
|---|---|---|
| T01 Laravel 11 + commit từng bước | PR1 + toàn bộ | ☐ |
| T02 Docker | PR1 | ✅ |
| T03 MySQL + seed | PR3 | ✅ |
| T04 MongoDB | PR2 | ✅ |
| T05 check-connections | PR2 | ✅ |
| T06 README | PR12 | ☐ |
| T07 Schema MySQL | PR3 | ✅ |
| T08 Collection Mongo + index | PR3 | ✅ |
| T09 Auth + role | PR4 | ✅ |
| T10 CRUD admin | PR5 | ☐ |
| T11 Bắt đầu làm bài | PR6, PR8 | ☐ |
| T12 Nộp + chấm | PR6 | ☐ |
| T13 Trang kết quả | PR8 | ☐ |
| T14 Thống kê aggregation | PR9 | ☐ |
| T15 Activity log | PR5, PR6 | ☐ |
| T16 API REST | PR6 | ☐ |
| T17 Tổ chức code | toàn bộ | ☐ |
| T18 ≥ 5 feature test | toàn bộ | ☐ |
| T19 Cache + Postman | PR5, PR12 | ☐ |
| T20 Autosave | PR7 | ☐ |
| T21 IELTS BE | PR10 | ☐ |
| T22 IELTS FE | PR11 | ☐ |
