# API Contract

- Base URL: `/api`. Header bắt buộc: `Accept: application/json`.
- Auth có hai cách: `Authorization: Bearer <token>` (Sanctum token, dùng cho Postman) hoặc cookie session (Sanctum stateful, dùng cho trang Alpine).
- Thời gian trả về theo ISO-8601 UTC (`2026-11-02T08:00:00Z`).

## 1. Format response

**Thành công: một đối tượng**
```json
{ "data": { "id": 1, "name": "Đề mẫu" } }
```

**Thành công: danh sách có phân trang** (format mặc định của Laravel Resource collection)
```json
{
  "data": [ { "id": 1, "name": "…" } ],
  "links": { "first": "…", "last": "…", "prev": null, "next": "…" },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 10, "total": 25 }
}
```

**Lỗi**
```json
{ "message": "Bài làm đã được nộp.", "code": "ATTEMPT_ALREADY_SUBMITTED" }
```
```json
{ "message": "The given data was invalid.", "code": "VALIDATION_FAILED",
  "errors": { "answers.12": ["…"] } }
```

## 2. Status code & mã lỗi

| HTTP | `code` | Khi nào |
|---|---|---|
| 200 | – | Đọc, cập nhật, nộp bài, resume attempt |
| 201 | – | Tạo attempt mới |
| 204 | – | Logout |
| 401 | `UNAUTHENTICATED` | Chưa đăng nhập hoặc token sai |
| 403 | `FORBIDDEN` | Sai role, không sở hữu attempt, signed URL sai hoặc hết hạn |
| 404 | `NOT_FOUND` | Không tìm thấy resource |
| 409 | `ATTEMPT_ALREADY_SUBMITTED` | Nộp lại attempt đã nộp |
| 409 | `ATTEMPT_CLOSED` | Autosave khi attempt đã hết giờ hoặc đã nộp |
| 409 | `ATTEMPT_NOT_SUBMITTED` | Xem kết quả khi attempt chưa nộp |
| 422 | `VALIDATION_FAILED` | Input sai |
| 422 | `EXAM_NOT_AVAILABLE` | Đề chưa publish hoặc không có câu hỏi |
| 429 | `TOO_MANY_REQUESTS` | Vượt rate limit (preview) |

## 3. Endpoints

### Auth
| Method | Path | Mô tả |
|---|---|---|
| POST | `/api/auth/login` | `{email, password, device_name}` → `{data: {token, user}}` |
| POST | `/api/auth/logout` | Thu hồi token hiện tại → 204 |
| GET | `/api/me` | Thông tin user hiện tại |

### Đề thi & làm bài (student) – T11, T12, T13, T16, T20
| Method | Path | Mô tả | Status |
|---|---|---|---|
| GET | `/api/exams?q=&page=` | Danh sách đề đã publish (cache, T19) | 200 |
| GET | `/api/exams/{exam}` | Chi tiết đề (không có câu hỏi), đề nháp trả 404 | 200 / 404 |
| POST | `/api/exams/{exam}/attempts` | Bắt đầu, hoặc trả về attempt đang mở | 201 / 200 |
| GET | `/api/attempts/{attempt}` | Câu hỏi đã trộn, đáp án đã lưu, `remaining_seconds` | 200 |
| PUT | `/api/attempts/{attempt}/answers/{questionId}` | Autosave `{option_ids: [int]}` | 200 / 409 |
| POST | `/api/attempts/{attempt}/submit` | Nộp `{answers?: {"<qid>": [int]}}` | 200 / 409 |
| GET | `/api/attempts/{attempt}/result` | Kết quả, câu sai, đáp án đúng | 200 / 409 nếu chưa nộp |

Các route `/api/attempts/{attempt}…` trả **403** nếu attempt không thuộc user hiện tại (`AttemptPolicy`), **404** nếu id không tồn tại.

**`GET /api/attempts/{id}`** (đang làm, **không có `is_correct`**)
```json
{ "data": {
  "id": "6725f0…", "exam": { "id": 3, "name": "Đề mẫu", "duration_minutes": 15 },
  "status": "in_progress", "started_at": "…", "expires_at": "…", "remaining_seconds": 734,
  "questions": [
    { "question_id": 12, "content": "…", "options": [ { "option_id": 40, "content": "…" } ] }
  ],
  "answers": { "12": [40] }
} }
```

**`POST /api/attempts/{id}/submit`** → 200
```json
{ "data": {
  "id": "6725f0…", "status": "submitted", "is_late": false,
  "score": 7.5, "correct_count": 6, "total_questions": 8,
  "submitted_at": "…", "result_url": "/api/attempts/6725f0…/result"
} }
```

**`GET /api/attempts/{id}/result`**
```json
{ "data": {
  "score": 7.5, "correct_count": 6, "total_questions": 8, "is_late": false,
  "questions": [
    { "question_id": 12, "content": "…", "is_correct": false,
      "selected_option_ids": [41], "correct_option_ids": [40],
      "options": [ { "option_id": 40, "content": "…", "is_correct": true } ] }
  ]
} }
```

### Admin – T14
| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/admin/stats/exams` | Điểm TB theo đề |
| GET | `/api/admin/stats/top-students` | Top 10 học sinh |
| GET | `/api/admin/stats/most-missed-questions` | Câu sai nhiều nhất |

CRUD đề và câu hỏi là trang web `/admin/exams…` (Blade), không có trong API.

### Gói IELTS – T21
| Method | Path | Mô tả | Status |
|---|---|---|---|
| GET | `/api/packages?level=&q=&page=` | Danh sách gói (`documents_count`, giá, ảnh bìa) | 200 |
| GET | `/api/packages/{slug}` | Chi tiết + mục lục theo Unit + tài liệu (`is_locked`) | 200 / 404 |
| POST | `/api/documents/{document}/preview` | `throttle:preview` → `{url, expires_at, preview_pages \| preview_seconds, total_pages}` | 200 / 429 |
| GET | `/preview/documents/{document}?expires=&signature=` | Middleware `signed`, stream bản xem thử | 200 / 403 |
| GET | `/api/admin/stats/previews` | Gói và tài liệu được xem thử nhiều nhất | 200 |
