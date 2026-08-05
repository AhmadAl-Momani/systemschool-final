<?php
require_once '../config.php';
//تعريف دالة تقوم بتوليد امتحان عشوائي.
function generateRandomExam($student_id, $level, $grade_level)
{
  global $conn;

  // تحديد العمليات المسموحة لكل صف
  $operations = [
    'الصف الأول' => ['+', '-'],
    'الصف الثاني' => ['+', '-', '*'],
    'الصف الثالث' => ['+', '-', '*', '/']
  ];

  // تحديد نطاق الأرقام
  $ranges = [
    'easy' => ['min' => 1, 'max' => 20],
    'medium' => ['min' => 1, 'max' => 50],
    'hard' => ['min' => 1, 'max' => 100]
  ];

  // إنشاء الامتحان
  $conn->query("INSERT INTO random_exams 
                 (student_id, level, grade_level, total_questions) 
                 VALUES ($student_id, '$level', '$grade_level', 10)");
  $exam_id = $conn->insert_id();

  // إنشاء الأسئلة
  for ($i = 0; $i < 10; $i++) {
    //عملية حسابية عشوائية
    $op = $operations[$grade_level][array_rand($operations[$grade_level])];
    //توليد رقمين عشوائيين حسب مستوى الصعوبة 
    $num1 = rand($ranges[$level]['min'], $ranges[$level]['max']);
    $num2 = rand($ranges[$level]['min'], $ranges[$level]['max']);

    // ضبط القسمة لتكون بدون باقي
    if ($op == '/') {
      $num2 = $num2 == 0 ? 1 : $num2;
      $result = $num1;
      $num1 = $num1 * $num2;
    } else {
      $result = eval ("return $num1 $op $num2;");
    }

    $question_text = "$num1 $op $num2 = ?";

    $conn->query("INSERT INTO random_exam_questions 
                     (exam_id, question_text, correct_answer, question_level, grade_level)
                     VALUES ($exam_id, '$question_text', '$result', '$level', '$grade_level')");
  }

  return $exam_id;
}

// مثال للاستخدام
$student_id = 1;
$level = 'easy';
$grade_level = 'الصف الأول';
$exam_id = generateRandomExam($student_id, $level, $grade_level);
echo "تم إنشاء امتحان عشوائي برقم: $exam_id";
?>