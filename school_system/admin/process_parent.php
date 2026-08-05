<?php
require_once '../config.php';
checkAuth(['admin']);

// تسجيل بيانات الطلب للتصحيح
error_log("Request received: " . print_r($_REQUEST, true));

// معالجة عمليات الإضافة والتعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // التحقق من البيانات المطلوبة للإضافة والتعديل الأساسي
  if (in_array($action, ['add', 'edit'])) {
    $required_fields = ['pname', 'email'];
    foreach ($required_fields as $field) {
      if (empty($_POST[$field])) {
        $_SESSION['error_message'] = "الحقل '{$field}' مطلوب";
        header("Location: parents.php");
        exit();
      }
    }

    // تنظيف البيانات
    $data = [
      'parent_id' => $_POST['parent_id'] ?? 0,
      'pname' => $conn->real_escape_string($_POST['pname']),
      'email' => $conn->real_escape_string($_POST['email']),
      'phone_num' => isset($_POST['phone_num']) ? $conn->real_escape_string($_POST['phone_num']) : null,
      'natnum' => isset($_POST['natnum']) ? $conn->real_escape_string($_POST['natnum']) : null,
      'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    if ($action === 'add') {
      // التحقق من كلمة المرور للإضافة
      if (empty($_POST['password'])) {
        $_SESSION['error_message'] = "كلمة المرور مطلوبة";
        header("Location: parents.php");
        exit();
      }

      // إضافة ولي أمر جديد
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $sql = "INSERT INTO parent (pname, email, password, phone_num, natnum, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "sssssi",
        $data['pname'],
        $data['email'],
        $password,
        $data['phone_num'],
        $data['natnum'],
        $data['is_active']
      );

      if ($stmt->execute()) {
        $_SESSION['success_message'] = "تم إضافة ولي الأمر بنجاح";
      } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء الإضافة: " . $conn->error;
      }

      header("Location: parents.php");
      exit();

    } elseif ($action === 'edit') {
      // تعديل ولي أمر موجود
      if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE parent SET 
                        pname = ?, email = ?, password = ?, phone_num = ?, 
                        natnum = ?, is_active = ? 
                        WHERE parent_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
          "sssssii",
          $data['pname'],
          $data['email'],
          $password,
          $data['phone_num'],
          $data['natnum'],
          $data['is_active'],
          $data['parent_id']
        );
      } else {
        $sql = "UPDATE parent SET 
                        pname = ?, email = ?, phone_num = ?, 
                        natnum = ?, is_active = ? 
                        WHERE parent_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
          "ssssii",
          $data['pname'],
          $data['email'],
          $data['phone_num'],
          $data['natnum'],
          $data['is_active'],
          $data['parent_id']
        );
      }

      if ($stmt->execute()) {
        $_SESSION['success_message'] = "تم تحديث بيانات ولي الأمر بنجاح";
      } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء التحديث: " . $conn->error;
      }

      header("Location: parents.php");
      exit();
    }
  }

  // معالجة إضافة ابن لولي الأمر
  if ($action === 'add_child') {
    $required_fields = ['parent_id', 'student_id', 'relationship'];
    foreach ($required_fields as $field) {
      if (empty($_POST[$field])) {
        $_SESSION['error_message'] = "جميع الحقول مطلوبة";
        header("Location: parent_children.php?id=" . $_POST['parent_id']);
        exit();
      }
    }

    $parent_id = (int) $_POST['parent_id'];
    $student_id = (int) $_POST['student_id'];
    $relationship = $conn->real_escape_string($_POST['relationship']);

    // التحقق من عدم وجود العلاقة بالفعل
    $check = $conn->query("SELECT COUNT(*) FROM parent_student 
                              WHERE parent_id = $parent_id AND student_id = $student_id")->fetch_row()[0];

    if ($check > 0) {
      $_SESSION['error_message'] = "هذا الطالب مضاف بالفعل لهذا ولي الأمر";
    } else {
      $sql = "INSERT INTO parent_student (parent_id, student_id, relationship) 
                    VALUES (?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iis", $parent_id, $student_id, $relationship);

      if ($stmt->execute()) {
        $_SESSION['success_message'] = "تم إضافة الطالب كابن لولي الأمر بنجاح";
      } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء الإضافة: " . $conn->error;
      }
    }

    header("Location: parent_children.php?id=$parent_id");
    exit();
  }
}

// معالجة عمليات الحذف (GET requests)
if (isset($_GET['action'])) {
  if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
    // حذف ولي أمر
    $parent_id = (int) $_GET['id'];

    // التحقق من وجود أبناء مرتبطين
    $children_count = $conn->query("SELECT COUNT(*) FROM parent_student 
                                       WHERE parent_id = $parent_id")->fetch_row()[0];

    if ($children_count > 0) {
      $_SESSION['error_message'] = "لا يمكن حذف ولي الأمر لأنه مرتبط بعدد $children_count من الطلاب";
    } else {
      $sql = "DELETE FROM parent WHERE parent_id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $parent_id);

      if ($stmt->execute()) {
        $_SESSION['success_message'] = "تم حذف ولي الأمر بنجاح";
      } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء الحذف: " . $conn->error;
      }
    }

    header("Location: parents.php");
    exit();

  } elseif ($_GET['action'] === 'remove_child' && isset($_GET['parent_id']) && isset($_GET['student_id'])) {
    // إزالة ابن من ولي الأمر
    $parent_id = (int) $_GET['parent_id'];
    $student_id = (int) $_GET['student_id'];

    $sql = "DELETE FROM parent_student 
                WHERE parent_id = ? AND student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $parent_id, $student_id);

    if ($stmt->execute()) {
      $_SESSION['success_message'] = "تم إزالة الطالب من قائمة الأبناء بنجاح";
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء الإزالة: " . $conn->error;
    }

    header("Location: parent_children.php?id=$parent_id");
    exit();
  }
}

// إذا لم يكن هناك طلب معروف
$_SESSION['error_message'] = "طلب غير معروف";
header("Location: parents.php");
exit();
?>