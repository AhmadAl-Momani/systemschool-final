<?php
require_once '../config.php';

// التحقق من الصلاحيات
checkAuth(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // البيانات الأساسية المطلوبة
  $required = ['tname', 'email', 'password', 'subject'];
  foreach ($required as $field) {
    if (empty($_POST[$field])) {
      $_SESSION['error_message'] = "الحقول المميزة بعلامة (*) مطلوبة";
      header("Location: teachers.php");
      exit();
    }
  }

  // تجهيز البيانات
  $data = [
    'tname' => $conn->real_escape_string($_POST['tname']),
    'email' => $conn->real_escape_string($_POST['email']),
    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
    'subject' => $conn->real_escape_string($_POST['subject']),
    'phone' => $_POST['phone'] ?? null,
    'qualification' => $_POST['qualification'] ?? null,
    'gender' => $_POST['gender'] ?? null,
    'address' => $_POST['address'] ?? null,
    'class_id' => $_POST['class_id'] ?? null,
    'is_active' => isset($_POST['is_active']) ? 1 : 0
  ];

  if ($action === 'add') {
    $sql = "INSERT INTO teacher (tname, email, password, subject, phone, qualification, gender, address, class_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
      "ssssssssii",
      $data['tname'],
      $data['email'],
      $data['password'],
      $data['subject'],
      $data['phone'],
      $data['qualification'],
      $data['gender'],
      $data['address'],
      $data['class_id'],
      $data['is_active']
    );
  } elseif ($action === 'edit' && !empty($_POST['teacher_id'])) {
    $teacher_id = (int) $_POST['teacher_id'];

    // إذا كانت كلمة المرور غير معبأة، لا نحدثها
    if (empty($_POST['password'])) {
      $sql = "UPDATE teacher SET 
                    tname = ?, email = ?, subject = ?, phone = ?, 
                    qualification = ?, gender = ?, address = ?, 
                    class_id = ?, is_active = ? 
                    WHERE teacher_id = ?";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "ssssssssii",
        $data['tname'],
        $data['email'],
        $data['subject'],
        $data['phone'],
        $data['qualification'],
        $data['gender'],
        $data['address'],
        $data['class_id'],
        $data['is_active'],
        $teacher_id
      );
    } else {
      $sql = "UPDATE teacher SET 
                    tname = ?, email = ?, password = ?, subject = ?, 
                    phone = ?, qualification = ?, gender = ?, address = ?, 
                    class_id = ?, is_active = ? 
                    WHERE teacher_id = ?";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "sssssssssii",
        $data['tname'],
        $data['email'],
        $data['password'],
        $data['subject'],
        $data['phone'],
        $data['qualification'],
        $data['gender'],
        $data['address'],
        $data['class_id'],
        $data['is_active'],
        $teacher_id
      );
    }
  }

  if ($stmt->execute()) {
    $_SESSION['success_message'] = ($action === 'add') ? "تم إضافة المعلم بنجاح" : "تم تحديث بيانات المعلم";
  } else {
    $_SESSION['error_message'] = "حدث خطأ: " . $conn->error;
  }

  header("Location: teachers.php");
  exit();
}

// معالجة الحذف
if ($_GET['action'] === 'delete' && !empty($_GET['id'])) {
  $stmt = $conn->prepare("DELETE FROM teacher WHERE teacher_id = ?");
  $stmt->bind_param("i", $_GET['id']);

  if ($stmt->execute()) {
    $_SESSION['success_message'] = "تم حذف المعلم بنجاح";
  } else {
    $_SESSION['error_message'] = "حدث خطأ أثناء الحذف";
  }

  header("Location: teachers.php");
  exit();
}