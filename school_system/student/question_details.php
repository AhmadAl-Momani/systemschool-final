<?php
require_once '../config.php';
checkAuth(['student']);

if (!isset($_GET['exam_id']) || !isset($_GET['question_id'])) {
  $_SESSION['error'] = "لم يتم تحديد السؤال";
  header("Location: random_exams.php");
  exit();
}

$exam_id = (int) $_GET['exam_id'];
$question_id = (int) $_GET['question_id'];
$student_id = $_SESSION['user']['id'];

// التحقق من أن الامتحان يخص الطالب
$exam_check = $conn->query("
    SELECT 1 FROM random_exams 
    WHERE re_id = $exam_id AND student_id = $student_id
")->num_rows;

if (!$exam_check) {
  $_SESSION['error'] = "لا يوجد لديك صلاحية لعرض هذا السؤال";
  header("Location: random_exams.php");
  exit();
}

// جلب تفاصيل السؤال
$question = $conn->query("
    SELECT 
        q.*,
        n.number_value as correct_number,
        a.selected_number,
        a.is_correct,
        (SELECT number_value FROM numbers WHERE number_id = a.selected_number) as student_answer
    FROM random_exam_questions q
    LEFT JOIN numbers n ON q.number_id = n.number_id
    LEFT JOIN random_exam_answers a ON q.exam_id = a.exam_id AND q.question_id = a.question_index
    WHERE q.exam_id = $exam_id AND q.question_id = $question_id
")->fetch_assoc();

include '../header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">
            <i class="fas fa-question-circle"></i> تفاصيل السؤال
            <span class="badge bg-<?= $question['is_correct'] ? 'success' : 'danger' ?> float-end">
              <?= $question['is_correct'] ? 'صحيح' : 'خطأ' ?>
            </span>
          </h4>
        </div>
        <div class="card-body">
          <div class="mb-4">
            <h5>معلومات السؤال:</h5>
            <ul class="list-group">
              <li class="list-group-item">
                <strong>المستوى:</strong>
                <span class="badge bg-<?=
                  $question['question_level'] == 'easy' ? 'success' :
                  ($question['question_level'] == 'medium' ? 'warning' : 'danger')
                  ?>">
                  <?= $question['question_level'] == 'easy' ? 'سهل' :
                    ($question['question_level'] == 'medium' ? 'متوسط' : 'صعب') ?>
                </span>
              </li>
              <li class="list-group-item">
                <strong>الصف:</strong> <?= $question['grade_level'] ?>
              </li>
            </ul>
          </div>

          <div class="mb-4">
            <h5>الإجابات:</h5>
            <div class="table-responsive">
              <table class="table table-bordered">
                <tr>
                  <th class="bg-light">الإجابة الصحيحة</th>
                  <td><?= $question['correct_number'] ?></td>
                </tr>
                <tr>
                  <th class="bg-light">إجابتك</th>
                  <td class="<?= $question['is_correct'] ? 'text-success' : 'text-danger' ?>">
                    <?= $question['student_answer'] ?? 'لم يتم الإجابة' ?>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <div class="text-center">
            <a href="random_exam_details.php?id=<?= $exam_id ?>" class="btn btn-primary">
              <i class="fas fa-arrow-left"></i> العودة إلى الامتحان
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>