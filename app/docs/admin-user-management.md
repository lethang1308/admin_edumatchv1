# Quản lý tài khoản EduMatch

Trang `/admin/users` dùng dữ liệu `users.name`, `avatar_path`, `email`, `phone`, `role`, `status` và các bảng hồ sơ. Danh sách có tìm kiếm, lọc vai trò/trạng thái và phân trang 15 tài khoản; không hiển thị số dư hoặc các trường giới tính của base cũ.

Trang chi tiết `/admin/users/{id}` hiển thị thông tin xác thực/liên hệ, ngày đăng ký/đăng nhập, hồ sơ theo vai trò, khu vực, môn dạy, bằng cấp, chi nhánh và lịch sử xét duyệt hồ sơ. Mật khẩu, token và đường dẫn tài liệu xác minh riêng tư không được đưa vào trang này; xem tài liệu qua trung tâm xác minh có kiểm tra quyền.

Admin và Super Admin được quản lý tài khoản. Form thêm/sửa lưu đúng hồ sơ và đồng bộ `users.role` với vai trò Spatie. Khi khóa hoặc đổi mật khẩu, các token API bị thu hồi. Thay email/số điện thoại sẽ xóa mốc xác thực của thông tin liên hệ đó. Không thể tự xóa, tự khóa hoặc tự đổi vai trò quản trị đang đăng nhập.

## Xóa và đăng ký lại

`AccountDeletionService` xử lý trong transaction: thu hồi token API, xóa mã OTP/reset password liên quan, thay email cũ bằng địa chỉ ngẫu nhiên thuộc miền `.invalid`, bỏ số điện thoại, thay mật khẩu bằng giá trị ngẫu nhiên và xóa mềm tài khoản. Email giả đáp ứng ràng buộc `users_contact_check` của database; không dùng làm địa chỉ gửi thư. Các khóa ngoại và lịch sử hồ sơ được giữ lại.

Email và số điện thoại thật được giải phóng khỏi unique index. Đăng ký lại vẫn phải xác thực OTP email và tạo ID/hồ sơ mới, không phục hồi hay chuyển dữ liệu của tài khoản đã xóa. Không dùng `withoutTrashed()` cho validation unique vì chỉ bỏ qua validation vẫn có thể vi phạm unique index khi INSERT.

Migration `2026_10_22_000001_release_deleted_user_identifiers` xử lý các tài khoản đã xóa bằng cơ chế cũ. Migration không sửa tài khoản đang hoạt động; có thể gọi lại mà không đổi các tài khoản đã xử lý. `down()` không khôi phục thông tin đăng nhập đã loại bỏ.

Kiểm thử: `php artisan test --filter=AdminUsersTest`. Bộ kiểm thử dùng database MySQL riêng, không xóa tài khoản thật và không gửi email thật.
