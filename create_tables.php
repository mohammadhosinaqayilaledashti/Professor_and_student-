<?php

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $queries = [
        // ایجاد جدول کاربران
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(256) NOT NULL,
            national_code VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(256) NOT NULL,
			phone VARCHAR(256) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('student', 'teacher', 'admin') NOT NULL,
            is_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        // ایجاد جدول اعلان‌ها
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,      -- شناسه یکتا برای هر اعلان
            user_id INT NOT NULL,                   -- شناسه کاربری که اعلان برای اوست
            sender_id INT,                          -- شناسه کاربری که اعلان را ارسال کرده (استاد یا ادمین)
            message TEXT NOT NULL,                  -- متن اعلان
            type ENUM('ticket', 'upload', 'enrollment_request') NOT NULL, -- نوع اعلان (تیکت یا آپلود)
            is_seen BOOLEAN DEFAULT FALSE,          -- وضعیت مشاهده شدن اعلان توسط کاربر
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- تاریخ و زمان ایجاد اعلان
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, -- ارتباط با جدول کاربران
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL -- ارتباط با کاربری که اعلان را ارسال کرده
        );",
        // ایجاد جدول تیکت‌های پشتیبانی
        "CREATE TABLE IF NOT EXISTS support_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            title VARCHAR(256),
            message TEXT,
            status ENUM('pending','answered', 'closed') DEFAULT 'pending',
            response TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );",
        // ایجاد جدول درخواست‌های درس توسط استاد
        "CREATE TABLE IF NOT EXISTS lesson_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            lesson_name VARCHAR(255) NOT NULL,
            major VARCHAR(255) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
        );",
        // ایجاد جدول درخواست‌های ثبت‌نام در درس
        "CREATE TABLE IF NOT EXISTS course_enrollment_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,  -- شناسه دانشجو که درخواست ارسال کرده
            lesson_request_id INT NOT NULL,  -- شناسه درس (مربوط به جدول درخواست تدریس)
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',  -- وضعیت درخواست ثبت‌نام
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- تاریخ ثبت درخواست
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- تاریخ آخرین بروزرسانی
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,  -- ارتباط با جدول کاربران (دانشجو)
            FOREIGN KEY (lesson_request_id) REFERENCES lesson_requests(id) ON DELETE CASCADE  -- ارتباط با جدول درخواست درس (توسط استاد)
        );",
        "CREATE TABLE IF NOT EXISTS lesson_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY, -- شناسه یکتا برای هر جلسه
            lesson_request_id INT NOT NULL, -- شناسه درس (مربوط به جدول درخواست درس)
            title VARCHAR(255) NOT NULL, -- عنوان جلسه
            description text NOT NULL, -- توضیحات جلسه
            content_link VARCHAR(255) NOT NULL, -- لینک فایل جلسه
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- تاریخ بارگذاری
            FOREIGN KEY (lesson_request_id) REFERENCES lesson_requests(id) ON DELETE CASCADE -- ارتباط با جدول درس‌ها
        );
",
        "CREATE TABLE IF NOT EXISTS public_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_request_id INT NOT NULL,
            teacher_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lesson_request_id) REFERENCES lesson_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
        );",
        "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL, -- شناسه کاربری که کامنت را ارسال کرده
                lesson_request_id INT NOT NULL, -- شناسه درس (مربوط به جدول درخواست درس)
                comment TEXT NOT NULL, -- متن کامنت
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- زمان ارسال کامنت
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, -- ارتباط با جدول کاربران
                FOREIGN KEY (lesson_request_id) REFERENCES lesson_requests(id) ON DELETE CASCADE -- ارتباط با جدول درخواست درس
            );
",
        "CREATE TABLE IF NOT EXISTS student_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,    -- شناسه یکتا برای هر تکلیف
            student_id INT NOT NULL,              -- شناسه دانشجویی که تکلیف را بارگذاری کرده
            lesson_request_id INT NOT NULL,       -- شناسه درس مربوطه
            file_link VARCHAR(255) NOT NULL,      -- لینک فایل بارگذاری شده تکلیف
            description TEXT,                     -- توضیحات اختیاری برای تکلیف
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- تاریخ و زمان بارگذاری تکلیف
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,  -- ارتباط با دانشجویی که تکلیف را ارسال کرده
            FOREIGN KEY (lesson_request_id) REFERENCES lesson_requests(id) ON DELETE CASCADE  -- ارتباط با درس
        );",

    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
    }

    // // درج یک کاربر admin
    $insertAdmin = "INSERT INTO users (full_name, national_code, email, phone, password, role, is_verified)
                    VALUES (:full_name, :national_code, :email, :phone, :password, :role, :is_verified)";
    $stmt = $pdo->prepare($insertAdmin);

    // تنظیم پارامترها
    $stmt->execute([
        ':full_name' => 'Admin User',
        ':national_code' => '1234567890',
        ':email' => 'admin@yahoo.com',
        ':phone' => '09123456789',
        ':password' => password_hash('admin', PASSWORD_BCRYPT), // هش کردن رمز عبور
        ':role' => 'admin',
        ':is_verified' => true
    ]);

    echo "Tables created and admin user inserted successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
