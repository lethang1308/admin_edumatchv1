# Hoàn thiện Giai đoạn 1 EduMatch

BE: `C:\laragon\www\admin_edumatchv1`; FE: `D:\Production\Edumatchv1`.

Cập nhật đăng ký: cả ba role hiện bắt buộc email và OTP, bao gồm trung tâm. Xem [registration-email-otp.md](registration-email-otp.md) cho API, cấu hình gửi mail và kiểm thử.

## Các luồng đã triển khai

- Đăng ký học viên, giáo viên và trung tâm qua cùng `POST /api/auth/register`, phân loại FormRequest theo role. User, hồ sơ, Spatie role và Sanctum token nằm trong cùng transaction.
- Teacher lưu teacher_profiles: họ tên, ngày sinh, headline, bio, experience_years, show_phone. Trạng thái xác minh luôn khởi tạo unverified; client không được tự cấp quyền/xác minh.
- Center lưu centers: tên đầy đủ, founded_year, bio, hotline, contact_email và slug duy nhất. Slug có hậu tố user ID, tránh trùng khi đăng ký đồng thời hoặc khi trung tâm cũ đã xóa mềm.
- GET /api/auth/me, login và refresh tải cả student_profile/teacher_profile/center. FE ánh xạ thông tin chuyên môn từ hồ sơ BE khi khôi phục phiên.
- Trang `/profile` nằm sau ProtectedRoute, có thông tin cá nhân, khu vực, avatar và tab bảo mật. Header desktop/mobile có liên kết cài đặt; học viên đi tới trang này khi mở hồ sơ cá nhân.

Tên trung tâm được phép dài 200 ký tự theo centers.name. Vì users.name giới hạn 150 ký tự, tên tài khoản đại diện được giới hạn 150, còn centers.name và tên hiển thị FE vẫn giữ đủ 200 ký tự.

## API hồ sơ

| Endpoint | Quyền và hành vi |
| --- | --- |
| GET /api/profile | Tài khoản active; lấy user và hồ sơ theo role, kèm province/district/ward |
| PUT /api/profile hoặc /api/student/profile | Chỉ học viên; cập nhật tên, ngày sinh, bio tối đa 1000, khu vực |
| POST /api/profile/avatar | Tài khoản active; multipart `avatar`, JPG/PNG/WebP, tối đa 2 MB |
| GET /api/administrative-areas | Danh mục active; lọc `level` và `parent_id` |

Update chỉ nhận whitelist của FormRequest; không đổi email, phone, role, user_id hoặc các trường quyền từ payload. Đồng bộ users.name và student_profiles.full_name. Kiểm tra đúng cấp hành chính và quan hệ cha/con; hỗ trợ tỉnh → phường/xã trực tiếp và tỉnh → quận/huyện lịch sử → phường/xã.

Avatar lưu đường dẫn tương đối `avatars/...` trong users.avatar_path; API trả thêm avatar_url đầy đủ. Cách này giữ ảnh hoạt động khi đổi domain/port. Ảnh cũ được xóa sau khi cập nhật thành công; file mới được dọn nếu cập nhật DB lỗi. FE có preview và nút lưu ảnh riêng.

Public storage đã được liên kết bằng `php artisan storage:link`. Khi triển khai môi trường khác, chạy lại lệnh này.

## Danh mục khu vực

Database ban đầu chưa có địa giới. Đã thêm snapshot offline và AdministrativeAreaSeeder:

```sh
php artisan db:seed --class=AdministrativeAreaSeeder
```

Snapshot ngày 18/10/2026 lấy từ [Province Open API v2](https://provinces.open-api.vn/), gồm 34 tỉnh/thành và 3321 phường/xã sau sáp nhập. API nguồn mô tả v2 là danh mục sau tháng 07/2025; danh mục/cơ cấu được đối chiếu với [thông tin danh mục trên Báo Chính phủ](https://baochinhphu.vn/ma-so-cac-don-vi-hanh-chinh.html).

Nguồn JSON: `database/data/vietnam-administrative-areas-v2.json`, có URL nguồn và ngày lấy. Seeder chạy lại được bằng upsert theo code, không xóa dữ liệu/quan hệ đang có, không phụ thuộc gọi mạng khi chạy. Cấp quận/huyện hiển thị trên FE khi có dữ liệu lịch sử tương ứng. Seeder đã chạy trên database dự án.

## Đổi mật khẩu và OTP

| Endpoint | Payload chính |
| --- | --- |
| POST /api/auth/change-password | current_password, password, password_confirmation; Bearer token |
| POST /api/auth/forgot-password | identifier: email hoặc SĐT |
| POST /api/auth/reset-password | identifier, otp, password, password_confirmation |

Các alias /api/change-password, /api/forgot-password, /api/reset-password cũng được giữ.

- Đổi mật khẩu kiểm tra mật khẩu hiện tại, tối thiểu 8/tối đa 128 ký tự, xác nhận khớp và khác mật khẩu cũ. Giữ token hiện tại, thu hồi các token khác và vô hiệu hóa mã reset đang tồn tại.
- Mã OTP 6 chữ số, chỉ lưu Hash; hết hạn sau 15 phút; 5 lần thử sai sẽ vô hiệu hóa. Lần thử sai được commit trước khi trả 422 để không vô tình rollback bộ đếm.
- Mã mới vô hiệu hóa các mã reset trước của cùng tài khoản. Reset khóa bản ghi trong transaction, đánh dấu đã dùng và thu hồi toàn bộ token Sanctum. Mã đã dùng không thể dùng lại.
- Forgot chỉ xử lý tài khoản active, chưa xóa mềm. Response chung không tiết lộ tài khoản có tồn tại trong môi trường production; không cho tài khoản blocked reset để bỏ qua khóa.
- Giới hạn gửi: 1 lần/phút và 5 lần/giờ trên identifier chuẩn hóa; thêm giới hạn 5 lần/phút trên IP. Giới hạn kiểm tra: 10 lần/phút trên IP + identifier. HTTP 429 được hiển thị rõ trên FE.
- Sửa sai khác trong PLAN theo schema gốc: dùng attempt_count, used_at, purpose=reset_password và channel=email/sms. Không tạo cột attempts/verified_at mới.

FE có modal Quên mật khẩu hai bước và form đổi mật khẩu trong `/profile`; xử lý lỗi 422 theo field, 429, lỗi mạng và trạng thái đang gửi.

## Gửi mã ở local và production

Ở `APP_ENV=local` hoặc `testing`, PASSWORD_RESET_DEBUG=true trả debug_otp để kiểm thử và FE hiển thị rõ đây là mã local/test. Chế độ này không gửi email/SMS ra ngoài. APP_DEBUG hoặc PASSWORD_RESET_DEBUG **không** thể bật debug_otp khi môi trường là production.

Để gửi email thực: cấu hình MAIL_MAILER=smtp và các biến MAIL_HOST/MAIL_PORT/MAIL_USERNAME/MAIL_PASSWORD/MAIL_ENCRYPTION/MAIL_FROM_ADDRESS theo nhà cung cấp. Email dùng `App\Mail\PasswordResetCode` và template tiếng Việt.

Để gửi SMS thực: cấu hình PASSWORD_RESET_SMS_URL và PASSWORD_RESET_SMS_TOKEN cho gateway HTTPS của ứng dụng. Adapter gửi JSON `{to, message}` với Authorization Bearer; nếu nhà cung cấp yêu cầu cấu trúc khác, sửa `PasswordResetDelivery::send` cho API đó. Hiện dự án chưa có cấu hình nhà cung cấp SMS. Ở production, kênh chưa cấu hình trả 503 rõ ràng; mã không được giả lập là đã gửi. Lỗi gửi mã vô hiệu hóa mã vừa tạo.

## Kiểm thử

```sh
php artisan test --filter=Phase1CompletionTest
php artisan test --filter="Phase1CompletionTest|StudentAuthTest"
```

Phase1CompletionTest bao phủ: đăng ký teacher/center/slug, rollback, validation/aliases; đọc/sửa hồ sơ, phân quyền, cây địa giới và upload avatar; đổi mật khẩu; reset OTP, hash, hết hạn, dùng lại, số lần thử, resend limit; trường hợp blocked/không tồn tại; không lộ OTP ở production; delivery email/SMS qua Mail::fake/Http::fake; seed địa giới chạy lại.

Tests dùng database MySQL tên ngẫu nhiên `edumatch_feature_test_*`, giao dịch riêng cho từng test và xóa database khi kết thúc. Không dùng RefreshDatabase trên database ứng dụng.

FE:

```sh
npm run test:auth
npm run build
```

15 tests FE kiểm tra payload 3 role, validation, ánh xạ lỗi, profile normalization, các dịch vụ profile/area/avatar/password và xử lý token/lỗi thật. Không có mock fallback trong luồng sản phẩm.

Kết quả ngày 18/10/2026: 25 tests BE / 417 assertions (gồm Phase1CompletionTest và StudentAuthTest) đạt; 15 tests FE đạt, lint và build thành công. Trình duyệt đã xác nhận: hồ sơ lưu tên/bio/tỉnh/phường; tải lại không mất dữ liệu; upload WebP qua multipart và ảnh tải được từ public storage; mở tab đổi mật khẩu; modal quên mật khẩu nhận phản hồi API và chuyển sang bước OTP local. Database/server/tab kiểm thử và ảnh upload thử đã được dọn. Ảnh kiểm chứng giao diện lưu ở `D:\Production\Edumatchv1\output\qa\phase1-profile-saved.png`.

Kiểm tra trình duyệt cũng phát hiện và sửa lệch ngày sinh do serialize ngày thành UTC: StudentProfile và TeacherProfile trả date_of_birth theo YYYY-MM-DD; tests kiểm tra ngày sinh nguyên vẹn sau round-trip. Không thay đổi ngày sinh đã lưu trong database.
