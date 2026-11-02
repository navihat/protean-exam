## Task
<!-- Mã task và mục ROADMAP, ví dụ: T11, T12, T16 · PR6 -->

## Thay đổi gì
-

## Vì sao (quyết định kỹ thuật)
<!-- Liên kết D<n> trong docs/DECISIONS.md nếu có -->

## Cách kiểm tra
```bash
docker compose exec app php artisan test --filter=
```
<!-- Các bước kiểm tra tay / request mẫu nếu cần -->

## Checklist (Definition of Done – CODING_CONTRACT §10)
- [ ] Đạt tiêu chí hoàn thành của task trong `docs/REQUIREMENTS.md`
- [ ] Controller mỏng, logic trong Service, validate qua FormRequest (C1, C2)
- [ ] Không N+1, có eager loading (C3)
- [ ] Không lộ `is_correct` trước khi nộp, không tin dữ liệu điểm/thời gian từ client (C5)
- [ ] Activity log chỉ ghi qua Event/Listener/Observer (C6)
- [ ] Response API đúng `docs/API_CONTRACT.md` (C7)
- [ ] Có feature test, `php artisan test` xanh, `pint --test` sạch (C8)
- [ ] Đã cập nhật docs liên quan và tick `docs/ROADMAP.md`
- [ ] `contract-reviewer` trả về READY FOR PR

## Còn thiếu / ghi chú cho README
<!-- Phần chưa làm sẽ được ghi vào mục "Những gì chưa làm được và vì sao" -->
