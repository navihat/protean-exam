# AGENTS.md

Hướng dẫn cho mọi AI agent (Claude Code, Codex, Cursor…) làm việc trong repo này.

## Dự án
Hệ thống thi trắc nghiệm online, là bài test tuyển dụng Backend (Protean Studios).
Stack: **Laravel 11** · PHP 8.3 · MySQL 8 (dữ liệu gốc) · MongoDB 7 (attempts, logs) · Blade + Alpine.js · Docker.

## Đọc trước khi code (theo thứ tự)
1. `docs/REQUIREMENTS.md`: đề bài gốc, các task `T01…T22` và tiêu chí hoàn thành
2. `docs/CODING_CONTRACT.md`: quy tắc **bắt buộc** (`C<n>`)
3. `docs/DECISIONS.md`: các quyết định đã chốt (`D<n>`). Không tự ý đổi.
4. `docs/ARCHITECTURE.md`: cấu trúc thư mục, schema, luồng
5. `docs/API_CONTRACT.md`: format response, status code, endpoint
6. `docs/ROADMAP.md`: đang ở PR nào, PR đó gồm gì
7. `docs/GIT_WORKFLOW.md`: nhánh, commit, PR

## Lệnh (mọi lệnh PHP chạy **trong container**, máy host không có PHP)
```bash
docker compose up -d --build                         # chạy toàn bộ stack
docker compose exec app php artisan test             # chạy test
docker compose exec app php artisan test --filter=SubmitAttemptTest
docker compose exec app ./vendor/bin/pint            # format
docker compose exec app ./vendor/bin/pint --test     # kiểm tra format (CI)
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan app:check-connections
docker compose exec app composer require <pkg>
docker compose run --rm node npm run build           # build asset
docker compose logs -f app
```

## Quy tắc vàng (tóm tắt, bản đầy đủ ở CODING_CONTRACT)
1. **Controller mỏng**: FormRequest → một lời gọi Service → Resource/View. Logic nằm trong `app/Services`.
2. **Không N+1**: `preventLazyLoading` đang bật, dùng `with()` hoặc `withCount()`.
3. **Chống gian lận**: server tự chấm từ snapshot; `is_correct` không bao giờ lộ ra trước khi nộp; thời gian lấy từ server.
4. **Activity log chỉ ghi trong `RecordActivity` listener**, không ghi trong controller hay service.
5. **Mongo**: id MySQL lưu dạng int; cập nhật trạng thái bằng lệnh atomic có điều kiện; thống kê bằng aggregation.
6. **Mỗi thay đổi có test**; test thời gian dùng `travel()`, không `sleep()`.
7. **Đơn giản và giải thích được**: ứng viên phải trình bày từng dòng trong buổi review. Không thêm package hay lớp trừu tượng không cần thiết.
8. Code và commit viết tiếng Anh; `docs/` và README viết tiếng Việt.

## Quy trình cho mỗi task
1. Xác định PR hiện tại trong ROADMAP và tạo nhánh theo GIT_WORKFLOW.
2. Code từng bước nhỏ, mỗi bước một commit Conventional Commit có `[T<nn>]`.
3. Chạy `pint` và `php artisan test`, cả hai phải xanh.
4. Tự review diff theo CODING_CONTRACT (agent `contract-reviewer`).
5. Cập nhật docs liên quan và tick ROADMAP.
6. Push và mở PR theo template. **Không merge, không push lên `main`, không force-push khi chưa được người dùng cho phép.**

## Không được làm
- Sửa `docs/REQUIREMENTS.md` (đây là đề gốc).
- Commit `.env`, file PDF đề bài, `vendor/`, `node_modules/`, `public/build/`.
- Tắt test, `markTestSkipped`, hạ assertion cho test pass.
- Đổi quyết định trong DECISIONS.md mà không hỏi người dùng.
- Dùng tài liệu hoặc scan sách thật cho T21; mọi file mẫu phải tự sinh.
