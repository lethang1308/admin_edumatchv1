# Giai đoạn 2 — Danh mục CMS & Trung tâm xác minh

BE: `C:\laragon\www\admin_edumatchv1`; FE: `D:\Production\Edumatchv1`.

## Sử dụng

- Đăng nhập CMS Blade tại `/login` bằng tài khoản có Spatie role `super_admin` hoặc `admin`, trạng thái active. Sidebar có Môn học, Cấp lớp, Tra cứu khu vực, Banner và Trung tâm xác minh.
- Giáo viên/trung tâm đăng nhập FE, mở biểu tượng khiên ở header, menu di động “Xác minh hồ sơ”, liên kết trong cài đặt hoặc `/xac-minh`.
- Giáo viên nộp ít nhất một bằng cấp/chứng chỉ và hai mặt CCCD. Có thể thêm chứng chỉ IELTS, TOEIC, HSK, nghiệp vụ sư phạm, tin học hoặc chuyên môn khác.
- Trung tâm nộp đăng ký doanh nghiệp, giấy phép giáo dục/đào tạo, hai mặt CCCD người đại diện. Mỗi tài liệu có tên, đơn vị cấp, năm cấp và tệp JPG/PNG/PDF.
- Mỗi tệp tối đa 2 MB; toàn bộ hồ sơ tối đa 7 MB, tối đa 8 tài liệu. Giới hạn này nằm trong cấu hình PHP hiện tại (upload/post 40 MB).
- Sau khi nộp, hồ sơ chuyển pending và không thể nộp trùng. Người dùng xem lịch sử và xem trước tài liệu riêng tư ngay trong FE.
- Admin mở Trung tâm xác minh, lọc theo trạng thái, loại đối tượng hoặc tên/SĐT/email; xem từng ảnh/PDF, sau đó xác nhận duyệt hoặc nhập lý do từ chối (tối đa 1000 ký tự).
- Duyệt gán approved cho request/document, verified cho profile/center và verified_at. Từ chối gán rejected, giữ lý do; người nộp có thể nộp bộ hồ sơ mới. Tất cả lịch sử được giữ lại.
- Kết quả xuất hiện trong thông báo tài khoản; FE tải lại khi mở thông báo hoặc quay lại tab. Hồ sơ đã verified không được nộp thêm và admin không thể tự duyệt hồ sơ của mình.

## CMS

| Đường dẫn | Chức năng |
| --- | --- |
| `/admin/subjects` | Tìm kiếm, phân trang, thêm/sửa/xóa/bật-tắt môn, tự sinh slug, icon |
| `/admin/education-levels` | CRUD mã cấp lớp, thứ tự và hiển thị |
| `/admin/administrative-areas` | Tra cứu/lọc danh mục hành chính, khu vực cha |
| `/admin/banners` | Ảnh, liên kết HTTP/HTTPS, vị trí, thứ tự, lịch bắt đầu/kết thúc, bật-tắt |
| `/admin/verifications` | KPI chờ duyệt, danh sách/lọc và thẩm định |
| `/admin/verifications/teachers`, `/centers` | Lọc riêng giáo viên/trung tâm |
| `/admin/verifications/{id}` | Hồ sơ, file preview, quyết định, người/thời điểm duyệt |

Môn học tối đa 150 ký tự, slug 180. Cấp lớp: code tối đa 30, dạng chữ thường/số/gạch ngang, tên tối đa 100. Môn hoặc cấp lớp đang có liên kết không thể xóa; có thể tắt hiển thị. Ảnh cũ được dọn sau khi thay thế/xóa thành công. SVG môn học được kiểm tra cấu trúc và từ chối script, sự kiện, style, thực thể XML, tài nguyên ngoài; banner chỉ nhận JPG/PNG/WebP.

## API

| Method và endpoint | Nội dung |
| --- | --- |
| `GET /api/subjects` | Môn hoạt động hoặc có bài approved, theo sort_order/id, có icon_url |
| `GET /api/education-levels` | Cấp lớp active theo thứ tự |
| `GET /api/banners` | Banner trang chủ active, đúng khoảng thời gian, theo thứ tự |
| `GET /api/administrative-areas/provinces` | Tỉnh/thành active từ DB |
| `GET /api/administrative-areas/districts/{provinceId}` | Quận/huyện con khi có dữ liệu |
| `GET /api/administrative-areas/wards/{parentId}` | Phường/xã theo tỉnh hoặc quận/huyện |
| `GET /api/administrative-areas?level=...&parent_id=...` | API tương thích Giai đoạn 1 |
| `GET /api/provider-verifications?user_ids[]=1` | Trạng thái công khai, tối đa 100 user IDs/request |
| `GET /api/providers/{userId}` | Hồ sơ nhà cung cấp công khai, whitelist, điện thoại theo quyền hiển thị |
| `GET /api/verifications?page=1` | Lịch sử chỉ của tài khoản hiện tại, trạng thái, loại tài liệu |
| `POST /api/verifications` | Nộp multipart hồ sơ giáo viên/trung tâm (Sanctum + active) |
| `GET /api/verifications/documents/{id}` | Xem file, chỉ chủ hồ sơ |
| `GET /api/notifications` | 50 thông báo tài khoản mới nhất |
| `POST /api/notifications/{id}/read` | Đánh dấu đã đọc, chỉ người nhận |
| `POST /api/notifications/read-all` | Đánh dấu tất cả thông báo của tài khoản |

Các API public trả `{data: ...}`. Multipart nộp hồ sơ có `documents[0][document_type]`, `[document_name]`, `[issuing_authority]`, `[issued_year]`, `[file]` rồi tăng index. Ownership, status và reviewer luôn được BE quyết định, không lấy từ client.

Tên loại tài liệu được lấy từ `config/verification.php`. Schema gốc dùng `original_name`, quan hệ `teacher` và `verificationDocuments`; không tạo các cột trùng `file_name` hoặc quan hệ không tồn tại từ ví dụ PLAN.

## Dữ liệu và migrations

- `2026_10_19_000001_add_verification_document_metadata`: thêm document_name, issuing_authority, issued_year nullable, tương thích hồ sơ cũ.
- `2026_10_19_000002_expand_subject_names`: mở rộng name/slug đúng giới hạn CMS; rollback có kiểm tra độ dài trước khi thu hẹp.
- `Phase2MasterDataSeeder`: 13 môn, 16 cấp lớp (1–12, mầm non, luyện thi, đại học, người đi làm). Chạy lại giữ các thay đổi tên/slug/thứ tự/trạng thái của admin và không tạo tài khoản mật khẩu mặc định.
- Danh mục hành chính tiếp tục dùng 34 tỉnh/thành, 3321 phường/xã đã có trong dự án; không giả lập 63 tỉnh hoặc quận/huyện không có trong DB. Endpoint quận/huyện hỗ trợ dữ liệu lịch sử khi được nhập.

```powershell
php artisan migrate --force
php artisan db:seed --class=Phase2MasterDataSeeder --force
```

Hai migration và seeder đã chạy trên database dự án ngày 19/10/2026.

## File riêng tư, review và huy hiệu

File nộp lưu bằng tên ngẫu nhiên dưới `storage/app/verification_documents/{requestId}` trên disk `verification`, không nằm trong symlink public/storage. JSON không trả file_path. Route xem file kiểm tra owner hoặc quyền admin, trả MIME chuẩn, nosniff và private/no-store. FE lấy Blob bằng Bearer token, không đưa token lên URL và thu hồi object URL khi đóng preview.

Submit/review khóa hàng user, request bằng transaction để chặn nộp trùng, duyệt lặp và duyệt hồ sơ cũ. Review cập nhật request, document, profile và notification cùng transaction. Lỗi upload/insert dọn những file vừa tạo, không giữ hồ sơ dở dang.

Preview PDF dùng Mozilla PDF.js 6.4.299 tự lưu trữ: FE tải động `PdfPreview` khi cần, CMS dùng `/assets/pdfjs/pdf.mjs` và worker cùng origin, kèm giấy phép Apache-2.0. Không gửi CCCD/chứng chỉ tới dịch vụ đọc PDF bên ngoài. Có chuyển trang và phóng to; PDF được bảo vệ bằng mật khẩu/hỏng sẽ có thông báo lỗi. Tệp public/assets/pdfjs được sao chép từ đúng phiên bản npm `pdfjs-dist` trong FE; cập nhật đồng bộ library/worker khi nâng cấp. Tài liệu tham khảo: [PDF.js examples](https://mozilla.github.io/pdf.js/examples/), [Laravel storage](https://laravel.com/docs/10.x/filesystem).

VerifiedBadge tại thẻ tìm kiếm/khóa học, chi tiết khóa học và trang cá nhân chỉ dựa trên API (status verified và verified_at), không tin các cờ verified cục bộ. Nhiều badge được gộp request theo batch; ID demo không thể tự biến thành huy hiệu xác minh. Hồ sơ công khai numeric lấy từ API whitelist, không lộ ngày sinh/CCCD/email riêng.

Các luồng đăng khóa học, feed và tương tác demo có sẵn ngoài phạm vi Giai đoạn 2 tiếp tục dùng cơ chế của dự án. Danh mục môn/cấp lớp/khu vực, banner, nộp/duyệt hồ sơ, thông báo xác minh và trạng thái huy hiệu đã dùng API thật.

## Thông báo realtime Pusher

`NotificationCreated` phát event `notification.created` vào private channel `private-user.{id}`. Kênh này xác thực tại `POST /broadcasting/auth` bằng Bearer token Sanctum/API; người dùng chỉ xác thực được kênh có ID của chính mình. `RealtimeNotificationService` lưu notification rồi chỉ phát sau khi transaction database commit, vì vậy thao tác review bị rollback không hiện thông báo giả.

FE dùng singleton `src/services/pusherService.js`, lấy `VITE_PUSHER_APP_KEY`, `VITE_PUSHER_APP_CLUSTER` và `VITE_PUSHER_AUTH_ENDPOINT`. Header chỉ tải lịch sử `/api/notifications` khi khởi tạo phiên đăng nhập và sau đó nhận thông báo mới qua WebSocket; không còn listener `focus`, mở panel hay sự kiện nội bộ gọi lại API. Logout hoặc token hết hạn sẽ đóng Pusher. API `POST /api/notifications/{id}/read` và `POST /api/notifications/read-all` vẫn dùng để cập nhật trạng thái đã đọc.

## Kiểm thử

```powershell
php artisan test --filter='AdminPhase2Test|StudentAuthTest|Phase1CompletionTest|RegistrationEmailOtpTest'
# FE
npm run test:auth
npm run test:phase2
npm run lint
npm run build
```

AdminPhase2Test có 22 tests: CRUD/slug/limits/ảnh/SVG an toàn, cấp lớp, banner, API visibility/schedule/hierarchy, quyền admin, tài liệu riêng tư/ownership, upload PDF, rollback và dọn file, duyệt giáo viên/trung tâm, lý do từ chối và nộp lại, chặn nộp trùng và tự duyệt, thông báo riêng, Pusher private-channel authorization và seeder giữ chỉnh sửa. Dùng database MySQL tạm và Storage::fake; không thay đổi hồ sơ thực.

Kết quả bộ BE kết hợp: **60 tests / 742 assertions đạt**, bao gồm hồi quy đăng ký/email OTP, hồ sơ, mật khẩu và Giai đoạn 2.

FE có 24 test xác thực + 9 test Giai đoạn 2 đạt: FormData không nhận ownership/status từ client, Blob preview, API master data, batch/dedup badge, ID demo, retry, notifications, cấu hình Pusher và ánh xạ trạng thái từ BE. Lint file sửa không có lỗi/cảnh báo. Build thành công; bundle chính vẫn có cảnh báo kích thước Vite từ dự án.

Kiểm thử trình duyệt dùng database/tài khoản/tệp demo riêng: giáo viên nộp 3 tài liệu, admin xem ảnh và duyệt, FE hiển thị verified/notification. Trung tâm nộp 4 tài liệu gồm PDF, admin từ chối có lý do và FE mở lại form bổ sung. PDF.js đọc được trang PDF thực ở cả CMS và FE, kèm chuyển trang/phóng to. Các ảnh QA nằm trong `D:\Production\Edumatchv1\output\qa\phase2-*.png`; dữ liệu và tiến trình QA được dọn khi hoàn tất.
