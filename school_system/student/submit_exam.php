<?php
require_once '../config.php';
checkAuth(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: exams.php");
  exit();
}

$student_id = $_SESSION['user']['id'];
$exam_id = $_SESSION['current_exam']['exam_id'];

// بدء المعاملة
$conn->begin_transaction();

try {
  // إدخال نتيجة الامتحان
  $stmt = $conn->prepare("INSERT INTO exam_results 
                          (exam_id, student_id, total_score, max_score, percentage) 
                          VALUES (?, ?, 0, 0, 0)");
  $stmt->bind_param("ii", $exam_id, $student_id);
  $stmt->execute();
  $result_id = $conn->insert_id;

  $total_score = 0;
  $max_score = 0;

  foreach ($_POST['questions'] as $q_id => $answer_data) {
    $question_type = $answer_data['type'];
    $student_answer = $answer_data['answer'];
    $points = (int) $answer_data['points'];
    $is_correct = 0;
    $score = 0;

    $max_score += $points;

    if ($question_type == 'multiple_choice') {
      // جلب الإجابة الصحيحة من question_options
      $stmt = $conn->prepare("SELECT option_text FROM question_options 
                                  WHERE question_id = ? AND is_correct = 1");
      $stmt->bind_param("i", $q_id);
      $stmt->execute();
      $correct_option = $stmt->get_result()->fetch_assoc();

      if ($correct_option && $student_answer == $correct_option['option_text']) {
        $is_correct = 1;
        $score = $points;
        $total_score += $score;
      }
    } else { // true/false
      // جلب الإجابة الصحيحة من exam_questions
      $stmt = $conn->prepare("SELECT correct_answer FROM exam_questions 
                                  WHERE question_id = ?");
      $stmt->bind_param("i", $q_id);
      $stmt->execute();
      $question = $stmt->get_result()->fetch_assoc();

      if ($question) {
        // إصلاح المقارنة: تحويل الإجابات إلى صيغة موحدة
        $student_ans = trim($student_answer);
        $correct_ans = trim($question['correct_answer']);
        
        // تحويل true/false إلى صح/خطأ للمقارنة
        if (strtolower($student_ans) === 'true' || $student_ans === 'صح') {
          $student_ans = 'صح';
        } elseif (strtolower($student_ans) === 'false' || $student_ans === 'خطأ') {
          $student_ans = 'خطأ';
        }
        
        if (strtolower($correct_ans) === 'true') {
          $correct_ans = 'صح';
        } elseif (strtolower($correct_ans) === 'false') {
          $correct_ans = 'خطأ';
        }
        
        // المقارنة المصححة
        if ($student_ans === $correct_ans) {
          $is_correct = 1;
          $score = $points;
          $total_score += $score;
        }
      }
    }

    // حفظ الإجابة (تخزين الإجابة الأصلية من الطالب)
    $stmt = $conn->prepare("INSERT INTO exam_answers 
                              (result_id, question_id, answer, is_correct, score) 
                              VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisii", $result_id, $q_id, $student_answer, $is_correct, $score);
    $stmt->execute();
  }

  // حساب النسبة المئوية
  $percentage = ($max_score > 0) ? round(($total_score / $max_score) * 100, 2) : 0;

  // تحديث النتيجة النهائية
  $stmt = $conn->prepare("UPDATE exam_results 
                          SET total_score = ?, max_score = ?, percentage = ? 
                          WHERE result_id = ?");
  $stmt->bind_param("iiii", $total_score, $max_score, $percentage, $result_id);
  $stmt->execute();

  $conn->commit();

  $_SESSION['success_message'] = "تم تسليم الامتحان بنجاح! درجتك: $total_score/$max_score";
  unset($_SESSION['current_exam']);
  header("Location: exams.php");
  exit();

} catch (Exception $e) {
  $conn->rollback();
  $_SESSION['error_message'] = "حدث خطأ أثناء تسليم الامتحان: " . $e->getMessage();
  header("Location: take_exam.php");
  exit();
}
