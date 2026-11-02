# Protean Exam – Hệ thống thi trắc nghiệm online

> README này đang ở dạng khung. Bản hoàn chỉnh (hướng dẫn chạy, quyết định kỹ thuật, phần chưa làm) sẽ được viết ở PR12, xem [docs/ROADMAP.md](docs/ROADMAP.md).

Laravel 11 · PHP 8.3 · MySQL 8 · MongoDB 7 · Blade + Alpine.js · Docker

## Chạy dự án
```bash
git clone <repo-url> protean-exam && cd protean-exam
docker compose up -d --build        # tự cài vendor, tạo key, migrate và seed
# mở http://localhost:8080
docker compose exec app php artisan app:check-connections
docker compose exec app php artisan test
```

## Tài liệu
| File | Nội dung |
|---|---|
| [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md) | Đề bài gốc (T01–T22) |
| [docs/DECISIONS.md](docs/DECISIONS.md) | Quyết định kỹ thuật và lý do |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Hạ tầng, cấu trúc thư mục, schema, luồng |
| [docs/API_CONTRACT.md](docs/API_CONTRACT.md) | Endpoint, format response, status code |
| [docs/CODING_CONTRACT.md](docs/CODING_CONTRACT.md) | Quy tắc code bắt buộc |
| [docs/GIT_WORKFLOW.md](docs/GIT_WORKFLOW.md) | Nhánh, commit, PR |
| [docs/ROADMAP.md](docs/ROADMAP.md) | Kế hoạch theo PR và tiến độ |

## Những gì chưa làm được và vì sao
_(cập nhật khi hoàn thiện)_
