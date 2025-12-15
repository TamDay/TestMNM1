# Meeting Room Booking System - PHP

Hệ thống đặt phòng họp được xây dựng bằng PHP thuần và MySQL.

## Yêu cầu hệ thống

- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Apache/Nginx web server
- XAMPP/WAMP (cho môi trường phát triển)

## Cài đặt

### 1. Clone hoặc copy dự án

Copy thư mục `meeting-room-booking` vào thư mục `htdocs` (XAMPP) hoặc `www` (WAMP).

### 2. Tạo database

1. Mở phpMyAdmin (http://localhost/phpmyadmin)
2. Tạo database mới tên `meeting_room_booking`
3. Import file `database.sql` vào database vừa tạo

### 3. Cấu hình database

Mở file `config/database.php` và cập nhật thông tin kết nối nếu cần:

```php
private $host = 'localhost';
private $db_name = 'meeting_room_booking';
private $username = 'root';
private $password = '';
```

### 4. Chạy ứng dụng

Truy cập: http://localhost/meeting-room-booking

## Tài khoản demo

### Admin
- Username: `admin`
- Password: `admin123`

### User
- Username: `nguyenvana`
- Password: `admin123`

## Tính năng

### Người dùng
- ✅ Đăng ký/Đăng nhập
- ✅ Xem danh sách phòng họp
- ✅ Xem chi tiết phòng
- ✅ Đặt phòng họp
- ✅ Xem lịch sử đặt phòng
- ✅ Hủy đặt phòng (nếu đang chờ xác nhận)

### Admin
- ✅ Dashboard với thống kê
- ✅ Quản lý phòng họp (CRUD)
- ✅ Quản lý đặt phòng
- ✅ Quản lý người dùng
- ✅ Cập nhật trạng thái đặt phòng

## Cấu trúc thư mục

```
meeting-room-booking/
├── admin/              # Trang quản trị
├── assets/            # CSS, JS, images
├── config/            # Cấu hình database
├── includes/          # Header, footer, functions
├── uploads/           # Upload files
├── *.php             # Các trang chính
└── database.sql      # File SQL
```

## Công nghệ sử dụng

- **Backend**: PHP (vanilla)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Design**: Modern UI với CSS animations

## Bảo mật

- Password được mã hóa bằng `password_hash()`
- Prepared statements để chống SQL injection
- Input sanitization
- Session management

## Hỗ trợ

Nếu gặp vấn đề, vui lòng kiểm tra:
1. Apache và MySQL đã chạy chưa
2. Database đã import đúng chưa
3. Đường dẫn trong code có đúng không

## License

MIT License - Free to use
