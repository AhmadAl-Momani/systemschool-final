<?php
require_once '../config.php';
checkAuth(['teacher']);

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    // التحقق من البيانات المطلوبة
    if (empty($_POST['exam_id']) || empty($_POST['question_text']) || empty($_POST['question_type'])) {
      throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
    }

    $exam_id = (int) $_POST['exam_id'];
    $question_text = $conn->real_escape_string($_POST['question_text']);
    $question_type = $conn->real_escape_string($_POST['question_type']);
    $points = isset($_POST['points']) ? (int) $_POST['points'] : 1;
    $correct_answer = '';

    // بدء المعاملة
    $conn->begin_transaction();

    try {
      // إضافة السؤال الأساسي
      $stmt = $conn->prepare("INSERT INTO exam_questions 
                                  (exam_id, question_text, question_type, points, correct_answer) 
                                  VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("issis", $exam_id, $question_text, $question_type, $points, $correct_answer);
      $stmt->execute();
      $question_id = $conn->insert_id;

      // معالجة الخيارات حسب نوع السؤال
      if ($question_type === 'multiple_choice') {
        if (empty($_POST['options'])) {
          throw new Exception('يجب إضافة خيارات للأسئلة متعددة الخيارات');
        }

        $options = $_POST['options'];
        $correct_option_index = isset($_POST['correct_option']) ? (int) $_POST['correct_option'] : 0;

        foreach ($options as $index => $option_text) {
          if (trim($option_text) === '') continue;
          
          $option_text_escaped = $conn->real_escape_string($option_text);
          $is_correct = ($index == $correct_option_index) ? 1 : 0;

          $stmt_opt = $conn->prepare("INSERT INTO question_options 
                                          (question_id, option_text, is_correct) 
                                          VALUES (?, ?, ?)");
          $stmt_opt->bind_param("isi", $question_id, $option_text_escaped, $is_correct);
          $stmt_opt->execute();

          if ($is_correct) {
            $correct_answer = $option_text;
          }
        }
      } else { // true_false
        // التأكد من استقبال القيمة الصحيحة من الراديو بوتون
        if (!isset($_POST['correct_answer'])) {
          throw new Exception('يجب اختيار الإجابة الصحيحة (صح أو خطأ)');
        }
        $correct_answer = ($_POST['correct_answer'] === 'true') ? 'صح' : 'خطأ';
      }

      // تحديث الإجابة الصحيحة في السؤال الأساسي
      $correct_answer_escaped = $conn->real_escape_string($correct_answer);
      $update_sql = "UPDATE exam_questions SET correct_answer = '$correct_answer_escaped' WHERE question_id = $question_id";
      if (!$conn->query($update_sql)) {
          throw new Exception('فشل تحديث الإجابة الصحيحة: ' . $conn->error);
      }

      $conn->commit();
      $response['success'] = true;
      $response['message'] = 'تم إضافة السؤال بنجاح';
    } catch (Exception $e) {
      $conn->rollback();
      throw $e;
    }
  } elseif ($action === 'delete') {
    if (empty($_POST['question_id'])) {
      throw new Exception('معرف السؤال مطلوب');
    }
    $question_id = (int) $_POST['question_id'];
    
    $conn->begin_transaction();
    try {
      $conn->query("DELETE FROM question_options WHERE question_id = $question_id");
      $conn->query("DELETE FROM exam_questions WHERE question_id = $question_id");
      $conn->commit();
      $response['success'] = true;
      $response['message'] = 'تم حذف السؤال بنجاح';
    } catch (Exception $e) {
      $conn->rollback();
      throw $e;
    }
  } else {
    $response['message'] = 'إجراء غير معروف';
  }
} catch (Exception $e) {
  $response['message'] = 'حدث خطأ: ' . $e->getMessage();
}

echo json_encode($response);
?>
