<?php
require_once '../config.php';
$user = checkAuth(['teacher']);

// إضافة ورقة عمل جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['worksheet'])) {
  $title = sanitize($_POST['title']);
  $class_id = sanitize($_POST['class_id']);
  $file = $_FILES['worksheet'];

  // تحميل الملف
  $target_dir = "../uploads/worksheets/";
  $target_file = $target_dir . basename($file["name"]);
  move_uploaded_file($file["tmp_name"], $target_file);

  // حفظ في قاعدة البيانات
  $sql = "INSERT INTO worksheets (title, file_path, teacher_id, class_id) 
            VALUES (?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssii", $title, $target_file, $user['id'], $class_id);
  $stmt->execute();
}

// جلب أوراق العمل
$worksheets = $conn->query("
    SELECT w.*, c.class_name, c.class_group 
    FROM worksheets w
    JOIN class c ON w.class_id = c.class_id
    WHERE w.teacher_id = {$user['id']}
");
?>

<!-- واجهة HTML مشابهة للوحة التحكم -->