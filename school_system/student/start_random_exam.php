<?php
//إنشاء امتحان عشوائي لطالب بناءً على مستوى الصعوبة
//وتخزينه في قاعدة البيانات،
// ثم حفظ بيانات الامتحان داخل الجلسة ($_SESSION) لعرضها لاحقًا في صفحة take_random_exam.php

require_once '../config.php';
checkAuth(['student']);
//إذا لم يتم تمرير مستوى، أو كان غير معتمد، يتم تحويل المستخدم مع رسالة خطأ.
if (!isset($_GET['level']) || !in_array($_GET['level'], ['easy', 'medium', 'hard'])) {
  $_SESSION['error'] = "مستوى الامتحان غير صحيح";
  header("Location: random_exams.php");
  exit();
}
//خذ مستوى الامتحان ومعرّف الطالب من الجلسة.
$level = $_GET['level'];
$student_id = $_SESSION['user']['id'];

// جلب معلومات الطالب مع التحقق من وجود grade_level
$student = $conn->query("SELECT * FROM student WHERE student_id = $student_id")->fetch_assoc();
//إذا الطالب غير موجود في الجدول، يتم الإنهاء برسالة خطأ. وإن لم يكن له صف، يتم تعيين "الصف الأول" كقيمة افتراضية.
if (!$student) {
  $_SESSION['error'] = "الطالب غير موجود";
  header("Location: random_exams.php");
  exit();
}

// تعيين قيمة افتراضية إذا كان grade_id فارغاً
$grade_level = !empty($student['grade_id']) ? $student['grade_id'] : 'الصف الأول';

// بدء معاملة قاعدة البيانات
//بدء معاملة لضمان أن كل العمليات تتم بنجاح أو يتم التراجع عنها.
$conn->begin_transaction();

try {
  // إنشاء سجل الامتحان العشوائي
  //نشاء سجل للامتحان وربط الطالب بالمستوى والصف
  $stmt = $conn->prepare("INSERT INTO random_exams (student_id, level, grade_level) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $student_id, $level, $grade_level);
  $stmt->execute();
  $exam_id = $conn->insert_id;
  $stmt->close();

  //  إنشاء الأسئلة العشوائية
  //عددهم 10 
  $questions = [];
  for ($i = 0; $i < 10; $i++) {
    $num1 = rand(0, 9);// اختيار رقمين راندوم
    $num2 = rand(0, 9);

    // تحديد العملية حسب المستوى
    switch ($level) {
      case 'easy':
        $operation = rand(0, 1) ? '+' : '-';
        break;
      case 'medium':
        $operation = ['+', '-', '*'][rand(0, 2)];
        break;
      case 'hard':
        $operation = ['+', '-', '*', '/'][rand(0, 3)];
        break;
    }

    // حساب الإجابة الصحيحة
    $correct_answer = match ($operation) {//افحص شو هي العملية+-ضرب قسمة
      '+' => $num1 + $num2,//ازا كانت جمع
      '-' => $num1 - $num2,
      '*' => $num1 * $num2,
      '/' => $num2 != 0 ? round($num1 / $num2, 2) : 0,//تاكد رقم 2 مو 0 
      default => 0//ازا كانت العملية مو معروفة خلي الناتج0
    };

    // إدراج السؤال في قاعدة البيانات
    $stmt = $conn->prepare("INSERT INTO random_exam_questions 
                              (exam_id, question_level, grade_level, num1, num2, operation, correct_answer) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiisd", $exam_id, $level, $grade_level, $num1, $num2, $operation, $correct_answer);
    $stmt->execute();
    $question_id = $stmt->insert_id;
    $stmt->close();
//حفظ السوال موقتا في الاراي
    $questions[] = [
      'id' => $question_id,
      'num1' => $num1,
      'num2' => $num2,
      'operation' => $operation,
      'correct_answer' => $correct_answer,
      'student_answer' => null,
      'is_correct' => false
    ];
  }

  // حفظ البيانات في الجلسة
  $_SESSION['random_exam'] = [
    'exam_id' => $exam_id,
    'level' => $level,
    'questions' => $questions,
    'current_question' => 0,
    'answers' => [],
    'start_time' => time()
  ];

  $conn->commit();
  header("Location: take_random_exam.php");
  exit();

} catch (Exception $e) {
  $conn->rollback();
  $_SESSION['error'] = "حدث خطأ أثناء إنشاء الامتحان: " . $e->getMessage();
  error_log("Exam creation error: " . $e->getMessage());
  header("Location: random_exams.php");
  exit();
}
?>