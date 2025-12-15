-- =============================================
-- COMPLETE DATABASE SCHEMA FOR MEETING ROOM BOOKING SYSTEM
-- Created: 2025-12-13
-- Version: 2.2 (With folder_path support)
-- =============================================

-- Drop existing tables in reverse order (to avoid foreign key constraints)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS lab_documents;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS room_types;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- TABLE 1: USERS
-- Stores user accounts (both regular users and admins)
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE 2: ROOM TYPES
-- Defines different categories of meeting rooms
-- =============================================
CREATE TABLE room_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE 3: ROOMS
-- Stores meeting room information
-- =============================================
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_type_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT NOT NULL DEFAULT 1,
    price_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    price_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    size DECIMAL(10,2) DEFAULT NULL COMMENT 'Room size in square meters',
    floor INT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    amenities TEXT COMMENT 'JSON array of amenities',
    equipment TEXT COMMENT 'JSON array of equipment',
    status ENUM('available', 'occupied', 'maintenance', 'inactive') DEFAULT 'available',
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE RESTRICT,
    INDEX idx_room_type (room_type_id),
    INDEX idx_status (status),
    INDEX idx_capacity (capacity),
    INDEX idx_price (price_per_hour),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE 4: BOOKINGS
-- Stores room booking information
-- =============================================
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_hours DECIMAL(5,2) NOT NULL,
    booking_type ENUM('hourly', 'daily', 'custom') DEFAULT 'hourly',
    total_price DECIMAL(10,2) NOT NULL,
    purpose TEXT,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'rejected') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    payment_method VARCHAR(50) DEFAULT NULL,
    cancellation_reason TEXT,
    cancelled_at TIMESTAMP NULL DEFAULT NULL,
    confirmed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_room_id (room_id),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE 5: CONTACTS
-- Stores contact form submissions
-- =============================================
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    admin_reply TEXT,
    replied_at TIMESTAMP NULL DEFAULT NULL,
    replied_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE 6: REVIEWS
-- Stores room reviews and ratings
-- NOTE: Rating validation (1-5) is handled in PHP code
-- =============================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    booking_id INT DEFAULT NULL,
    rating INT NOT NULL COMMENT 'Must be between 1-5 (validated in PHP)',
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_response TEXT,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_room_id (room_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLE 7: LAB DOCUMENTS
-- Stores uploaded documents and files with folder support
-- =============================================
CREATE TABLE lab_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL COMMENT 'File size in bytes',
    file_type VARCHAR(50),
    category VARCHAR(100),
    folder_path VARCHAR(500) DEFAULT NULL COMMENT 'Folder structure path',
    uploaded_by INT NOT NULL,
    downloads INT DEFAULT 0,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_folder_path (folder_path),
    INDEX idx_status (status),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Insert default room types
INSERT INTO room_types (name, description, icon, status) VALUES
('Phòng Họp Nhỏ', 'Phòng họp dành cho 4-8 người, phù hợp cho các cuộc họp nhóm nhỏ', 'fa-users', 'active'),
('Phòng Họp Trung', 'Phòng họp dành cho 10-20 người, trang bị đầy đủ thiết bị hiện đại', 'fa-building', 'active'),
('Phòng Hội Nghị', 'Phòng hội nghị lớn dành cho 30-100 người, phù hợp cho sự kiện, hội thảo', 'fa-presentation', 'active'),
('Phòng Đào Tạo', 'Phòng đào tạo với bàn ghế linh hoạt, máy chiếu và bảng trắng', 'fa-chalkboard-teacher', 'active'),
('Phòng VIP', 'Phòng họp cao cấp với nội thất sang trọng và dịch vụ đặc biệt', 'fa-crown', 'active');

-- Insert admin account
-- Default password: admin123
-- Password hash generated with: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, email, password, full_name, phone, role, status, created_at) VALUES
('admin', 'admin@meetingroom.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '0123456789', 'admin', 'active', NOW());

-- Insert sample users for testing
INSERT INTO users (username, email, password, full_name, phone, role, status) VALUES
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', '0987654321', 'user', 'active'),
('user2', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', '0976543210', 'user', 'active');

-- Insert sample rooms
INSERT INTO rooms (room_type_id, name, description, capacity, price_per_hour, price_per_day, size, floor, location, status) VALUES
(1, 'Phòng Họp A1', 'Phòng họp nhỏ tầng 1, view đẹp, thoáng mát', 6, 100000, 800000, 25.5, 1, 'Tầng 1, Tòa nhà A', 'available'),
(1, 'Phòng Họp A2', 'Phòng họp nhỏ tầng 2, yên tĩnh', 8, 120000, 900000, 30.0, 2, 'Tầng 2, Tòa nhà A', 'available'),
(2, 'Phòng Họp B1', 'Phòng họp trung bình, đầy đủ tiện nghi', 15, 200000, 1500000, 50.0, 1, 'Tầng 1, Tòa nhà B', 'available'),
(2, 'Phòng Họp B2', 'Phòng họp trung bình, có máy chiếu 4K', 20, 250000, 1800000, 60.0, 2, 'Tầng 2, Tòa nhà B', 'available'),
(3, 'Hội Trường C1', 'Hội trường lớn, sức chứa 100 người', 100, 500000, 3500000, 150.0, 1, 'Tầng 1, Tòa nhà C', 'available'),
(4, 'Phòng Đào Tạo D1', 'Phòng đào tạo với bàn ghế linh hoạt', 30, 300000, 2000000, 80.0, 3, 'Tầng 3, Tòa nhà D', 'available'),
(5, 'Phòng VIP E1', 'Phòng VIP cao cấp, nội thất sang trọng', 12, 400000, 3000000, 45.0, 5, 'Tầng 5, Tòa nhà E', 'available');

-- =============================================
-- VERIFICATION QUERIES
-- Run these to verify the database setup
-- =============================================

-- Check all tables
SELECT 'Tables created successfully!' as Status;

-- Count records in each table
SELECT 'users' as TableName, COUNT(*) as RecordCount FROM users
UNION ALL
SELECT 'room_types', COUNT(*) FROM room_types
UNION ALL
SELECT 'rooms', COUNT(*) FROM rooms
UNION ALL
SELECT 'bookings', COUNT(*) FROM bookings
UNION ALL
SELECT 'contacts', COUNT(*) FROM contacts
UNION ALL
SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL
SELECT 'lab_documents', COUNT(*) FROM lab_documents;

-- Show admin account
SELECT id, username, email, full_name, role, created_at 
FROM users 
WHERE role = 'admin';

-- Show all rooms with their types
SELECT r.id, r.name, rt.name as room_type, r.capacity, r.price_per_hour, r.status
FROM rooms r
JOIN room_types rt ON r.room_type_id = rt.id
ORDER BY r.id;

-- =============================================
-- NOTES
-- =============================================
-- 1. Default admin credentials:
--    Username: admin
--    Password: admin123
--
-- 2. All sample users have the same password: admin123
--
-- 3. Make sure to change default passwords in production!
--
-- 4. This schema supports:
--    - User management (admin & regular users)
--    - Room types and rooms
--    - Booking system with time slots
--    - Contact form submissions
--    - Review and rating system
--    - Document/file management WITH FOLDER SUPPORT
--
-- 5. Foreign key constraints are properly set up
--    to maintain data integrity
--
-- 6. Indexes are added for better query performance
--
-- 7. NO TRIGGERS (compatible with free hosting)
--    - Rating validation (1-5) is handled in PHP
--    - Room rating updates are handled in PHP
--
-- 8. Compatible with InfinityFree and similar
--    free hosting providers
--
-- 9. NEW in v2.2:
--    - Added folder_path column to lab_documents
--    - Supports hierarchical folder structure
-- =============================================
