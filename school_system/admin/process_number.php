<?php
require_once '../config.php';
checkAuth(['admin']);

// إنشاء مجلد التحميلات إذا لم يكن موجوداً
if (!file_exists('../uploads/numbers')) {
  mkdir('../uploads/numbers', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // التحقق من البيانات المطلوبة
if (!isset($_POST['number_value']) || $_POST['number_value'] === '') {
    $_SESSION['error_message'] = "قيمة الرقم مطلوبة";
    header("Location: numbers.php");
    exit();
  }

  $number_value = (int) $_POST['number_value'];
  $number_id = $_POST['number_id'] ?? 0;

  if ($action === 'add') {
    // التحقق من وجود صورة
    if (!isset($_FILES['number_image']) || $_FILES['number_image']['error'] !== UPLOAD_ERR_OK) {
      $_SESSION['error_message'] = "صورة الرقم مطلوبة";
      header("Location: numbers.php");
      exit();
    }

    // معالجة رفع الصورة
    $file = $_FILES['number_image'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "number_$number_value." . $ext;
    $upload_path = "../uploads/numbers/" . $filename;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
      $_SESSION['error_message'] = "حدث خطأ أثناء رفع الصورة";
      header("Location: numbers.php");
      exit();
    }

    // إضافة الرقم الجديد
    $sql = "INSERT INTO numbers (number_value, image_path) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $number_value, $filename);

    if ($stmt->execute()) {
      $_SESSION['success_message'] = "تم إضافة الرقم بنجاح";
    } else {
      @unlink($upload_path); // حذف الصورة إذا فشلت الإضافة
      $_SESSION['error_message'] = "حدث خطأ أثناء الإضافة: " . $conn->error;
    }
  } elseif ($action === 'edit') {
    // تحديث الرقم الموجود
    $update_image = '';

    if (isset($_FILES['number_image']) && $_FILES['number_image']['error'] === UPLOAD_ERR_OK) {
      // جلب معلومات الصورة القديمة
      $old_image = $conn->query("SELECT image_path FROM numbers WHERE number_id = $number_id")->fetch_assoc()['image_path'];

      // رفع الصورة الجديدة
      $file = $_FILES['number_image'];
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = "number_$number_value." . $ext;
      $upload_path = "../uploads/numbers/" . $filename;

      if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $update_image = ", image_path = '$filename'";
        // حذف الصورة القديمة
        if (!empty($old_image)) {
          @unlink("../uploads/numbers/" . $old_image);
        }
      }
    }

    $sql = "UPDATE numbers SET number_value = $number_value $update_image WHERE number_id = $number_id";

    if ($conn->query($sql)) {
      $_SESSION['success_message'] = "تم تحديث الرقم بنجاح";
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء التحديث: " . $conn->error;
    }
  }

  header("Location: numbers.php");
  exit();
}

// معالجة حذف الرقم
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
  $number_id = (int) $_GET['id'];

  // جلب معلومات الصورة أولاً
  $number = $conn->query("SELECT * FROM numbers WHERE number_id = $number_id")->fetch_assoc();

  if ($number) {
    // حذف الرقم
    $conn->query("DELETE FROM numbers WHERE number_id = $number_id");

    // حذف الصورة
    if (!empty($number['image_path'])) {
      @unlink("../uploads/numbers/" . $number['image_path']);
    }

    $_SESSION['success_message'] = "تم حذف الرقم بنجاح";
  } else {
    $_SESSION['error_message'] = "الرقم غير موجود";
  }

  header("Location: numbers.php");
  exit();
}

// إذا لم يكن هناك طلب معروف
$_SESSION['error_message'] = "طلب غير معروف";
header("Location: numbers.php");
exit();
?>