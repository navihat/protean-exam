# Kiến trúc

## 1. Hạ tầng (Docker)

```
            :8000
  browser ───────► nginx ──fastcgi:9000──► app (php-fpm 8.3)
                                            │        │
                                       mysql:3306  mongo:27017
                                       (MySQL 8)   (MongoDB 7)
  node (one-off): npm ci && npm run build → public/build
```

| Service | Image | Ghi chú |
|---|---|---|
| `app` | build từ `docker/php/Dockerfile` | PHP 8.3-fpm, ext `pdo_mysql`, `mongodb`, `intl`, `zip`, `bcmath`; entrypoint tự cài vendor, key, migrate, seed |
| `nginx` | `nginx:1.27-alpine` | Serve `public/`, chuyển `.php` sang `app` |
| `mysql` | `mysql:8.0` | DB `exam` cho app, `exam_testing` cho test (tạo bằng `docker/mysql/init.sql`) |
| `mongo` | `mongo:7.0` | DB `exam` cho app, `exam_testing` cho test |
| `node` | `node:22-alpine` | Build asset Vite một lần rồi thoát |

## 2. Cấu trúc thư mục dự kiến

```
app/
├── Console/Commands/
│   └── CheckConnections.php            # T05  app:check-connections
├── Enums/
│   ├── UserRole.php                    # admin | student
│   ├── AttemptStatus.php               # in_progress | submitted
│   └── DocumentType.php                # pdf | audio            (T21)
├── Events/                             # T15  domain events
│   ├── ContentChanged.php              # exam/question created|updated|deleted (từ Observer)
│   ├── AttemptStarted.php
│   ├── AttemptSubmitted.php
│   └── DocumentPreviewed.php           # (T21)
├── Exceptions/                         # domain exceptions, render ra {message, code}
│   ├── DomainException.php
│   ├── AttemptAlreadySubmittedException.php
│   └── AttemptNotOwnedException.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                      # web, middleware auth + role:admin
│   │   │   ├── ExamController.php              # T10
│   │   │   ├── QuestionController.php          # T10
│   │   │   ├── StatisticsController.php        # T14
│   │   │   └── PreviewStatisticsController.php # T22
│   │   ├── Student/                    # web pages (Blade shell + Alpine)
│   │   │   ├── ExamPageController.php
│   │   │   ├── AttemptPageController.php       # T11/T20 trang làm bài
│   │   │   ├── ResultPageController.php        # T13
│   │   │   └── PackagePageController.php       # T22
│   │   └── Api/                        # JSON, auth:sanctum
│   │       ├── AuthController.php              # login token cho Postman
│   │       ├── ExamController.php              # T16 GET /api/exams
│   │       ├── AttemptController.php           # start/show/answer/submit/result
│   │       ├── PackageController.php           # T21
│   │       ├── DocumentPreviewController.php   # T21
│   │       └── Admin/StatisticsController.php  # T14/T21 stats API
│   ├── Middleware/EnsureUserHasRole.php        # T09
│   ├── Requests/                       # T10/T17 mọi input qua FormRequest
│   │   ├── Admin/{Store,Update}ExamRequest.php
│   │   ├── Admin/{Store,Update}QuestionRequest.php
│   │   └── Api/{SaveAnswer,SubmitAttempt,ListPackages}Request.php
│   └── Resources/                      # T16 format response
│       ├── ExamResource.php
│       ├── AttemptResource.php
│       ├── AttemptQuestionResource.php  # KHÔNG có is_correct
│       ├── AttemptResultResource.php    # có đáp án đúng, chỉ sau khi nộp
│       ├── PackageResource.php / PackageDetailResource.php
│       └── DocumentResource.php
├── Listeners/RecordActivity.php        # T15 nơi DUY NHẤT ghi activity_logs
├── Models/
│   ├── User.php  Exam.php  Question.php  Option.php          # MySQL
│   ├── Package.php  Unit.php  Document.php                   # MySQL (T21)
│   └── Mongo/
│       ├── Attempt.php                 # connection mongodb
│       ├── ActivityLog.php
│       └── PreviewLog.php              # (T21)
├── Observers/ExamObserver.php  QuestionObserver.php          # T15 + bust cache T19
├── Providers/AppServiceProvider.php    # preventLazyLoading, RateLimiter, observers
└── Services/                           # T17 toàn bộ business logic
    ├── Exam/ExamService.php            # CRUD + search + cache
    ├── Exam/QuestionService.php
    ├── Attempt/AttemptService.php      # start/resume, autosave, finalize
    ├── Attempt/GradingService.php      # T12 chấm điểm thuần, dễ unit test
    ├── Statistics/ExamStatisticsService.php      # T14 aggregation
    ├── Package/PackageService.php                # T21
    ├── Package/DocumentPreviewService.php        # T21 signed URL + cắt file
    └── Statistics/PreviewStatisticsService.php   # T21

config/exam.php                         # list_cache_seconds (T19); grace seconds sẽ thêm ở PR6
database/
├── migrations/                         # MySQL + migration tạo index Mongo
├── factories/
└── seeders/                            # idempotent: chạy lại không nhân bản dữ liệu
routes/ web.php  api.php  console.php
tests/
├── Feature/
│   ├── Auth/RoleMiddlewareTest.php
│   ├── Admin/ExamCrudTest.php
│   ├── Api/ExamIndexTest.php
│   ├── Api/StartAttemptTest.php
│   ├── Api/AutosaveTest.php
│   ├── Api/SubmitAttemptTest.php       # chấm điểm + nộp quá giờ (bắt buộc)
│   ├── Admin/StatisticsTest.php
│   ├── Console/CheckConnectionsTest.php
│   └── Api/DocumentPreviewTest.php     # T21
├── Unit/GradingServiceTest.php
└── Concerns/RefreshMongo.php           # truncate collection Mongo giữa các test
docker/  docs/  .github/  .claude/
```

## 3. Luồng chính

### Bắt đầu / tiếp tục làm bài (T11, T20)
```
POST /api/exams/{exam}/attempts
 └ AttemptController@store → AttemptService::startOrResume(user, exam)
     ├ tìm attempt in_progress (user, exam)
     │   ├ còn hạn  → trả attempt cũ (200)
     │   └ hết hạn → finalize (chấm phần đã lưu), rồi tạo mới
     ├ load exam.questions.options (eager), shuffle câu và đáp án
     ├ insert attempt {snapshot, expires_at, status: in_progress}
     │   └ duplicate key (race) → đọc lại attempt đang mở (200)
     └ event AttemptStarted → RecordActivity
```

### Autosave (T20)
```
PUT /api/attempts/{attempt}/answers/{questionId}   body: {option_ids: [..]}
 └ AttemptService::saveAnswer → updateOne(
       {_id, user_id, status: in_progress, expires_at > now},
       {$set: {"answers.{qid}": [...], updated_at}})
     matched = 0 → 409 ATTEMPT_CLOSED
```

### Activity log và cache danh sách đề (T15, T19)
```
Admin sửa đề/câu hỏi → ExamService / QuestionService → Eloquent save/delete
 └ ExamObserver / QuestionObserver   (chạy SAU khi transaction commit)
     ├ ExamService::flushPublishedListCache()  → tăng exams:list-version
     └ ContentChanged::dispatch(...)  → RecordActivity → activity_logs
Login / Logout / Registered (web, và token API qua TokenAuthService) → RecordActivity
```

### Nộp bài (T12)
```
POST /api/attempts/{attempt}/submit   body: {answers?: {qid: [optionIds]}}
 └ AttemptService::submit
     ├ đã submitted        → 409
     ├ now ≤ expires_at+grace → merge answers request vào answers đã lưu
     ├ quá hạn              → bỏ answers request, is_late = true
     ├ GradingService::grade(snapshot, answers) → results[], correct_count, score
     ├ updateOne với điều kiện status=in_progress (atomic, tránh nộp 2 lần)
     └ event AttemptSubmitted → RecordActivity
```

## 4. Schema

### MySQL
```
users      id, name, email[unique], password, role[index], timestamps
exams      id, name[index], description?, duration_minutes, is_published[index],
           created_by → users.id (nullOnDelete), timestamps, deleted_at
questions  id, exam_id → exams.id (cascade)[index], content, position, timestamps
options    id, question_id → questions.id (cascade)[index], content, is_correct, position, timestamps
-- T21
packages   id, slug[unique], name, level[index] (1..3), description, cover_path, price, is_published, timestamps
units      id, package_id → packages (cascade), title, position
documents  id, unit_id → units (cascade), title, type (pdf|audio), file_path (private),
           page_count?, preview_pages?, duration_seconds?, preview_seconds?, is_free, timestamps
```

### MongoDB
```js
// attempts
{
  _id, user_id: 12, exam_id: 3, status: "in_progress" | "submitted",
  exam: { name: "…", duration_minutes: 15 },
  snapshot: [ { question_id, content, options: [ { option_id, content, is_correct } ] } ],  // đã trộn
  answers: { "<question_id>": [option_id, …] },
  results: [ { question_id, content, selected: [..], correct: [..], is_correct: false } ], // sau khi chấm
  correct_count, total_questions, score, is_late,
  started_at, expires_at, submitted_at, created_at, updated_at
}
// index: {user_id:1}, {exam_id:1}, {created_at:-1}, {status:1, expires_at:1},
//        {user_id:1, exam_id:1} unique partial {status:"in_progress"}

// activity_logs
{ _id, user_id, action: "attempt.submitted", subject_type: "attempt", subject_id, meta: {…}, ip, user_agent, created_at }
// index: {user_id:1}, {action:1}, {created_at:-1}

// preview_logs (T21)
{ _id, user_id?, ip, document_id, package_id, created_at }
// index: {document_id:1}, {package_id:1}, {created_at:-1}
```

## 5. Thống kê (T14), các pipeline aggregation
- **Điểm TB theo đề:** `$match {status: submitted}` → `$group {_id: $exam_id, avg: {$avg: $score}, attempts: {$sum: 1}}` → `$sort`.
- **Top 10 học sinh:** `$match submitted` → `$group {_id: $user_id, avg: {$avg: $score}, best: {$max: $score}, attempts: {$sum:1}}` → `$sort {avg:-1, attempts:-1}` → `$limit 10`.
- **Câu sai nhiều nhất:** `$match submitted` → `$unwind $results` → `$match {results.is_correct: false}` → `$group {_id: $results.question_id, wrong: {$sum:1}}` → `$sort` → `$limit 10`. Nội dung câu hỏi lấy bằng `$first: $results.content` (đã chép từ snapshot), nên vẫn hiển thị được khi câu đã bị xoá.
