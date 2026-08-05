<?php
require_once '../config.php';
checkAuth(['teacher']);

// الحصول على معرف المعلم من الجلسة
$teacher_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action == 'add') {
    // التحقق من البيانات المطلوبة
    $required = ['title', 'subject', 'class_id', 'start_time', 'end_time', 'duration', 'password'];
    foreach ($required as $field) {
      if (empty($_POST[$field])) {
        $_SESSION['error_message'] = "جميع الحقول المطلوبة يجب ملؤها";
        header("Location: teacher_exams.php");
        exit();
      }
    }

    // تنظيف البيانات
    $title = $conn->real_escape_string($_POST['title']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $class_id = (int) $_POST['class_id'];
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $password = $conn->real_escape_string($_POST['password']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $duration = (int) $_POST['duration'];

    // إدراج الامتحان الجديد
    $sql = "INSERT INTO exams (
                title, description, password, duration, 
                start_time, end_time, teacher_id, class_id, subject, created_at
            ) VALUES (
                '$title', '$description', '$password', $duration,
                '$start_time', '$end_time', $teacher_id, $class_id, '$subject', NOW()
            )";

    if ($conn->query($sql)) {
      $exam_id = $conn->insert_id;
      $_SESSION['success_message'] = "تم إضافة الامتحان بنجاح. يمكنك الآن إضافة الأسئلة.";
      // التوجيه لصفحة إدارة الأسئلة للامتحان الجديد
      header("Location: teacher_exams.php?manage_questions=" . $exam_id);
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء إضافة الامتحان: " . $conn->error;
      header("Location: teacher_exams.php");
    }
    exit();
  }

  if ($action == 'edit') {
    // التحقق من البيانات المطلوبة للتعديل
    $required = ['exam_id', 'title', 'subject', 'start_time', 'end_time', 'duration', 'password'];
    foreach ($required as $field) {
      if (empty($_POST[$field])) {
        $_SESSION['error_message'] = "جميع الحقول المطلوبة يجب ملؤها";
        header("Location: teacher_exams.php");
        exit();
      }
    }

    $exam_id = (int) $_POST['exam_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $password = $conn->real_escape_string($_POST['password']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $duration = (int) $_POST['duration'];

    // التحقق من أن الامتحان يخص هذا المعلم
    $check = $conn->query("SELECT exam_id FROM exams WHERE exam_id = $exam_id AND teacher_id = $teacher_id")->num_rows;

    if ($check == 1) {
      $sql = "UPDATE exams SET
                    title = '$title',
                    description = '$description',
                    password = '$password',
                    duration = $duration,
                    start_time = '$start_time',
                    end_time = '$end_time',
                    subject = '$subject'
                    WHERE exam_id = $exam_id";

      if ($conn->query($sql)) {
        $_SESSION['success_message'] = "تم تحديث الامتحان بنجاح";
      } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء التحديث: " . $conn->error;
      }
    } else {
      $_SESSION['error_message'] = "لا تملك صلاحية تعديل هذا الامتحان";
    }

    header("Location: teacher_exams.php");
    exit();
  }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
  $exam_id = (int) $_GET['id'];

  // التحقق من أن الامتحان يخص هذا المعلم
  $check = $conn->query("SELECT exam_id FROM exams WHERE exam_id = $exam_id AND teacher_id = $teacher_id")->num_rows;

  if ($check == 1) {
    // حذف الامتحان والنتائج المرتبطة به
    $conn->query("DELETE FROM exam_questions WHERE exam_id = $exam_id");
    $conn->query("DELETE FROM exam_results WHERE exam_id = $exam_id");

    if ($conn->query("DELETE FROM exams WHERE exam_id = $exam_id")) {
      $_SESSION['success_message'] = "تم حذف الامتحان بنجاح";
    } else {
      $_SESSION['error_message'] = "حدث خطأ أثناء الحذف: " . $conn->error;
    }
  } else {
    $_SESSION['error_message'] = "لا تملك صلاحية حذف هذا الامتحان";
  }

  header("Location: teacher_exams.php");
  exit();
}

header("Location: teacher_exams.php");
exit();
?>
