<?php
// تعریف آرایه‌ای از معادل‌های فارسی
$translations = [
    'ticket' => 'تیکت',
    'upload' => 'آپلود',
    'enrollment_request' => 'درخواست حضور در دوره',
];
$allowedTypes = [
    // فایل‌های صوتی
    'audio/mpeg',     // MP3
    'audio/mp3',      // MP3

    // فایل‌های متنی
    'application/pdf', // PDF
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
    'application/msword', // DOC
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
    'application/vnd.ms-excel', // XLS
    'application/zip', // ZIP
    'application/x-rar-compressed', // RAR
    'text/plain', // TXT

    // ویدیوها
    'video/mp4', // MP4
    'video/x-msvideo', // AVI
    'video/mpeg', // MPEG
    'video/x-flv', // FLV
    'video/quicktime', // MOV

    // تصاویر
    'image/jpeg',  // JPG, JPEG
    'image/png',   // PNG
    'image/gif',   // GIF
    'image/bmp',   // BMP
    'image/webp',  // WEBP

    // سایر فرمت‌ها می‌توانند اضافه شوند
];

$pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function _getAllUsers()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function _getUserByID($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=:user_id");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function _createUser($full_name, $national_code, $email, $password, $phone, $role)
{
    try {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO users (full_name, national_code, email, password,phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $full_name,
            $national_code,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $phone,
            $role
        ]);
        return true; // Success
    } catch (PDOException $e) {
        return false; // Failure
    }
}


function _checkNationalCodeIranian($national_code)
{
    if (!preg_match('/^[0-9]{10}$/', $national_code))
        return false;
    for ($i = 0; $i < 10; $i++)
        if (preg_match('/^' . $i . '{10}$/', $national_code))
            return false;
    for ($i = 0, $sum = 0; $i < 9; $i++)
        $sum += ((10 - $i) * intval(substr($national_code, $i, 1)));
    $ret = $sum % 11;
    $parity = intval(substr($national_code, 9, 1));
    if (($ret < 2 && $ret == $parity) || ($ret >= 2 && $ret == 11 - $parity))
        return true;
    return false;
}
function _checkIfNationalCodeExists($national_code)
{
    try {
        global $pdo; // استفاده از شیء PDO جهانی

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE national_code = :national_code");
        $stmt->bindParam(':national_code', $national_code, PDO::PARAM_STR);
        $stmt->execute();

        // اگر تعداد رکوردها بیشتر از 0 باشد، کد ملی وجود دارد
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // خطای ارتباط با پایگاه داده را مدیریت کنید
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function _checkIfEmailExists($email)
{
    try {
        global $pdo; // استفاده از شیء PDO جهانی

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        // اگر تعداد رکوردها بیشتر از 0 باشد، ایمیل وجود دارد
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // خطای ارتباط با پایگاه داده را مدیریت کنید
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function _authenticateUser($identifier, $password)
{
    global $pdo;

    try {
        // Prepare the SQL statement to fetch the user by either email or national code
        $stmt = $pdo->prepare("SELECT * FROM users WHERE national_code = :identifier OR email = :identifier");
        $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the user data
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if a user was found and the password is correct
        if ($user && password_verify($password, $user['password'])) {
            return $user; // Authentication successful
        } else {
            return false; // Authentication failed
        }
    } catch (PDOException $e) {
        // Handle any database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function _getUnverifiedUsers()
{
    global $pdo;

    try {
        $stmt = $pdo->query("SELECT * FROM users WHERE is_verified = 0 ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}

function _get_root_url()
{
    // Determine the protocol (http or https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    // Get the host name
    $host = $_SERVER['HTTP_HOST'];

    // Construct the root URL
    $root_url = $protocol . $host;

    return $root_url;
}
define('ASSETS_URL', _get_root_url() . '/assets/');
define('BASE_URL', _get_root_url() . '/');
function _getUsersRegisteredLastWeek()
{
    global $pdo;

    try {
        // Calculate the date one week ago
        $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));

        // Prepare and execute the SQL query
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= :oneWeekAgo AND role != 'admin'");
        $stmt->bindParam(':oneWeekAgo', $oneWeekAgo, PDO::PARAM_STR);
        $stmt->execute();

        // Return the number of users registered in the past week (excluding admins)
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function _getVerifiedUsersCount()
{
    global $pdo;

    try {
        // Prepare and execute the SQL query
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_verified = 1 AND role != 'admin'");

        // Return the number of verified users (excluding admins)
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function _getUnverifiedUsersCount()
{
    global $pdo;

    try {
        // Prepare and execute the SQL query
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_verified = 0 AND role != 'admin'");

        // Return the number of unverified users (excluding admins)
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function _convertGregorianToPersian($gregorianDate)
{
    // جدا کردن تاریخ و زمان
    list($date,) = explode(' ', $gregorianDate);

    // جدا کردن سال، ماه و روز
    list($year, $month, $day) = explode('-', $date);

    // تبدیل تاریخ میلادی به شمسی
    $persianDate = jdate('Y/m/d', mktime(0, 0, 0, $month, $day, $year));

    return $persianDate;
}
function _changeUserStatus($userId, $actionValue)
{
    global $pdo;

    try {
        // Sanitize inputs
        $userId = intval($userId);
        $status = $actionValue == 'reject' ? 0 : 1;
        // Prepare the SQL statement
        $stmt = $pdo->prepare("UPDATE users SET is_verified = :status WHERE id = :id");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

        // Execute the statement
        $result = $stmt->execute();

        return $result;
    } catch (PDOException $e) {
        // Handle any database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function _updateUserProfile($userId, $fullName, $phone, $nationalCode, $email, $password)
{
    global $pdo;
    $sql = "UPDATE users SET full_name = :full_name, phone = :phone, national_code = :national_code, email = :email";
    if ($password) {
        $sql .= ", password = :password";
    }
    $sql .= " WHERE id = :user_id";

    // Prepare and execute the statement
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':national_code', $nationalCode);
    $stmt->bindParam(':email', $email);
    if ($password) {
        $stmt->bindParam(':password', $password);
    }
    $stmt->bindParam(':user_id', $userId);

    return $stmt->execute();
}
function _currentUserID()
{
    return $_SESSION['user_id'] ?? null;
}
function _createLessonRequest($teacherId, $lessonName, $major)
{
    // استفاده از PDO برای اجرای کوئری‌های دیتابیس
    global $pdo;

    try {
        // آماده‌سازی کوئری SQL برای درج یک رکورد جدید در جدول lesson_requests
        $stmt = $pdo->prepare("
            INSERT INTO lesson_requests (teacher_id, lesson_name, major, status, created_at, updated_at) 
            VALUES (:teacher_id, :lesson_name, :major, :status, NOW(), NOW())
        ");

        // بایند کردن مقادیر ورودی به پارامترهای کوئری
        $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
        $stmt->bindParam(':lesson_name', $lessonName, PDO::PARAM_STR);
        $stmt->bindParam(':major', $major, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR); // وضعیت پیش‌فرض "pending" است

        // اجرای کوئری
        return $stmt->execute();
    } catch (PDOException $e) {
        // در صورت بروز خطا، پیغام خطا در لاگ ذخیره می‌شود
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}
function _getAllRequestsRegisterLessonByTeacherID($teacherId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM lesson_requests  WHERE teacher_id = :teacher_id ORDER BY id DESC");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _getUserFullNameById($teacherId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users  WHERE id = :teacher_id");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _getPendingLessonRequests()
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM lesson_requests  WHERE status = 'pending' ORDER BY updated_at DESC");
        $stmt->execute([]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _changeLessonRequestStatus($requestId, $status)
{
    global $pdo;

    $stmt = $pdo->prepare("UPDATE lesson_requests SET status = :status WHERE id = :id");
    return $stmt->execute([':status' => $status, ':id' => $requestId]);
}
function _getLessonsApprovedTeacherByID($teacherId)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM  lesson_requests WHERE teacher_id = :teacher_id AND status='approved'");
    $stmt->execute([':teacher_id' => $teacherId]);
    return  $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function _createSupportTicket($userId, $title, $message)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, title, message, response) VALUES (:user_id, :title, :message, :response)");
        // Note that we are binding the `response` as `null` explicitly here
        $response = null;
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':response', $response, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (PDOException $e) {
        // Handle the error (logging or displaying a message)
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function _getAllSupportTicketsByUserID($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = :user_id");
    $stmt->execute(["user_id" => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function _changeTicketStatus($ticketId, $status, $response = null)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE support_tickets SET status = :status, response = :response, updated_at = NOW() WHERE id = :id");
    $stmt->execute(['status' => $status, 'response' => $response, 'id' => $ticketId]);
    return $stmt->rowCount();
}

function _getAllTickets()
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE status = 'pending' ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _getPathBeforeQuery($url)
{
    // Parse the URL and extract the path
    $parsedUrl = parse_url($url);
    return $parsedUrl['path'] ?? '';
}
function _getTicketByUserId($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function _updateTicketResponse($userId, $response)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE support_tickets SET response = :response, status = 'answered' WHERE user_id = :user_id");
    return $stmt->execute(['response' => $response, 'user_id' => $userId]);
}
function _getAllCourseAccepted()
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM lesson_requests WHERE status = 'approved' ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _convertTeacherIDToName($teacherId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id =:teacher_id");
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['full_name'];
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _convertIDToName($user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id =:user_id");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['full_name'];
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _createNotification($userId, $senderId, $message, $type)
{
    global $pdo;
    // Prepare the SQL statement to insert the notification
    $insertNotification = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, type) VALUES (?, ?, ?, ?)");

    // Execute the statement with the provided parameters
    $insertNotification->execute([$userId, $senderId, $message, $type]);
}
function _getUnreadNotifications($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_seen = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function _getAllMyNotifications($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function _getCountUnreadNotifications($userId)
{
    return count(_getUnreadNotifications($userId));
}



// تابعی برای برگرداندن معادل فارسی
function _translate($word)
{
    global $translations; // دسترسی به آرایه درون تابع
    return isset($translations[$word]) ?    "اعلان جدید از " . $translations[$word] . " رسید."
        : $word;
}
function _getNotificationByID($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? ");
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function _getPublicMessageBYLessonRequestID($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM public_messages WHERE id = ? ");
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function _markNotificationAsSeen($notificationId)
{
    global $pdo;

    try {
        // Prepare the SQL statement to update the `is_seen` field to 1
        $stmt = $pdo->prepare("UPDATE notifications SET is_seen = 1 WHERE id = ?");
        $stmt->execute([$notificationId]);

        return $stmt->rowCount(); // Returns the number of affected rows
    } catch (PDOException $e) {
        // Handle any database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function _createEnrollmentRequest($studentId, $lessonRequestId)
{
    global $pdo;
    try {
        // آماده‌سازی کوئری برای درج درخواست جدید
        $sql = "INSERT INTO course_enrollment_requests (student_id, lesson_request_id, status) 
                VALUES (:student_id, :lesson_request_id, 'pending')";
        $stmt = $pdo->prepare($sql);

        // جایگذاری مقادیر در پارامترهای کوئری
        $stmt->execute(['lesson_request_id' => $lessonRequestId, 'student_id' => $studentId]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
function _updateEnrollmentStatus($requestId, $newStatus)
{
    global $pdo;
    try {
        // بررسی معتبر بودن مقدار وضعیت جدید
        if (!in_array($newStatus, ['pending', 'approved', 'rejected'])) {
            throw new Exception('Invalid status value.');
        }

        // آماده‌سازی کوئری برای به‌روزرسانی وضعیت درخواست
        $sql = "UPDATE course_enrollment_requests SET status = :status, updated_at = NOW() 
                WHERE id = :request_id";
        $stmt = $pdo->prepare($sql);

        // جایگذاری مقادیر در پارامترهای کوئری
        $stmt->execute(['request_id' => $requestId, 'status' => $newStatus]);

        // اجرای کوئری
        return $stmt->rowCount();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
function _checkIfRequestExists($user_id, $course_id)
{
    global $pdo;
    $query = "SELECT COUNT(*) FROM course_enrollment_requests WHERE student_id = :student_id AND lesson_request_id = :lesson_request_id AND status='pending'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['student_id' => $user_id, 'lesson_request_id' => $course_id]);
    return $stmt->fetchColumn() > 0;
}
function _getTeacherIdByCourse($course_id)
{
    global $pdo;
    $query = "SELECT teacher_id FROM lesson_requests WHERE id = :course_id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['course_id' => $course_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function _getAllEnrollmentRequestsByStudentID($studentId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM course_enrollment_requests WHERE student_id = :student_id ORDER BY created_at DESC");
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // مدیریت خطاهای دیتابیس
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _getCourseByID($id)
{
    global $pdo;
    $query = "SELECT * FROM lesson_requests WHERE id = :id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function _getEnrollmentRequestsForTeacher($teacherId)
{
    global $pdo;

    try {
        // Prepare the SQL query to join course_enrollment_requests with lesson_requests
        $stmt = $pdo->prepare("
            SELECT 
                course_enrollment_requests.id AS request_id,
                course_enrollment_requests.status AS request_status,
                course_enrollment_requests.created_at AS request_created_at,
                course_enrollment_requests.updated_at AS request_updated_at,
                lesson_requests.lesson_name,
                lesson_requests.major,
                users.id AS student_id,
                users.full_name AS student_name,
                users.email AS student_email,
                users.phone AS student_phone
            FROM 
                course_enrollment_requests
            INNER JOIN 
                lesson_requests ON lesson_requests.id = course_enrollment_requests.lesson_request_id
            INNER JOIN 
                users ON users.id = course_enrollment_requests.student_id
            WHERE 
                lesson_requests.teacher_id = :teacher_id AND course_enrollment_requests.status = 'pending';
        ");

        // Bind the teacher ID parameter
        $stmt->execute(['teacher_id' => $teacherId]);

        // Fetch all results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle any database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function _getEnrollmentRequestsForStudent($studentId)
{
    global $pdo;

    try {
        // Prepare the SQL query to join course_enrollment_requests with lesson_requests and users (for teacher info)
        $stmt = $pdo->prepare("
            SELECT 
                course_enrollment_requests.id AS request_id,
                course_enrollment_requests.status AS request_status,
                course_enrollment_requests.created_at AS request_created_at,
                course_enrollment_requests.updated_at AS request_updated_at,
                lesson_requests.lesson_name,
                lesson_requests.major,
                users.id AS teacher_id,
                users.full_name AS teacher_name,
                users.email AS teacher_email,
                users.phone AS teacher_phone
            FROM 
                course_enrollment_requests
            INNER JOIN 
                lesson_requests ON lesson_requests.id = course_enrollment_requests.lesson_request_id
            INNER JOIN 
                users ON users.id = lesson_requests.teacher_id
            WHERE 
                course_enrollment_requests.student_id = :student_id AND course_enrollment_requests.status = 'approved';
        ");

        // Bind the student ID parameter
        $stmt->execute(['student_id' => $studentId]);

        // Fetch all results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle any database errors
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * 
 * Helper Function dd for test Code
 */
function dd(...$data)
{
    echo "<pre>";
    foreach ($data as $item) {
        var_dump($item);
    }
    echo "</pre>";
    die(); // برای متوقف کردن اجرای برنامه
}

function _changeStatusRequestRegisterInLessonStudent($requestId, $studentId, $status)
{
    global $pdo;

    $stmt = $pdo->prepare("UPDATE course_enrollment_requests SET status = :status WHERE id  = :id AND student_id=:student_id");
    return $stmt->execute(['status' => $status, 'id' => $requestId, 'student_id' => $studentId]);
}
function _getApprovedLessonsByTeacherAndCourse($teacherId, $lessonRequestId)
{
    // اتصال به دیتابیس (فرض کنید قبلاً اتصال PDO ایجاد شده است)
    global $pdo;

    // کوئری SQL
    $sql = "SELECT users.id ,users.full_name , users.email , users.phone , course_enrollment_requests.created_at FROM users 
             INNER JOIN course_enrollment_requests 
                 ON users.id = course_enrollment_requests.student_id 
             INNER JOIN lesson_requests 
                 ON lesson_requests.id = course_enrollment_requests.lesson_request_id 
             WHERE lesson_requests.status = 'approved' 
               AND lesson_requests.teacher_id = :teacher_id 
               AND lesson_requests.id = :lesson_request_id";

    // آماده‌سازی کوئری
    $stmt = $pdo->prepare($sql);

    // مقادیر پارامترها را تنظیم می‌کنیم
    $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $stmt->bindParam(':lesson_request_id', $lessonRequestId, PDO::PARAM_INT);

    // اجرای کوئری
    $stmt->execute();

    // دریافت نتایج
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function _convertIDLessonToLessonName($idLesson)
{
    global $pdo;
    $query = "SELECT lesson_name FROM lesson_requests WHERE id = :id ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $idLesson]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function _isTeacherOwnerOfLesson($teacherId, $lessonRequestId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM lesson_requests 
        WHERE id = :lesson_request_id AND teacher_id = :teacher_id
    ");
    $stmt->execute([
        'lesson_request_id' => $lessonRequestId,
        'teacher_id' => $teacherId
    ]);

    return $stmt->fetchColumn() > 0; // اگر مقدار بیشتر از صفر باشد، یعنی این درس به این مدرس تعلق دارد
}
function _addLessonSession($lessonRequestId, $title, $description, $contentLink)
{
    // اتصال به دیتابیس (فرض کنید قبلاً اتصال PDO ایجاد شده است)
    global $pdo;

    // کوئری SQL
    $sql = "INSERT INTO lesson_sessions (lesson_request_id, title, description, content_link) 
            VALUES (:lesson_request_id, :title, :description, :content_link)";

    // آماده‌سازی کوئری
    $stmt = $pdo->prepare($sql);

    // مقادیر پارامترها را تنظیم می‌کنیم
    $stmt->bindParam(':lesson_request_id', $lessonRequestId, PDO::PARAM_INT);
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':content_link', $contentLink, PDO::PARAM_STR);

    // اجرای کوئری
    if ($stmt->execute()) {
        return true; // درج موفقیت‌آمیز بود
    } else {
        return false; // خطا در درج
    }
}
function _getApprovedSessionsByLesson($teacherId, $lessonRequestId)
{
    // اتصال به دیتابیس (فرض کنید قبلاً اتصال PDO ایجاد شده است)
    global $pdo;

    // کوئری SQL برای دریافت جلسات درس تأیید شده
    $sql = "SELECT lesson_sessions.title, lesson_sessions.description, lesson_sessions.content_link, lesson_sessions.created_at 
            FROM lesson_sessions
            INNER JOIN lesson_requests 
                ON lesson_sessions.lesson_request_id = lesson_requests.id
            WHERE lesson_requests.status = 'approved'
              AND lesson_requests.teacher_id = :teacher_id
              AND lesson_requests.id = :lesson_request_id";

    // آماده‌سازی کوئری
    $stmt = $pdo->prepare($sql);

    // مقادیر پارامترها را تنظیم می‌کنیم
    $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $stmt->bindParam(':lesson_request_id', $lessonRequestId, PDO::PARAM_INT);

    // اجرای کوئری
    $stmt->execute();

    // دریافت نتایج
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function _insertPublicMessage($lessonRequestId, $teacherId, $message)
{
    global $pdo;

    // کوئری SQL
    $sql = "INSERT INTO public_messages (lesson_request_id, teacher_id, message) 
            VALUES (:lesson_request_id, :teacher_id, :message)";

    // آماده‌سازی کوئری
    $stmt = $pdo->prepare($sql);

    // تنظیم پارامترها
    $stmt->bindParam(':lesson_request_id', $lessonRequestId, PDO::PARAM_INT);
    $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR);

    // اجرای کوئری و بررسی نتیجه
    if ($stmt->execute()) {
        return true; // درج موفقیت‌آمیز
    } else {
        return false; // خطا در درج
    }
}
function _getPublicMessagesByLessonRequestID($lessonRequestId)
{
    global $pdo;

    // کوئری SQL
    $sql = "SELECT public_messages.id, public_messages.message, public_messages.created_at, 
                   users.full_name AS sender_name 
            FROM public_messages 
            INNER JOIN users ON public_messages.teacher_id = users.id 
            WHERE public_messages.lesson_request_id = :lesson_request_id 
            ORDER BY public_messages.created_at DESC";

    // آماده‌سازی کوئری
    $stmt = $pdo->prepare($sql);

    // تنظیم پارامترها
    $stmt->bindParam(':lesson_request_id', $lessonRequestId, PDO::PARAM_INT);

    // اجرای کوئری
    $stmt->execute();

    // دریافت نتایج
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function _addComment($userId, $lessonRequestId, $comment)
{
    global $pdo; // فرض کنید از PDO برای اتصال به دیتابیس استفاده می‌کنید

    $stmt = $pdo->prepare("INSERT INTO comments (user_id, lesson_request_id, comment) VALUES (:user_id, :lesson_request_id, :comment)");
    return $stmt->execute([
        ':user_id' => $userId,
        ':lesson_request_id' => $lessonRequestId,
        ':comment' => $comment,
    ]);
}
function _getCommentsByLessonRequestId($lessonRequestId)
{
    global $pdo; // فرض کنید از PDO برای اتصال به دیتابیس استفاده می‌کنید

    $stmt = $pdo->prepare("SELECT c.*, u.full_name 
                            FROM comments c
                            JOIN users u ON c.user_id = u.id
                            WHERE c.lesson_request_id = :lesson_request_id
                            ORDER BY c.created_at DESC");
    $stmt->execute([':lesson_request_id' => $lessonRequestId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // برگرداندن تمامی کامنت‌ها به عنوان یک آرایه
}
function _addStudentAssignment($studentId, $lessonRequestId, $filePath, $description)
{
    global $pdo;

    $sql = "INSERT INTO student_assignments (student_id, lesson_request_id, file_link, description, created_at) 
            VALUES (:student_id, :lesson_request_id, :file_link, :description, NOW())";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':student_id' => $studentId,
        ':lesson_request_id' => $lessonRequestId,
        ':file_link' => $filePath,
        ':description' => $description
    ]);
}
function _getAllAssignmentsForLessonRequest($lessonRequestId, $studentId)
{
    global $pdo;

    $sql = "SELECT * FROM student_assignments WHERE lesson_request_id = :lesson_request_id AND student_id =:student_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['lesson_request_id' => $lessonRequestId, 'student_id' => $studentId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
