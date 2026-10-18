# TÀI LIỆU KẾ HOẠCH TRIỂN KHAI HỆ THỐNG EDUMATCH (LARAVEL + REACT + MYSQL)

> **Dự án**: Nền tảng kết nối Học viên với Giáo viên & Trung tâm đào tạo (EduMatch)  
> **Phiên bản tài liệu**: 2.0  
> **Ngày lập**: 18/10/2026  
> **Kiến trúc**:
> - **Backend API & Admin**: Laravel 10 (Sanctum API cho Client + Blade Views cho Super Admin)
> - **Frontend Web**: React 19 + Vite 8 + Tailwind CSS v4 (`Edumatchv1`)
> - **Cơ sở dữ liệu**: MySQL 8.x (35 bảng theo thiết kế chuẩn hóa)
> - **Phạm vi 4 Roles**: Học sinh (`student`), Giáo viên (`teacher`), Trung tâm (`center`), Quản trị viên tối cao (`super_admin`).

---

## MỤC LỤC
1. [TỔNG QUAN KIẾN TRÚC & NGUYÊN TẮC VẬN HÀNH](#1-tổng-quan-kiến-trúc--nguyên-tắc-vận-hành)
2. [GIAI ĐOẠN 1: NỀN TẢNG CƠ SỞ DỮ LIỆU & AUTHENTICATION ĐA ROLE](#giai-đoạn-1-nền-tảng-cơ-sở-dữ-liệu--authentication-đa-role)
3. [GIAI ĐOẠN 2: XÁC MINH DANH TÍNH & QUẢN LÝ KHÓA HỌC (KIỂM DUYỆT BÀI ĐĂNG)](#giai-đoạn-2-xác-minh-danh-tính--quản-lý-khóa-học-kiểm-duyệt-bài-đăng)
4. [GIAI ĐOẠN 3: TÌM KIẾM, KHÁM PHÁ & VẬN HÀNH LEAD (YÊU CẦU TƯ VẤN)](#giai-đoạn-3-tìm-kiếm-khám-phá--vận-hành-lead-yêu-cầu-tư-vấn)
5. [GIAI ĐOẠN 4: ĐÁNH GIÁ CÓ KIỂM CHỨNG & BẢNG TIN CỘNG ĐỒNG (FEED)](#giai-đoạn-4-đánh-giá-có-kiểm-chứng--bảng-tin-cộng-đồng-feed)
6. [GIAI ĐOẠN 5: GIAO TIẾP THỜI GIAN THỰC (CHAT IN-APP) & HỆ THỐNG THÔNG BÁO](#giai-đoạn-5-giao-tiếp-thời-gian-thực-chat-in-app--hệ-thống-thông-báo)
7. [GIAI ĐOẠN 6: SUPER ADMIN QUẢN TRỊ (BLADE), BÁO CÁO VI PHẠM & AUDIT LOGS](#giai-đoạn-6-super-admin-quản-trị-blade-báo-cáo-vi-phạm--audit-logs)
8. [BẢNG TỔNG HỢP API & ROUTE MAPPING TOÀN HỆ THỐNG](#8-bảng-tổng-hợp-api--route-mapping-toàn-hệ-thống)

---

## 1. TỔNG QUAN KIẾN TRÚC & NGUYÊN TẮC VẬN HÀNH

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        NGƯỜI DÙNG CUỐI (CLIENT)                        │
│   Học sinh (Student)   │   Giáo viên (Teacher)   │ Trung tâm (Center)  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ (RESTful API / JSON + Sanctum Token)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                     REACT 19 FRONTEND (Edumatchv1)                     │
│    Pages: Home, Search, CourseDetail, CreateCourse, TeacherProfile,    │
│           Feed, Messages, Contact, CenterSupport, Auth (Login/Reg)     │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Axios Client (Bearer Token)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        LARAVEL 10 BACKEND CORE                         │
│  ┌───────────────────────────────┐  ┌────────────────────────────────┐ │
│  │     API Controllers (v1)      │  │    Super Admin (Blade Views)   │ │
│  │   Auth, Profile, Post, Lead,  │  │   Dashboard, Moderation, User, │ │
│  │   Review, Feed, Chat, Notice  │  │   Verification, Reports, Logs  │ │
│  └───────────────┬───────────────┘  └────────────────┬───────────────┘ │
│                  │ (Eloquent ORM & Services)         │                 │
│                  └─────────────────┬─────────────────┘                 │
│                                    ▼                                   │
│                           MYSQL 8.x DATABASE                           │
│                      (35 Tables - UTF8MB4/InnoDB)                      │
└────────────────────────────────────────────────────────────────────────┘
```

### Nguyên tắc nghiệp vụ then chốt (Core Rules):
1. **Kiểm duyệt bắt buộc (Content Moderation)**: Mọi bài đăng khóa học (`posts`) của Giáo viên & Trung tâm bắt buộc trải qua luồng: `draft` ➔ `pending` (gửi duyệt) ➔ Super Admin duyệt thì mới chuyển sang `published` để hiển thị ra ngoài website.
2. **Mô hình Lead kín (Lead Generation)**: Học viên quan tâm khóa học sẽ gửi Yêu cầu tư vấn (`consultation_requests`). Người dạy nhận lead, chăm sóc và cập nhật tiến trình: `new` ➔ `contacted` ➔ `consulting` ➔ `converted` (thành công) / `failed` (thất bại).
3. **Đánh giá chống Spam (Anti-Spam Review)**: Học viên **chỉ được phép đánh giá và chấm sao** cho khóa học/người dạy khi đã có 1 Lead đạt trạng thái `converted` với khóa học đó (ràng buộc 1-1 qua `reviews.request_id` UNIQUE).
4. **Phân quyền nghiêm ngặt**:
   - `student`, `teacher`, `center`: Tương tác qua React Frontend bằng REST API (Laravel Sanctum).
   - `super_admin`: Quản trị toàn quyền thông qua giao diện máy chủ nội bộ (Laravel Blade + Session Auth).

---

## GIAI ĐOẠN 1: NỀN TẢNG CƠ SỞ DỮ LIỆU & AUTHENTICATION ĐA ROLE

### 1. Mục tiêu giai đoạn
- Thiết lập hoàn chỉnh cấu trúc Database 35 bảng, thiết lập Foreign Keys, Constraints và Seeders ban đầu.
- Xây dựng hệ thống Đăng ký / Đăng nhập / Đổi mật khẩu hỗ trợ đầy đủ 4 Role.
- Cấp phát API Token (Sanctum) cho React Frontend và Web Session cho Admin Blade.

### 2. Các bảng Database liên quan
- `users`
- `student_profiles`
- `teacher_profiles`
- `centers`
- `administrative_areas`
- `auth_verification_tokens`

### 3. Công việc triển khai chi tiết
| STT | Nhiệm vụ kỹ thuật | Chi tiết thực hiện | Kết quả cần đạt |
| :---: | :--- | :--- | :--- |
| **1.1** | **Viết Migration & Model** | Tạo các file Migration chuẩn cho `users`, `administrative_areas`, `student_profiles`, `teacher_profiles`, `centers`. Khai báo quan hệ Eloquent (1-1 `hasOne`, `belongsTo`). | `php artisan migrate` thành công không lỗi cú pháp hay ràng buộc khóa ngoại. |
| **1.2** | **Import Dữ liệu Địa giới (`administrative_areas`)** | Viết Seeder nạp danh sách Tỉnh/Thành phố, Quận/Huyện, Phường/Xã Việt Nam theo chuẩn mã hành chính. | Có API `GET /api/v1/areas` trả về cây địa giới phục vụ `AdministrativePicker.jsx` của FE. |
| **1.3** | **API Đăng ký đa Role (`/api/v1/auth/register`)** | - Tiếp nhận `role` (`student`, `teacher`, `center`).<br>- Validate số điện thoại (E.164), email duy nhất.<br>- Transaction tạo `users` và profile tương ứng (`student_profiles`, `teacher_profiles`, hoặc `centers`). | Đăng ký thành công trả về User resource + Sanctum plainTextToken. |
| **1.4** | **API Đăng nhập (`/api/v1/auth/login`)** | Cho phép đăng nhập linh hoạt bằng **Số điện thoại** hoặc **Email** + Password. Kiểm tra trạng thái tài khoản (`status = active`). | Trả về thông tin user đầy đủ theo role + Token. Chặn nếu tài khoản bị khóa (`blocked`). |
| **1.5** | **Quên / Đặt lại mật khẩu** | Tạo cơ chế sinh token OTP/Link qua bảng `auth_verification_tokens` (mã hóa hash, giới hạn thời gian 15 phút, tối đa 5 lần nhập sai). | Luồng gửi mã xác thực và đổi mật khẩu mới an toàn. |
| **1.6** | **Trang Đăng nhập Quản trị (Admin Blade)** | Tạo route `/admin/login`, giao diện Blade đăng nhập riêng. Áp dụng middleware kiểm tra `role === 'super_admin'`. | Chỉ super_admin mới đăng nhập được vào trang quản trị Laravel Blade. |

---

## GIAI ĐOẠN 2: XÁC MINH DANH TÍNH & QUẢN LÝ KHÓA HỌC (KIỂM DUYỆT BÀI ĐĂNG)

### 1. Mục tiêu giai đoạn
- Cho phép Giáo viên & Trung tâm xây dựng hồ sơ, tải tài liệu chứng minh năng lực để Admin thẩm định.
- Cho phép Giáo viên & Trung tâm tạo khóa học/bài đăng và thực hiện quy trình gửi duyệt bài.
- Xây dựng màn hình kiểm duyệt hồ sơ và duyệt bài đăng bên giao diện Laravel Blade của Admin.

### 2. Các bảng Database liên quan
- `teacher_qualifications`, `verification_requests`, `verification_documents`
- `subjects`, `education_levels`, `teacher_subjects`
- `posts`, `post_levels`, `post_images`, `post_moderation_logs`
- `center_branches`, `course_batches`, `course_schedules`

### 3. Công việc triển khai chi tiết
| STT | Nhiệm vụ kỹ thuật | Chi tiết thực hiện | Giao diện kết nối |
| :---: | :--- | :--- | :--- |
| **2.1** | **Quản lý Danh mục Môn học & Cấp lớp** | - Tạo bảng `subjects`, `education_levels`.<br>- Admin Blade: CRUD Môn học (tên, slug, icon, thứ tự, bật/tắt).<br>- API `GET /api/v1/subjects` cho dropdown trên FE. | Admin Blade: `/admin/subjects`<br>React FE: Bộ chọn môn học ở Home & CreateCourse. |
| **2.2** | **Nộp hồ sơ xác minh Giáo viên / Trung tâm** | - Giáo viên nộp: Bằng cấp, chứng chỉ, CCCD.<br>- Trung tâm nộp: Giấy phép ĐKKD, Giấy phép hoạt động giáo dục.<br>- Lưu vào `verification_requests` và `verification_documents`. | React FE: `TeacherProfilePage.jsx` (modal nộp hồ sơ). |
| **2.3** | **Admin Blade: Xét duyệt Hồ sơ Giáo viên / Trung tâm** | Màn hình Admin hiển thị danh sách hồ sơ `pending`, xem trực tiếp ảnh/PDF bằng cấp. Nút bấm: **Phê duyệt (`verified`)** hoặc **Từ chối (`rejected`) + nhập lý do**. | Admin Blade: `/admin/verifications` |
| **2.4** | **Đăng bài & Quản lý Khóa học (`posts`)** | - API `POST /api/v1/courses` tạo bài đăng (tiêu đề, môn, học phí, hình thức: online/offline/hybrid, lộ trình, lịch học).<br>- Upload nhiều ảnh (`post_images`).<br>- Mặc định lưu trạng thái: `draft` hoặc `pending`. | React FE: `CreateCoursePage.jsx` |
| **2.5** | **Admin Blade: Kiểm duyệt Khóa học (Moderation Center)** | - Danh sách bài chờ duyệt.<br>- Xem preview bài đăng đầy đủ y hệt ngoài FE.<br>- Thao tác: **Duyệt (`published`)**, **Từ chối (`rejected` + lý do)**, hoặc **Hạ bài (`hidden`)**.<br>- Tự động ghi vết vào `post_moderation_logs`. | Admin Blade: `/admin/posts/moderation` |
| **2.6** | **Mở rộng Đợt học & Chi nhánh (Dành cho Trung tâm)** | API quản lý cơ sở đào tạo (`center_branches`) và ca học mở đợt (`course_batches`, `course_schedules`) gắn giáo viên phụ trách. | React FE: Form cấu hình ca học trung tâm. |

---

## GIAI ĐOẠN 3: TÌM KIẾM, KHÁM PHÁ & VẬN HÀNH LEAD (YÊU CẦU TƯ VẤN)

### 1. Mục tiêu giai đoạn
- Cung cấp API tìm kiếm, lọc khóa học, xem chi tiết và xem hồ sơ công khai của người dạy cho Học viên.
- Xây dựng trọn vẹn vòng đời của Lead: Học viên gửi form yêu cầu tư vấn ➔ Người dạy tiếp nhận ➔ Chăm sóc ➔ Chốt kết quả.

### 2. Các bảng Database liên quan
- `consultation_requests`
- `request_status_logs`
- `center_staff`
- `search_logs`
- `notifications`

### 3. Công việc triển khai chi tiết
| STT | Nhiệm vụ kỹ thuật | Chi tiết thực hiện | Luồng dữ liệu |
| :---: | :--- | :--- | :--- |
| **3.1** | **API Khám phá & Lọc khóa học (`/api/v1/courses`)** | - Query từ bảng `posts` với điều kiện: `moderation_status = 'published'` và `deleted_at IS NULL`.<br>- Lọc đa tiêu chí: Môn học (`subject_id`), Cấp lớp (`level_id`), Hình thức (`teaching_mode`), Địa bàn (`province_id`), Khoảng giá (`price_min`, `price_max`).<br>- Sắp xếp: Mới nhất, giá tăng/giảm, đánh giá cao nhất. | React FE: `Home.jsx` (bộ lọc tìm gia sư). |
| **3.2** | **Ghi nhận Lịch sử Tìm kiếm (`search_logs`)** | Mỗi lượt gọi API tìm kiếm có từ khóa hoặc bộ lọc, hệ thống tự động ghi 1 dòng vào `search_logs` (từ khóa, môn, cấp lớp, số kết quả trả về) để phục vụ Analytics. | Chạy qua Middleware/Job bất đồng bộ (Queue). |
| **3.3** | **API Chi tiết Khóa học & Hồ sơ Người dạy** | - `GET /api/v1/courses/{slug}`: Thông tin chi tiết, ảnh, các ca học, thông tin giáo viên/trung tâm.<br>- `GET /api/v1/teachers/{id}` hoặc `/api/v1/centers/{slug}`: Profile công khai, số năm kinh nghiệm, các môn dạy, danh sách lớp đang mở, lượt trust. | React FE: `CourseDetailPage.jsx`, `TeacherProfilePage.jsx`. |
| **3.4** | **Học viên Gửi Yêu cầu Tư vấn (`POST /api/v1/consultations`)** | Học viên điền form: Tên liên hệ, SĐT, Email, Nhu cầu/lời nhắn gắn với `post_id`.<br>- Tạo bản ghi `consultation_requests` với trạng thái `new`.<br>- Bắn thông báo (`notifications`) tới chủ bài đăng. | React FE: Modal đăng ký học tại trang chi tiết. |
| **3.5** | **Quản lý Lead phía Giáo viên / Trung tâm** | - `GET /api/v1/leads`: Xem danh sách học viên đăng ký, lọc theo trạng thái.<br>- `PATCH /api/v1/leads/{id}/status`: Cập nhật tiến trình: `new` ➔ `contacted` (đã gọi điện) ➔ `consulting` (đang hẹn lịch) ➔ `converted` (chốt thành công) hoặc `failed` (thất bại) + ghi chú nội bộ.<br>- Phân công lead cho nhân viên tư vấn (`assigned_staff_id`).<br>- Tự động ghi vết thay đổi vào `request_status_logs`. | React FE: Trang quản lý học viên tiềm năng (Lead Management Dashboard). |
| **3.6** | **Theo dõi Lead phía Học viên** | API `GET /api/v1/my-consultations`: Học viên xem lại các khóa học mình đã gửi yêu cầu và trạng thái xử lý hiện tại. Cho phép bấm Hủy (`cancelled`) nếu trạng thái vẫn là `new`. | React FE: Màn hình lịch sử học tập cá nhân. |

---

## GIAI ĐOẠN 4: ĐÁNH GIÁ CÓ KIỂM CHỨNG & BẢNG TIN CỘNG ĐỒNG (FEED)

### 1. Mục tiêu giai đoạn
- Thiết lập hệ thống Đánh giá - Chấm sao minh bạch, ngăn chặn triệt để review ảo thông qua xác thực Lead.
- Xây dựng Mạng xã hội / Bảng tin thu nhỏ (Newsfeed) để Giáo viên và Trung tâm chia sẻ bài viết, video, tăng tương tác với học sinh.

### 2. Các bảng Database liên quan
- `reviews`
- `post_favorites`, `profile_interactions`
- `feed_posts`, `feed_media`, `feed_likes`, `feed_comments`

### 3. Công việc triển khai chi tiết
| STT | Nhiệm vụ kỹ thuật | Chi tiết thực hiện | Quy tắc nghiệp vụ |
| :---: | :--- | :--- | :--- |
| **4.1** | **API Đánh giá Khóa học (`POST /api/v1/reviews`)** | - Học viên gửi `request_id`, số sao (1-5), nội dung nhận xét.<br>- **Validation bắt buộc**: Bản ghi `consultation_requests` phải có `student_user_id === auth()->id()` và `status === 'converted'`.<br>- Cột `reviews.request_id` là UNIQUE (ngăn 1 lead review nhiều lần). | Chỉ người học thật mới được đánh giá. |
| **4.2** | **Tự động Cập nhật Điểm Đánh giá (Cache Rating)** | Sau khi có review mới được duyệt (`published`), Event/Listener tự động tính toán lại và cập nhật `rating_avg` và `review_count` trong bảng `teacher_profiles` hoặc `centers`. | Tối ưu tốc độ query trang chủ (không cần tính AVG realtime). |
| **4.3** | **Lưu Yêu thích & Thả tim Tin tưởng (Trust)** | - `POST /api/v1/posts/{id}/favorite`: Lưu/bỏ lưu khóa học.<br>- `POST /api/v1/profiles/{id}/trust`: Thả tim tin tưởng hồ sơ giáo viên/trung tâm. Cập nhật cache `trust_count`. | React FE: Nút Heart/Bookmark trên Card và Header profile. |
| **4.4** | **Bảng tin Cộng đồng: Đăng bài (`feed_posts`)** | - Giáo viên/Trung tâm đăng bài viết, gắn tag trung tâm nếu có.<br>- Hỗ trợ upload nhiều ảnh/video vào bảng `feed_media`.<br>- Quản lý trạng thái bài đăng (`published`). | React FE: `FeedPage.jsx` (khung soạn thảo chia sẻ). |
| **4.5** | **Tương tác Bảng tin: Like & Nested Comments** | - `POST /api/v1/feed/{id}/like`: Toggle thả tim, lưu `feed_likes`, cập nhật `like_count`.<br>- `POST /api/v1/feed/{id}/comments`: Bình luận và trả lời bình luận (`parent_id`), cập nhật `comment_count`.<br>- Modal danh sách người thích (`LikesModal.jsx`). | React FE: `FeedPage.jsx`, `LikesModal.jsx`. |

---

## GIAI ĐOẠN 5: GIAO TIẾP THỜI GIAN THỰC (CHAT IN-APP) & HỆ THỐNG THÔNG BÁO

### 1. Mục tiêu giai đoạn
- Cung cấp tính năng Chat trực tiếp giữa Học viên với Giáo viên / Trung tâm để trao đổi lịch học và nhu cầu chi tiết.
- Hệ thống thông báo in-app thời gian thực về trạng thái bài đăng, lead mới, đánh giá mới.

### 2. Các bảng Database liên quan
- `chat_conversations`, `chat_participants`, `chat_messages`, `chat_attachments`
- `notifications`

### 3. Công việc triển khai chi tiết
| STT | Nhiệm vụ kỹ thuật | Chi tiết thực hiện | Công nghệ áp dụng |
| :---: | :--- | :--- | :--- |
| **5.1** | **Khởi tạo & Danh sách Hội thoại Chat** | - `POST /api/v1/chat/conversations`: Mở cuộc trò chuyện (truyền ID đối phương và `post_id` nếu có).<br>- `GET /api/v1/chat/conversations`: Lấy danh sách cuộc trò chuyện kèm tin nhắn mới nhất và số tin chưa đọc. | React FE: `MessagesPage.jsx` & `FloatingChat.jsx`. |
| **5.2** | **Gửi & Nhận Tin nhắn (`chat_messages`)** | - `GET /api/v1/chat/conversations/{id}/messages`: Lấy lịch sử chat (phân trang paginate).<br>- `POST /api/v1/chat/conversations/{id}/messages`: Gửi tin nhắn văn bản hoặc tệp đính kèm (`chat_attachments`).<br>- Hỗ trợ loại tin nhắn: `text`, `image`, `file`, `system`. | Lưu trữ tin nhắn chuẩn hóa, cập nhật `last_message_at`. |
| **5.3** | **Realtime Chat với Pusher / Laravel Echo** | Khi gửi tin nhắn thành công, broadcast event `MessageSent` qua Pusher channel private `chat.conversation.{id}`. React FE lắng nghe qua Laravel Echo để hiển thị tin nhắn ngay lập tức. | Laravel Broadcast + Pusher PHP Server. |
| **5.4** | **Hệ thống Thông báo In-App (`notifications`)** | - Tạo Notification Events trong Laravel: `NewLeadReceived`, `PostApproved`, `PostRejected`, `NewReviewReceived`.<br>- Lưu vào bảng `notifications` (`user_id`, `type`, `title`, `content`, `data`).<br>- API lấy thông báo, đánh dấu đã đọc (`read_at`). | React FE: Chuông thông báo trên Header. |

---

## GIAI ĐOẠN 6: SUPER ADMIN QUẢN TRỊ (BLADE), BÁO CÁO VI PHẠM & AUDIT LOGS

### 1. Mục tiêu giai đoạn
- Xây dựng trọn vẹn bộ khung giao diện quản trị Super Admin trên nền tảng **Laravel Blade**.
- Dashboard thống kê chuyên sâu, quản lý nội dung trang chủ (Banners, CMS), xử lý vi phạm và lưu nhật ký quản trị.

### 2. Các bảng Database liên quan
- `reports`
- `banners`, `homepage_features`, `system_settings`
- `admin_logs`, `user_activity_logs`

### 3. Công việc triển khai chi tiết
| STT | Nhiệm vụ kỹ thuật | Chi tiết thực hiện trên Laravel Blade | Ý nghĩa vận hành |
| :---: | :--- | :--- | :--- |
| **6.1** | **Dashboard Thống kê & Báo cáo Số liệu** | - Thẻ thống kê tổng: User mới (tách 3 role), Bài chờ duyệt, Hồ sơ chờ duyệt, Lead mới.<br>- Tỷ lệ chuyển đổi lead (`converted / total`).<br>- Biểu đồ cột/đường xu hướng môn học được tìm kiếm nhiều nhất (Query thống kê từ `search_logs`).<br>- Xuất file Excel báo cáo bằng thư viện `maatwebsite/excel`. | Route: `/admin/dashboard`<br>Cung cấp bức tranh toàn cảnh cho ban quản trị. |
| **6.2** | **Quản trị Tài khoản & Phân quyền User** | - Danh sách tài khoản, tìm kiếm theo tên/SĐT/email, lọc role (`student`, `teacher`, `center`).<br>- Nút bấm Khóa tài khoản (`status = 'blocked'`) kèm ô nhập lý do (`blocked_reason`).<br>- Mở khóa tài khoản. Xem chi tiết lịch sử hoạt động của user từ `user_activity_logs`. | Route: `/admin/users` |
| **6.3** | **Xử lý Khiếu nại & Báo cáo Vi phạm (`reports`)** | - Tiếp nhận báo cáo quan hệ đa hình (`post`, `teacher`, `center`, `review`, `feed_post`).<br>- Màn hình xem bằng chứng tố cáo, link đối tượng bị báo cáo.<br>- Thao tác: Khóa đối tượng vi phạm, gửi thông báo cảnh cáo, đổi trạng thái báo cáo sang `resolved` hoặc `rejected`. | Route: `/admin/reports` |
| **6.4** | **Quản lý Nội dung Trang chủ (Banners & Featured)** | - CRUD Banner quảng cáo (`banners`): Upload ảnh banner, link CTA, ngày bắt đầu/kết thúc, thứ tự hiển thị.<br>- Ghim nổi bật (`homepage_features`): Chọn giáo viên tiêu biểu, trung tâm uy tín hoặc khóa học hot lên đầu trang chủ. | Route: `/admin/banners`<br>Route: `/admin/features` |
| **6.5** | **Nhật ký Quản trị (Admin Audit Logs)** | Tạo Middleware tự động bắt các thao tác nhạy cảm của Admin (Duyệt bài, từ chối hồ sơ, khóa tài khoản, xóa đánh giá) và ghi vào `admin_logs` (admin_id, action, target_type, target_id, old_values, new_values, ip_address). | Route: `/admin/logs`<br>Phục vụ bảo mật, chống lạm quyền và kiểm tra trách nhiệm. |
| **6.6** | **Cấu hình Hệ thống (`system_settings`)** | Màn hình cấu hình: Hotline hỗ trợ, email liên hệ, số ảnh upload tối đa, bật/tắt chế độ bảo trì. | Route: `/admin/settings` |

---

## 8. BẢNG TỔNG HỢP API & ROUTE MAPPING TOÀN HỆ THỐNG

### A. API Dành cho React Frontend (Client API - Prefix `/api/v1`)
| Phương thức | URI Endpoint | Mô tả chức năng | Quyền truy cập |
| :---: | :--- | :--- | :---: |
| `POST` | `/api/v1/auth/register` | Đăng ký tài khoản (Student / Teacher / Center) | Public |
| `POST` | `/api/v1/auth/login` | Đăng nhập lấy Bearer Token | Public |
| `GET` | `/api/v1/auth/me` | Lấy thông tin tài khoản đăng nhập hiện tại | Authenticated |
| `POST` | `/api/v1/auth/logout` | Đăng xuất, thu hồi Token | Authenticated |
| `GET` | `/api/v1/areas` | Lấy danh mục địa giới Tỉnh/Huyện/Xã | Public |
| `GET` | `/api/v1/subjects` | Lấy danh mục môn học & cấp lớp | Public |
| `GET` | `/api/v1/courses` | Tìm kiếm & lọc danh sách khóa học published | Public |
| `GET` | `/api/v1/courses/{slug}` | Xem chi tiết nội dung khóa học | Public |
| `POST` | `/api/v1/courses` | Tạo khóa học mới (Teacher / Center) | Teacher, Center |
| `PUT` | `/api/v1/courses/{id}` | Cập nhật bài đăng khóa học | Owner |
| `POST` | `/api/v1/courses/{id}/submit` | Gửi duyệt bài đăng (`draft` ➔ `pending`) | Owner |
| `GET` | `/api/v1/teachers/{id}` | Xem hồ sơ công khai giáo viên | Public |
| `GET` | `/api/v1/centers/{slug}` | Xem trang thương hiệu trung tâm | Public |
| `POST` | `/api/v1/verification/submit` | Nộp tài liệu xác minh giáo viên/trung tâm | Teacher, Center |
| `POST` | `/api/v1/consultations` | Học viên gửi yêu cầu tư vấn khóa học (Lead) | Authenticated |
| `GET` | `/api/v1/my-consultations` | Học viên theo dõi lịch sử yêu cầu tư vấn | Student |
| `GET` | `/api/v1/leads` | Người dạy xem danh sách học viên đăng ký | Teacher, Center |
| `PATCH`| `/api/v1/leads/{id}/status` | Người dạy cập nhật trạng thái xử lý lead | Owner |
| `POST` | `/api/v1/reviews` | Đánh giá khóa học (Bắt buộc lead converted) | Student |
| `POST` | `/api/v1/interactions/trust` | Bấm tin tưởng / Thả tim hồ sơ GV/TT | Authenticated |
| `GET` | `/api/v1/feed` | Lấy danh sách bài viết trên Bảng tin | Public |
| `POST` | `/api/v1/feed` | Đăng bài viết chia sẻ lên Bảng tin | Teacher, Center |
| `POST` | `/api/v1/feed/{id}/like` | Thả tim bài viết bảng tin | Authenticated |
| `POST` | `/api/v1/feed/{id}/comments`| Bình luận / Trả lời bình luận | Authenticated |
| `GET` | `/api/v1/chat/conversations`| Danh sách hội thoại chat | Authenticated |
| `POST` | `/api/v1/chat/messages` | Gửi tin nhắn văn bản hoặc file | Authenticated |
| `GET` | `/api/v1/notifications` | Lấy danh sách thông báo in-app | Authenticated |
| `POST` | `/api/v1/reports` | Gửi báo cáo vi phạm | Authenticated |

---

### B. Routes Dành cho Super Admin (Laravel Blade Views - Prefix `/admin`)
| Phương thức | URL Route | Tên View Blade tương ứng | Chức năng quản trị |
| :---: | :--- | :--- | :--- |
| `GET/POST`| `/admin/login` | `auth.login` | Đăng nhập tài khoản quản trị |
| `GET` | `/admin/dashboard` | `admin.dashboard.index` | Thống kê số liệu, biểu đồ xu hướng tìm kiếm |
| `GET` | `/admin/users` | `admin.users.index` | Danh sách tài khoản, lọc theo 4 role |
| `POST` | `/admin/users/{id}/toggle-status` | — (Redirect) | Khóa hoặc mở khóa tài khoản |
| `GET` | `/admin/verifications` | `admin.verifications.index` | Trung tâm duyệt bằng cấp, hồ sơ pháp lý |
| `POST` | `/admin/verifications/{id}/review` | — (Redirect) | Phê duyệt hoặc từ chối hồ sơ xác minh |
| `GET` | `/admin/posts/moderation` | `admin.posts.moderation` | Danh sách khóa học chờ duyệt |
| `GET` | `/admin/posts/{id}/preview` | `admin.posts.preview` | Xem trước nội dung khóa học |
| `POST` | `/admin/posts/{id}/approve` | — (Redirect) | Phê duyệt xuất bản khóa học |
| `POST` | `/admin/posts/{id}/reject` | — (Redirect) | Từ chối duyệt kèm lý do |
| `GET/POST`| `/admin/subjects` | `admin.subjects.index` | CRUD Danh mục môn học |
| `GET` | `/admin/leads` | `admin.leads.index` | Theo dõi toàn bộ lead & tỷ lệ chuyển đổi |
| `GET` | `/admin/reviews` | `admin.reviews.index` | Kiểm duyệt đánh giá, ẩn đánh giá vi phạm |
| `GET` | `/admin/reports` | `admin.reports.index` | Tiếp nhận và giải quyết khiếu nại |
| `GET/POST`| `/admin/banners` | `admin.banners.index` | Quản lý banner quảng cáo ngoài trang chủ |
| `GET` | `/admin/logs` | `admin.logs.index` | Xem nhật ký thao tác quản trị hệ thống |

---

## 9. CHECKLIST KIỂM THỬ KHI HOÀN THÀNH MỖI GIAI ĐOẠN

- [ ] **Giai đoạn 1**: Đăng ký được tài khoản với 3 role trên React FE, kiểm tra dữ liệu vào đúng bảng `users`, `student_profiles`, `teacher_profiles`, `centers`. Đăng nhập lấy được Bearer Token. Admin đăng nhập được vào Blade.
- [ ] **Giai đoạn 2**: Giáo viên tạo bài đăng khóa học chuyển trạng thái sang `pending`. Mở trang Admin Blade duyệt bài ➔ Bài chuyển sang `published` và hiển thị ra ngoài trang chủ React FE.
- [ ] **Giai đoạn 3**: Học viên tìm kiếm khóa học theo môn/khu vực ➔ Bấm gửi form yêu cầu tư vấn. Giáo viên đăng nhập nhận được thông báo lead mới, đổi trạng thái từ `new` ➔ `contacted` ➔ `converted`.
- [ ] **Giai đoạn 4**: Sau khi lead đạt `converted`, học viên vào lại khóa học để đánh giá 5 sao. Kiểm tra rating trung bình của giáo viên tự động cập nhật.
- [ ] **Giai đoạn 5**: Học viên và giáo viên gửi tin nhắn qua lại trong cửa sổ chat, tin nhắn hiển thị tức thì qua WebSocket/Pusher.
- [ ] **Giai đoạn 6**: Admin xem được biểu đồ thống kê các môn học được tìm kiếm nhiều nhất trên Dashboard, tải được file Excel báo cáo và kiểm tra được lịch sử thao tác trong Audit Logs.
