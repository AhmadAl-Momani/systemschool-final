<?php
require_once '../config.php';
checkAuth(['student']);

$worksheet_id = (int) ($_GET['id'] ?? 0);
$student_id = $_SESSION['user']['id'];

// التحقق من أن الطالب لديه حق الوصول
$worksheet = $conn->query("
    SELECT w.* 
    FROM worksheets w
    JOIN student s ON w.class_id = s.class_id
    WHERE w.worksheet_id = $worksheet_id AND s.student_id = $student_id
")->fetch_assoc();

if ($worksheet && file_exists('../uploads/worksheets/' . $worksheet['file_path'])) {
  
  // تسجيل التحميل
  // نتحقق أولاً إذا كان هناك سجل موجود لهذا الطالب وهذه الورقة
  $check_view = $conn->query("SELECT view_id FROM worksheet_views WHERE worksheet_id = $worksheet_id AND student_id = $student_id");
  
  if ($check_view->num_rows > 0) {
    // تحديث السجل الموجود
    $conn->query("UPDATE worksheet_views SET downloaded_at = NOW() WHERE worksheet_id = $worksheet_id AND student_id = $student_id");
  } else {
    // إنشاء سجل جديد
    $conn->query("INSERT INTO worksheet_views (worksheet_id, student_id, viewed_at, downloaded_at) VALUES ($worksheet_id, $student_id, NOW(), NOW())");
  }

  // إرسال الملف
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . basename($worksheet['file_path']) . '"');
  readfile('../uploads/worksheets/' . $worksheet['file_path']);
  exit;
} else {
  $_SESSION['error'] = "ورقة العمل غير متاحة";
  header("Location: worksheets.php");
  exit;
}
?>
