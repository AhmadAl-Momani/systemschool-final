<?php
require_once '../config.php';
checkAuth(['student']);

if (!isset($_SESSION['exam_results'])) {
  $_SESSION['error'] = "لا توجد نتائج متاحة";
  header("Location: random_exams.php");
  exit();
}

$results = $_SESSION['exam_results'];
include '../header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
          <h3><i class="fas fa-award"></i> نتيجة الامتحان العشوائي</h3>
        </div>
        <div class="card-body">
          <div class="text-center mb-4">
            <h2 class="display-4">
              <span
                class="text-<?= $results['score'] >= 80 ? 'success' : ($results['score'] >= 50 ? 'warning' : 'danger') ?>">
                <?= round($results['score']) ?>%
              </span>
            </h2>
            <div class="progress" style="height: 30px;">
              <div
                class="progress-bar bg-<?= $results['score'] >= 80 ? 'success' : ($results['score'] >= 50 ? 'warning' : 'danger') ?>"
                style="width: <?= $results['score'] ?>%">
              </div>
            </div>
            <p class="mt-2">
              <?= $results['correct_answers'] ?> إجابة صحيحة من <?= $results['total_questions'] ?> سؤال
            </p>
          </div>

          <div class="mb-4">
            <h5 class="text-center mb-3"><i class="fas fa-list"></i> تفاصيل الأسئلة</h5>
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
                  <?php foreach ($results['details'] as $i => $q): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td>
                        <?= $q['num1'] ?>   <?= $q['operation'] ?>   <?= $q['num2'] ?> = ؟
                      </td>
                      <td><?= $q['student_answer'] ?></td>
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
          </div>

          <div class="text-center">
            <a href="random_exams.php" class="btn btn-primary">
              <i class="fas fa-arrow-left"></i> العودة إلى الامتحانات
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// حذف نتيجة الامتحان من الجلسة بعد عرضها
unset($_SESSION['exam_results']);
include '../footer.php';
?>