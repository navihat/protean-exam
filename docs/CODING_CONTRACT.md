# Coding Contract

Các quy tắc **bắt buộc** cho mọi code trong repo, áp dụng cho cả người và AI agent.
Mỗi quy tắc có mã `C<n>` để trích dẫn khi review ("vi phạm C7").
Muốn phá lệ thì ghi lý do vào PR và [DECISIONS.md](DECISIONS.md).

## 0. Nguyên tắc chung
- **C0.1** Code phải **giải thích được** trong buổi review. Không thêm thư viện, pattern hay lớp trừu tượng mà đề không cần. Đơn giản quan trọng hơn khéo léo.
- **C0.2** Mỗi thay đổi gắn với một task `T<nn>` trong [REQUIREMENTS.md](REQUIREMENTS.md).
- **C0.3** Code, tên biến và commit message viết bằng **tiếng Anh**. Tài liệu trong `docs/` và README viết **tiếng Việt**.
- **C0.4** Format bằng **Laravel Pint** (preset `laravel`). CI chạy `pint --test`.
- **C0.5** Không commit `.env`, file PDF đề bài, dữ liệu trong `storage/`, `vendor/`, `node_modules/`.

## 1. Phân lớp

```
Route → Middleware → FormRequest → Controller → Service → Model / Mongo
                                        │            └→ Event → Listener
                                        └→ Resource (API) | View (web)
```

| Lớp | Được làm | Không được làm |
|---|---|---|
| **Controller** | Nhận FormRequest, gọi **một** method Service, trả Resource/View/redirect | Query builder, business rule, `try/catch` nghiệp vụ, ghi log, `Cache::` |
| **FormRequest** | Rule validate, `authorize()`, `prepareForValidation()` | Gọi Service, ghi DB |
| **Service** | Business logic, transaction, gọi Model, dispatch Event | Đọc `request()`, trả `response()`/view, phụ thuộc HTTP |
| **Model** | Quan hệ, cast, scope (`scopeSearch`), accessor | Business logic nhiều bước |
| **Resource** | Định dạng output, ẩn field nhạy cảm | Query thêm (chỉ dùng `whenLoaded`) |
| **Listener/Observer** | Side-effect: activity log, bust cache | Business logic chính |

- **C1.1** Controller action **≤ ~10 dòng**. Dài hơn là dấu hiệu logic chưa vào Service.
- **C1.2** Service nhận tham số kiểu rõ ràng (`User $user, Exam $exam, array $validated`), không nhận `Request`.
- **C1.3** Service được inject qua constructor, không dùng facade `app()` hay `resolve()` trong code nghiệp vụ.
- **C1.4** Web controller và API controller **dùng chung Service**. Không được nhân đôi logic.

## 2. Validation (FormRequest)
- **C2.1** Mọi input từ người dùng đi qua **FormRequest**. Không gọi `$request->validate()` trong controller.
- **C2.2** Controller chỉ dùng `$request->validated()`, không dùng `$request->all()`.
- **C2.3** Câu hỏi phải có ≥ 2 đáp án và ≥ 1 đáp án đúng. Rule này validate trong `StoreQuestionRequest` bằng closure hoặc rule class.

## 3. Eloquent & N+1
- **C3.1** `Model::preventLazyLoading(! app()->isProduction())` được bật trong `AppServiceProvider`. Test sẽ fail nếu có lazy load.
- **C3.2** List dùng `with()` hoặc `withCount()`. Phân trang bằng `paginate()` với `->withQueryString()`.
- **C3.3** Tìm kiếm theo tên viết trong scope `scopeSearch(Builder $q, ?string $term)`.
- **C3.4** Thao tác nhiều bảng MySQL (tạo câu hỏi cùng đáp án) phải bọc trong `DB::transaction`.
- **C3.5** Khai báo `$fillable` rõ ràng, không dùng `$guarded = []`.

## 4. MongoDB
- **C4.1** Model Mongo nằm trong `App\Models\Mongo\*`, kế thừa `MongoDB\Laravel\Eloquent\Model` và khai báo `$connection = 'mongodb'`.
- **C4.2** Id tham chiếu sang MySQL (`user_id`, `exam_id`…) luôn lưu và cast thành **int**.
- **C4.3** Không gọi MySQL bên trong vòng lặp kết quả Mongo. Gom id lại rồi gọi **một** truy vấn `whereIn` (D11).
- **C4.4** Cập nhật có điều kiện trạng thái (autosave, submit) phải là **một lệnh atomic** có điều kiện trong filter. Không làm kiểu đọc → sửa → ghi.
- **C4.5** Index Mongo được tạo bằng **migration** (`Schema::connection('mongodb')`), không tạo tay.
- **C4.6** Thống kê dùng **aggregation pipeline** (`raw(fn ($c) => $c->aggregate([...]))`), không kéo dữ liệu về rồi tính bằng PHP.

## 5. Bảo mật & chống gian lận
- **C5.1** Client **không bao giờ** gửi điểm hay cờ đúng/sai. Server chỉ nhận `option_ids`.
- **C5.2** `is_correct` **không xuất hiện** trong bất kỳ response nào trước khi attempt được nộp. Bắt buộc có test kiểm tra.
- **C5.3** Kiểm tra quyền sở hữu attempt (`attempt.user_id === auth id`) bằng Policy (`AttemptPolicy`). Không thuộc quyền thì trả 403.
- **C5.4** `option_ids` gửi lên phải thuộc câu hỏi tương ứng trong snapshot. Id lạ thì bỏ qua (hoặc trả 422).
- **C5.5** Thời gian luôn lấy từ server (`now()`). Không tin bất kỳ timestamp nào từ client.
- **C5.6** File gốc (T21) chỉ nằm ở disk private. Không có route nào trả file gốc.

## 6. Event & Activity log
- **C6.1** `ActivityLog` **chỉ được ghi trong** `App\Listeners\RecordActivity`. Dùng grep `ActivityLog::` để kiểm tra: kết quả chỉ được nằm trong Listeners hoặc test.
- **C6.2** Service dispatch event **sau khi** ghi DB thành công.
- **C6.3** Tên action theo dạng `<subject>.<verb>` ở thì quá khứ: `exam.created`, `attempt.submitted`, `auth.login`.

## 7. API
- **C7.1** Mọi response thành công trả qua **API Resource**. Format, status code và mã lỗi theo [API_CONTRACT.md](API_CONTRACT.md).
- **C7.2** Lỗi nghiệp vụ được ném thành exception kế thừa `App\Exceptions\DomainException` (có `code` và `status`) và render tập trung ở `bootstrap/app.php`.
- **C7.3** Route API đặt tên (`->name('api.attempts.submit')`). Test gọi route qua `route()`.

## 8. Test
- **C8.1** Mỗi PR tính năng **kèm feature test** cho đường đi chính và ít nhất một case lỗi.
- **C8.2** Test chạy trên MySQL `exam_testing` và Mongo `exam_testing`. MySQL reset bằng `RefreshDatabase`, Mongo bằng trait `Tests\Concerns\RefreshMongo`.
- **C8.3** Test thời gian dùng `$this->travel()` hoặc `Carbon::setTestNow()`. Không dùng `sleep()`.
- **C8.4** Test không phụ thuộc thứ tự chạy và không phụ thuộc dữ liệu seed.
- **C8.5** Lệnh `php artisan test` phải pass trước khi mở PR.

## 9. Đặt tên
| Loại | Quy ước | Ví dụ |
|---|---|---|
| Service | `<Domain>Service` | `AttemptService`, `GradingService` |
| FormRequest | `<Verb><Model>Request` | `StoreExamRequest` |
| Resource | `<Model>[Variant]Resource` | `AttemptResultResource` |
| Event | `<Subject><PastVerb>` | `AttemptSubmitted` |
| Enum | PascalCase, backed string | `AttemptStatus::InProgress = 'in_progress'` |
| Route | `{area}.{resource}.{action}` | `admin.exams.index`, `api.attempts.submit` |
| Bảng/collection | snake_case số nhiều | `activity_logs` |
| Config | `config/exam.php` | `exam.submit_grace_seconds` |

## 10. Definition of Done (cho mỗi task/PR)
- [ ] Đạt **tiêu chí hoàn thành** của task trong REQUIREMENTS.md
- [ ] Không vi phạm quy tắc C nào (hoặc có ghi chú phá lệ)
- [ ] Có feature test; `php artisan test` xanh; `pint --test` sạch
- [ ] Cập nhật API_CONTRACT, ARCHITECTURE, DECISIONS nếu có thay đổi
- [ ] Tick checkbox trong ROADMAP.md
- [ ] PR mô tả được **"vì sao"**, không chỉ "làm gì"
