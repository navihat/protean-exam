# Git / GitHub Flow

Đề chấm cả **lịch sử commit** (T01), nên mọi thay đổi đi qua nhánh, PR và CI rồi mới vào `main`.

## 1. Nhánh
- `main`: luôn chạy được (`docker compose up` + `php artisan test` xanh). **Không commit trực tiếp.**
- Nhánh làm việc tách từ `main` mới nhất, **mỗi nhánh tương ứng một PR trong [ROADMAP.md](ROADMAP.md)**.

| Prefix | Dùng cho | Ví dụ |
|---|---|---|
| `feat/` | Tính năng | `feat/T11-T12-attempt-flow` |
| `fix/` | Sửa lỗi | `fix/T12-late-submit-grace` |
| `chore/` | Setup, Docker, CI, dependency | `chore/T01-T02-bootstrap-docker` |
| `test/` | Chỉ thêm test | `test/T18-statistics` |
| `docs/` | Tài liệu | `docs/T06-readme` |
| `refactor/` | Đổi cấu trúc, không đổi hành vi | `refactor/T17-attempt-service` |

Tên nhánh: `<prefix>/T<nn>[-T<nn>]-<mo-ta-ngan-kebab>`.

## 2. Commit
Dùng [Conventional Commits](https://www.conventionalcommits.org/), có thêm mã task:

```
<type>(<scope>): <mô tả ngắn, tiếng Anh, thì hiện tại> [T<nn>]

<body: vì sao thay đổi, nếu cần>
```

- `type`: `feat` `fix` `chore` `test` `docs` `refactor` `perf` `style`
- `scope`: `docker` `auth` `exam` `question` `attempt` `grading` `stats` `activity` `api` `package` `preview` `ui` `ci`
- Ví dụ:
  - `chore(docker): add php-fpm image with mongodb extension [T02]`
  - `feat(attempt): shuffle question order when starting attempt [T11]`
  - `feat(attempt): enforce single open attempt with partial unique index [T20]`
  - `test(grading): cover late submission grading saved answers only [T18]`

Quy tắc:
- **Mỗi commit là một bước logic nhỏ và tự chạy được.** Không có commit "wip" hay "fix" vô nghĩa trên `main`. Nếu có thì dọn bằng `git rebase` trước khi mở PR.
- Commit code và test của nó **cùng nhau**, hoặc test ngay sau.
- Không commit file sinh ra (`vendor`, `public/build`, `.env`).

## 3. Quy trình một task

```
git switch main && git pull
git switch -c feat/T10-admin-exam-crud
# code theo CODING_CONTRACT; commit từng bước nhỏ
docker compose exec app ./vendor/bin/pint
docker compose exec app php artisan test
# tự review: chạy agent contract-reviewer trên diff
git push -u origin feat/T10-admin-exam-crud
gh pr create --fill --base main     # template PR sẽ tự điền
# CI xanh → merge → xoá nhánh → tick ROADMAP
```

## 4. Pull Request
- Một PR tương ứng **một mục trong ROADMAP**. Nên ≤ ~600 dòng diff (không tính file sinh ra).
- Dùng template `.github/pull_request_template.md`: task, cái gì, vì sao, cách test, checklist contract.
- **Điều kiện merge:** CI xanh (Pint + test) và checklist Definition of Done được tick.
- **Kiểu merge:** **"Create a merge commit"** (`--no-ff`). Không dùng squash, vì squash làm mất các commit từng bước mà người chấm muốn xem. Merge commit thì vẫn nhóm được theo PR.
- Merge xong thì xoá nhánh.

## 5. Bảo vệ `main` (cài trên GitHub)
- Settings → Branches → Add rule cho `main`:
  - Require a pull request before merging
  - Require status checks to pass: `ci / tests`
  - Allow **merge commits** only (tắt squash và rebase)
- Repo cá nhân thì không cần "required reviewers"; dùng agent `contract-reviewer` để tự review.

## 6. Tag & nộp bài
- `v0.1.0`: xong core (T01–T19)
- `v0.2.0`: xong T20
- `v1.0.0`: bản nộp, README hoàn chỉnh
- Trước khi nộp: clone repo ra thư mục trống và chạy đúng các lệnh trong README để chắc chắn người khác chạy được.
