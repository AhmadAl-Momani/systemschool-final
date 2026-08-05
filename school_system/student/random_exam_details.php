<?php
require_once '../config.php';
checkAuth(['student']);

if (!isset($_GET['id'])) {
  header("Location: random_exams.php");
  exit();
}

$exam_id = (int) $_GET['id'];
$student_id = $_SESSION['user']['id'];

// جلب بيانات الامتحان
$exam = $conn->query("
  SELECT re.*, s.stuname 
  FROM random_exams re
  JOIN student s ON re.student_id = s.student_id
  WHERE re.re_id = $exam_id AND re.student_id = $student_id
")->fetch_assoc();

if (!$exam) {
  $_SESSION['error'] = "لا يوجد امتحان بهذا الرقم";
  header("Location: random_exams.php");
  exit();
}

// جلب الأسئلة والإجابات
$questions = $conn->query("
  SELECT q.*, a.selected_number, a.is_correct
  FROM random_exam_questions q
  LEFT JOIN random_exam_answers a ON q.question_id = a.question_id AND a.exam_id = $exam_id
  WHERE q.exam_id = $exam_id
  ORDER BY q.question_id
")->fetch_all(MYSQLI_ASSOC);

include '../header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow">
        <div class="card-header bg-info text-white">
          <h4 class="mb-0">
            <i class="fas fa-info-circle"></i> تفاصيل الامتحان
            <span
              class="badge bg-<?= $exam['level'] == 'easy' ? 'success' : ($exam['level'] == 'medium' ? 'warning' : 'danger') ?> float-end">
              <?= $exam['level'] == 'easy' ? 'سهل' : ($exam['level'] == 'medium' ? 'متوسط' : 'صعب') ?>
            </span>
          </h4>
        </div>
        <div class="card-body">
          <div class="row mb-4">
            <div class="col-md-4">
              <p><strong>الطالب:</strong> <?= htmlspecialchars($exam['stuname']) ?></p>
            </div>
            <div class="col-md-4">
              <p><strong>تاريخ الامتحان:</strong> <?= date('Y/m/d H:i', strtotime($exam['completed_at'])) ?></p>
            </div>
            <div class="col-md-4">
              <p><strong>المستوى:</strong>
                <span
                  class="badge bg-<?= $exam['level'] == 'easy' ? 'success' : ($exam['level'] == 'medium' ? 'warning' : 'danger') ?>">
                  <?= $exam['level'] == 'easy' ? 'سهل' : ($exam['level'] == 'medium' ? 'متوسط' : 'صعب') ?>
                </span>
              </p>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-4">
              <p><strong>عدد الأسئلة:</strong> <?= $exam['total_questions'] ?></p>
            </div>
            <div class="col-md-4">
              <p><strong>الإجابات الصحيحة:</strong> <?= $exam['correct_answers'] ?></p>
            </div>
            <div class="col-md-4">
              <p><strong>النسبة المئوية:</strong>
                <span
                  class="badge bg-<?= $exam['score'] >= 80 ? 'success' : ($exam['score'] >= 50 ? 'warning' : 'danger') ?>">
                  <?= round($exam['score']) ?>%
                </span>
              </p>
            </div>
          </div>

          <div class="progress mb-4" style="height: 30px;">
            <div
              class="progress-bar bg-<?= $exam['score'] >= 80 ? 'success' : ($exam['score'] >= 50 ? 'warning' : 'danger') ?>"
              style="width: <?= $exam['score'] ?>%">
              <?= round($exam['score']) ?>%
            </div>
          </div>

          <h5 class="mb-3"><i class="fas fa-list-ol"></i> الأسئلة والإجابات</h5>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>السؤال</th>
                  <th>إجابتك</th>
                  <th>الإجابة الصحيحة</th>
                  <th>الحالة</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($questions as $i => $q): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= $q['num1'] ?>   <?= $q['operation'] ?>   <?= $q['num2'] ?> = ؟</td>
                    <td><?= $q['selected_number'] ?? '-' ?></td>
                    <td><?= $q['correct_answer'] ?></td>
                    <td>
                      <span class="badge bg-<?= $q['is_correct'] ? 'success' : 'danger' ?>">
                        <?= $q['is_correct'] ? 'صحيح' : 'خطأ' ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="text-center mt-4">
            <a href="random_exams.php" class="btn btn-primary">
              <i class="fas fa-arrow-left"></i> العودة إلى الامتحانات العشوائية
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>