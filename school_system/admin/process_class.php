<?php
require_once '../config.php';

// التحقق من صلاحية المستخدم
checkAuth(['admin']);

// معالجة عمليات الإضافة والتعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // التحقق من البيانات المطلوبة
  $required_fields = ['class_name', 'grade_level', 'section'];
  foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
      $_SESSION['error_message'] = "الحقول المميزة بعلامة (*) مطلوبة";
      header("Location: classes.php");
      exit();
    }
  }

  // تنظيف البيانات
  $data = [
    'class_id' => $_POST['class_id'] ?? 0,
    'class_name' => $conn->real_escape_string($_POST['class_name']),
    'class_group' => isset($_POST['class_group']) ? $conn->real_escape_string($_POST['class_group']) : null,
    'grade_level' => $conn->real_escape_string($_POST['grade_level']),
    'section' => $conn->real_escape_string($_POST['section'])
  ];

  if ($action === 'add') {
    // إضافة صف جديد
    $sql = "INSERT INTO class (class_name, class_group, grade_level, section) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
      "ssss",
      $data['class_name'],
      $data['class_group'],
      $data['grade_level'],
      $data['section']
    );

    if ($stmt->execute()) {
      $_SESSION['success_message'] = "تم إضافة الصف بنجاح";
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء إضافة الصف: " . $conn->error;
    }
  } elseif ($action === 'edit') {
    // تعديل الصف الحالي
    $sql = "UPDATE class SET 
                class_name = ?, 
                class_group = ?, 
                grade_level = ?, 
                section = ? 
                WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
      "ssssi",
      $data['class_name'],
      $data['class_group'],
      $data['grade_level'],
      $data['section'],
      $data['class_id']
    );

    if ($stmt->execute()) {
      $_SESSION['success_message'] = "تم تحديث بيانات الصف بنجاح";
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء تحديث البيانات: " . $conn->error;
    }
  }

  header("Location: classes.php");
  exit();
}

// معالجة عملية الحذف
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
  $class_id = (int) $_GET['id'];

  // التحقق من وجود طلاب في الصف قبل الحذف
  $check_sql = "SELECT COUNT(*) FROM student WHERE class_id = ?";
  $check_stmt = $conn->prepare($check_sql);
  $check_stmt->bind_param("i", $class_id);
  $check_stmt->execute();
  $result = $check_stmt->get_result();
  $students_count = $result->fetch_row()[0];

  if ($students_count > 0) {
    $_SESSION['error_message'] = "لا يمكن حذف الصف لأنه يحتوي على طلاب مسجلين";
  } else {
    $delete_sql = "DELETE FROM class WHERE class_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $class_id);

    if ($delete_stmt->execute()) {
      $_SESSION['success_message'] = "تم حذف الصف بنجاح";
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء حذف الصف";
    }
  }

  header("Location: classes.php");
  exit();
}

// إذا لم يكن هناك طلب معروف
$_SESSION['error_message'] = "طلب غير معروف";
header("Location: classes.php");
exit();
?>