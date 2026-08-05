<?php
require_once '../config.php';
checkAuth(['student']);

if (!isset($_SESSION['random_exam'])) {
  $_SESSION['error'] = "لا يوجد امتحان نشط";
  header("Location: random_exams.php");
  exit();
}

$exam = $_SESSION['random_exam'];
$student_id = $_SESSION['user']['id'];

// حساب النتيجة
$correct_answers = 0;
$total_questions = count($exam['questions']);
$details = [];

foreach ($_POST['questions'] as $i => $answer) {
  $is_correct = (float) $answer['student_answer'] == (float) $answer['correct_answer'];
  if ($is_correct)
    $correct_answers++;

  $details[] = [
    'question_id' => $exam['questions'][$i]['id'],
    'num1' => $answer['num1'],
    'num2' => $answer['num2'],
    'operation' => $answer['operation'],
    'correct_answer' => $answer['correct_answer'],
    'student_answer' => $answer['student_answer'],
    'is_correct' => $is_correct
  ];
}

$score = round(($correct_answers / $total_questions) * 100, 2);

// حفظ الإجابات في قاعدة البيانات
$conn->begin_transaction();
try {
  foreach ($details as $answer) {
    $stmt = $conn->prepare("INSERT INTO random_exam_answers 
      (exam_id, question_id, number_id, selected_number, operation, correct_answer, is_correct) 
      VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
      "iiidsdi",
      $exam['exam_id'],
      $answer['question_id'],
      $answer['num1'], // يمكن استخدام num1 كـ number_id
      $answer['student_answer'],
      $answer['operation'],
      $answer['correct_answer'],
      $answer['is_correct']
    );
    $stmt->execute();
    $stmt->close();
  }

  // تحديث نتيجة الامتحان
  $stmt = $conn->prepare("UPDATE random_exams 
    SET correct_answers = ?, score = ? 
    WHERE re_id = ?");
  $stmt->bind_param("idi", $correct_answers, $score, $exam['exam_id']);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
} catch (Exception $e) {
  $conn->rollback();
  $_SESSION['error'] = "حدث خطأ أثناء حفظ النتائج";
  header("Location: random_exams.php");
  exit();
}

// حفظ النتائج في الجلسة
$_SESSION['exam_results'] = [
  'exam_id' => $exam['exam_id'],
  'level' => $exam['level'],
  'total_questions' => $total_questions,
  'correct_answers' => $correct_answers,
  'score' => $score,
  'details' => $details
];

// حذف بيانات الامتحان الحالي
unset($_SESSION['random_exam']);

header("Location: random_exam_result.php");
exit();
?>