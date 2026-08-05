<?php
require_once '../config.php';
checkAuth(['teacher']);

// الحصول على معرف المعلم من الجلسة بدلاً من POST
$teacher_id = $_SESSION['user']['id'];

// إنشاء مجلد التحميلات إذا لم يكن موجوداً
if (!file_exists('../uploads/worksheets')) {
  mkdir('../uploads/worksheets', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action == 'add') {
    // التحقق من البيانات المطلوبة
    $required = ['title', 'class_id', 'subject'];
    foreach ($required as $field) {
      if (empty($_POST[$field])) {
        $_SESSION['error_message'] = "جميع الحقول المطلوبة يجب ملؤها";
        header("Location: teacher_worksheets.php");
        exit();
      }
    }

    // التحقق من وجود الملف
    if (!isset($_FILES['worksheet_file']) || $_FILES['worksheet_file']['error'] != UPLOAD_ERR_OK) {
      $_SESSION['error_message'] = "يجب رفع ملف ورقة العمل";
      header("Location: teacher_worksheets.php");
      exit();
    }

    // التحقق من نوع الملف
    $allowed_types = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $file_type = $_FILES['worksheet_file']['type'];

    if (!in_array($file_type, $allowed_types)) {
      $_SESSION['error_message'] = "نوع الملف غير مسموح به (يجب أن يكون PDF أو Word)";
      header("Location: teacher_worksheets.php");
      exit();
    }

    // التحقق من حجم الملف (بحد أقصى 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['worksheet_file']['size'] > $max_size) {
      $_SESSION['error_message'] = "حجم الملف كبير جداً (الحد الأقصى 5MB)";
      header("Location: teacher_worksheets.php");
      exit();
    }

    // تنظيف البيانات
    $title = $conn->real_escape_string($_POST['title']);
    $class_id = (int) $_POST['class_id'];
    $subject = $conn->real_escape_string($_POST['subject']);

    // إنشاء اسم فريد للملف
    $file_ext = pathinfo($_FILES['worksheet_file']['name'], PATHINFO_EXTENSION);
    $file_name = 'worksheet_' . time() . '_' . uniqid() . '.' . $file_ext;
    $upload_path = '../uploads/worksheets/' . $file_name;

    // رفع الملف إلى السيرفر
    if (move_uploaded_file($_FILES['worksheet_file']['tmp_name'], $upload_path)) {
      // إدراج بيانات ورقة العمل في قاعدة البيانات
      $sql = "INSERT INTO worksheets (
                title, file_path, teacher_id, class_id, 
                subject, upload_date
            ) VALUES (
                '$title', '$file_name', $teacher_id, $class_id,
                '$subject', NOW()
            )";

      if ($conn->query($sql)) {
        $_SESSION['success_message'] = "تم رفع ورقة العمل بنجاح";

        // تسجيل النشاط في سجل المعلمين (إذا كان الجدول موجوداً)
        $log_sql = "INSERT INTO teacher_activity_log 
                            (teacher_id, activity_type, description, activity_date) 
                            VALUES 
                            ($teacher_id, 'upload', 'قام برفع ورقة عمل جديدة: $title', NOW())";
        @$conn->query($log_sql);

      } else {
        // في حالة خطأ في قاعدة البيانات، نحذف الملف الذي تم رفعه
        unlink($upload_path);
        $_SESSION['error_message'] = "حدث خطأ أثناء حفظ البيانات: " . $conn->error;
      }
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء رفع الملف";
    }

    header("Location: teacher_worksheets.php");
    exit();
  }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
  $worksheet_id = (int) $_GET['id'];

  // التحقق من أن ورقة العمل تخص هذا المعلم
  $worksheet = $conn->query("
        SELECT worksheet_id, title, file_path 
        FROM worksheets 
        WHERE worksheet_id = $worksheet_id AND teacher_id = $teacher_id
    ")->fetch_assoc();

  if ($worksheet) {
    // حذف الملف من السيرفر
    $file_path = '../uploads/worksheets/' . $worksheet['file_path'];
    if (file_exists($file_path)) {
      if (!unlink($file_path)) {
        $_SESSION['error_message'] = "حدث خطأ أثناء حذف الملف من السيرفر";
        header("Location: teacher_worksheets.php");
        exit();
      }
    }

    // حذف السجل من قاعدة البيانات
    if ($conn->query("DELETE FROM worksheets WHERE worksheet_id = $worksheet_id")) {
      $_SESSION['success_message'] = "تم حذف ورقة العمل بنجاح";

      // تسجيل النشاط في سجل المعلمين
      $log_sql = "INSERT INTO teacher_activity_log 
                        (teacher_id, activity_type, description, activity_date) 
                        VALUES 
                        ($teacher_id, 'delete', 'قام بحذف ورقة عمل: {$worksheet['title']}', NOW())";
      @$conn->query($log_sql);

    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء الحذف من قاعدة البيانات";
    }
  } else {
    $_SESSION['error_message'] = "ورقة العمل غير موجودة أو لا تملك صلاحية حذفها";
  }

  header("Location: teacher_worksheets.php");
  exit();
}

// إذا لم يكن هناك طلب معروف، إعادة التوجيه للصفحة الرئيسية
$_SESSION['error_message'] = "طلب غير معروف";
header("Location: dashboard.php");
exit();
?>
