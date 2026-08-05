<?php
require_once '../config.php';
checkAuth(['teacher']);

$exam_id = $_GET['exam_id'];
$questions = $conn->query("SELECT * FROM exam_questions WHERE exam_id = $exam_id ORDER BY question_id");

if ($questions->num_rows > 0) {
  echo '<div class="list-group">';
  while ($question = $questions->fetch_assoc()) {
    echo '<div class="list-group-item mb-3">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<h6>' . htmlspecialchars($question['question_text']) . '</h6>';
    echo '<div>';
    echo '<button class="btn btn-sm btn-danger" onclick="deleteQuestion(' . $question['question_id'] . ', ' . $exam_id . ')">';
    echo '<i class="fas fa-trash"></i> حذف';
    echo '</button>';
    echo '</div>';
    echo '</div>';

    // عرض الخيارات إذا كان السؤال متعدد الخيارات
    if ($question['question_type'] === 'multiple_choice') {
      $options = $conn->query("SELECT * FROM question_options WHERE question_id = " . $question['question_id']);
      echo '<ul class="list-group mt-2">';
      while ($option = $options->fetch_assoc()) {
        $is_correct = $option['is_correct'] ? ' <span class="badge bg-success">صحيح</span>' : '';
        echo '<li class="list-group-item">' . htmlspecialchars($option['option_text']) . $is_correct . '</li>';
      }
      echo '</ul>';
    } else {
      // للسؤال صح/خطأ
      echo '<div class="mt-2">';
      echo '<span class="badge bg-primary">الإجابة الصحيحة: ' . $question['correct_answer'] . '</span>';
      echo '</div>';
    }

    echo '<div class="mt-2">';
    echo '<span class="badge bg-info">الدرجة: ' . $question['points'] . '</span>';
    echo '<span class="badge bg-secondary ms-2">' . ($question['question_type'] === 'multiple_choice' ? 'متعدد الخيارات' : 'صح/خطأ') . '</span>';
    echo '</div>';
    echo '</div>';
  }
  echo '</div>';
} else {
  echo '<div class="alert alert-info">لا توجد أسئلة لهذا الامتحان بعد</div>';
}