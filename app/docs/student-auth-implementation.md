# Đăng ký và đăng nhập học viên

Backend: `C:\laragon\www\admin_edumatchv1`.
Frontend: `D:\Production\Edumatchv1`.

## API

| Method | Endpoint | Chức năng |
| --- | --- | --- |
| POST | /api/auth/register | Tạo tài khoản học viên và hồ sơ trong một transaction |
| POST | /api/auth/login | Đăng nhập bằng email/SĐT |
| GET | /api/auth/me, /api/auth/user | Lấy user, student_profile, roles và permissions |
| POST | /api/auth/logout | Thu hồi bearer token hiện tại |
| POST | /api/auth/refresh | Thay token hiện tại bằng token mới |

Các endpoint `/api/register`, `/api/login`, `/api/user`, `/api/me`, `/api/logout`, `/api/refresh` vẫn hoạt động tương ứng.

Payload đăng ký:

```json
{
  "name": "Nguyễn Văn A",
  "email": "nguyenvana@gmail.com",
  "phone": "0981234567",
  "date_of_birth": "2002-05-15",
  "password": "Admin@123",
  "password_confirmation": "Admin@123",
  "role": "student"
}
```

- Hỗ trợ aliases `full_name`/`fullName` cho name; `dateOfBirth`/`dob` cho date_of_birth.
- Email được trim, chuyển chữ thường, kiểm tra RFC/DNS và unique. Domain email cần có DNS hợp lệ; không gửi thư xác minh trong tính năng này.
- SĐT di động VN đầu 03/05/07/08/09 được chuẩn hóa thành +84. Kiểm tra trùng và đăng nhập hỗ trợ cả dữ liệu cũ lưu đầu 0. Cả tài khoản xóa mềm vẫn giữ unique email/phone.
- Name 2–150 ký tự, email tối đa 191, password 8–128 và phải xác nhận, ngày sinh bắt buộc dạng YYYY-MM-DD và không trong tương lai.
- Endpoint này chỉ nhận role student hoặc bỏ trống. Teacher/center/super_admin trả lỗi 422, tránh việc tài khoản bị gán sai role. Chức năng đăng ký hồ sơ giáo viên/trung tâm nằm ngoài kế hoạch học viên này.
- User/profile/role/token được tạo trong cùng transaction. Không lấy status, balance hoặc quyền từ payload.
- Response thành công có `user`, `token`, `access_token`, `token_type`; hai trường token có cùng giá trị.
- Login nhận `email`, `phone`, hoặc `identifier`, cùng `password`; `remember` là trường boolean tương thích FE, không thay đổi thời hạn token Sanctum mặc định. FE tiếp tục lưu phiên trong localStorage.
- Mật khẩu sai hoặc tài khoản không tồn tại: 401; tài khoản blocked: 403; dữ liệu sai/trùng: 422 với `errors` theo tên field.
- `/me` và refresh cũng chặn tài khoản blocked. Logout vẫn cho phép thu hồi token của tài khoản bị khóa.

## Frontend

Giữ giao diện hiện có và thêm tích hợp API thật:

- Base URL ưu tiên VITE_API_BASE_URL, sau đó VITE_API_URL, mặc định http://127.0.0.1:8000/api.
- Đăng ký gửi password_confirmation và date_of_birth; lỗi Laravel hiển thị dưới đúng field và focus field lỗi đầu tiên.
- Thành công lưu token/user trả về từ BE và chuyển đến /chao-mung-hoc-vien.
- Đăng nhập gửi email hoặc phone theo nội dung người dùng nhập; loại bỏ tài khoản/mật khẩu demo điền sẵn.
- Loại bỏ toàn bộ mock fallback trong authService/AuthContext và cơ chế kiểm tra trùng tài khoản qua localStorage.
- Tải lại trang gọi /auth/me để xác thực token trước khi mở trang được bảo vệ. API không kết nối/không xác thực thì không tạo phiên giả.
- Lỗi 401 từ phiên đang dùng xóa state/localStorage; lỗi login không xóa một phiên khác đang có. Logout luôn dọn token/user tại thiết bị; API thành công đồng thời thu hồi token máy chủ.
- Birthday từ student_profile được ánh xạ thêm sang dob để các màn hình hiện có tiếp tục đọc được.

## Seeder

```sh
php artisan db:seed --class=RoleAndPermissionSeeder
```

Seeder chạy lại được, tạo roles student/teacher/center/super_admin và giữ admin/user cho code cũ; cấp quyền admin cho super_admin/admin. Bổ sung role phù hợp cho tài khoản đang có mà không xóa role/quyền tùy chỉnh. Không tạo tài khoản mật khẩu mặc định. Khi triển khai lên môi trường khác, chạy migrations trước seeder nếu schema v2 chưa có.

## Kiểm thử

BE:

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/StudentAuthTest.php
```

10 tests bao phủ đăng ký/hồ sơ/role/token, dữ liệu sai, trùng email/SĐT, aliases và route cũ, login hai loại định danh, blocked/xóa mềm, logout/refresh, rollback khi tạo hồ sơ lỗi, seeder chạy nhiều lần. Tests tạo database MySQL tạm có prefix edumatch_auth_test_ và xóa sau khi hoàn tất; không sửa database ứng dụng.

FE:

```sh
npm run test:auth
npm run build
```

9 tests kiểm tra payload, validation, ánh xạ lỗi, nhận token, khôi phục hồ sơ, không nuốt lỗi auth/network và dọn localStorage khi logout. Các tests FE dùng Axios adapter giả lập phản hồi; kiểm thử tích hợp thực được thực hiện riêng trên trình duyệt với API/database tạm.

Kết quả ngày 18/10/2026: BE đạt 10 tests / 138 assertions, FE đạt 9 tests, ESLint các file thay đổi đạt và production build thành công. Trình duyệt đã chạy qua đăng ký → chào mừng → tải lại/khôi phục phiên → logout → mật khẩu sai → login bằng SĐT → logout → login bằng email viết hoa → logout. Database, server và tab kiểm thử tạm đã được dọn; seeder role đã chạy trên database dự án.
