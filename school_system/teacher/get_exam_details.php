<?php
require_once '../config.php';
checkAuth(['teacher']);

if (isset($_GET['exam_id'])) {
  $exam_id = (int) $_GET['exam_id'];
  $teacher_id = $_SESSION['user']['id'];

  $exam = $conn->query("SELECT * FROM exams WHERE exam_id = $exam_id AND teacher_id = $teacher_id")->fetch_assoc();

  if ($exam) {
    // تحويل التنسيق الزمني ليتوافق مع input type="datetime-local"
    $exam['start_time'] = str_replace(' ', 'T', $exam['start_time']);
    $exam['end_time'] = str_replace(' ', 'T', $exam['end_time']);
    echo json_encode($exam);
  } else {
    echo json_encode(null);
  }
}
?>