@AGENTS.md

## Riêng cho Claude Code
- Trả lời người dùng bằng **tiếng Việt**.
- Subagent của dự án nằm trong `.claude/agents/`:
  - `laravel-implementer`: code một PR trong ROADMAP theo contract
  - `test-engineer`: viết hoặc sửa feature test (MySQL + Mongo, thời gian, race)
  - `contract-reviewer`: review diff theo CODING_CONTRACT và tiêu chí của đề, **chỉ đọc**
- Trước khi mở PR: chạy `contract-reviewer` trên `git diff main...HEAD` và sửa hết các vi phạm mức **Blocker**.
- Chỉ commit, push hay mở PR khi người dùng yêu cầu hoặc đã cho phép trong phiên.
