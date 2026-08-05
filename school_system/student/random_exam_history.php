<?php
require_once '../config.php';
checkAuth(['student']);

$student_id = $_SESSION['user']['id'];

// جلب سجل الامتحانات العشوائية
$exam_history = $conn->query("
    SELECT * FROM random_exams
    WHERE student_id = $student_id
    ORDER BY completed_at DESC
");

include '../header.php';
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 p-3 bg-light">
      <div class="sticky-top" style="top: 20px;">
        <div class="list-group">
          <a href="dashboard.php" class="list-group-item list-group-item-action">
            <i class="fas fa-home"></i> الرئيسية
          </a>
          <a href="worksheets.php" class="list-group-item list-group-item-action">
            <i class="fas fa-file-alt"></i> أوراق العمل
          </a>
          <a href="exams.php" class="list-group-item list-group-item-action">
            <i class="fas fa-clipboard-list"></i> الامتحانات
          </a>
          <a href="random_exams.php" class="list-group-item list-group-item-action active">
            <i class="fas fa-random"></i> الامتحانات العشوائية
          </a>
          <a href="results.php" class="list-group-item list-group-item-action">
            <i class="fas fa-chart-line"></i> النتائج
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-9 p-4">
      <h2><i class="fas fa-history"></i> سجل الامتحانات العشوائية</h2>
      <hr>

      <?php if ($exam_history->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>المستوى</th>
                <th>التاريخ</th>
                <th>الإجابات الصحيحة</th>
                <th>النسبة</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php $counter = 1;
              while ($exam = $exam_history->fetch_assoc()):
                $percentage = ($exam['correct_answers'] / $exam['total_questions']) * 100;
                $level_class = [
                  'easy' => 'success',
                  'medium' => 'warning',
                  'hard' => 'danger'
                ];
                ?>
                <tr>
                  <td><?= $counter++ ?></td>
                  <td>
                    <span class="badge bg-<?= $level_class[$exam['level']] ?>">
                      <?= $exam['level'] === 'easy' ? 'سهل' :
                        ($exam['level'] === 'medium' ? 'متوسط' : 'صعب') ?>
                    </span>
                  </td>
                  <td><?= date('Y-m-d H:i', strtotime($exam['completed_at'])) ?></td>
                  <td><?= $exam['correct_answers'] ?> / <?= $exam['total_questions'] ?></td>
                  <td>
                    <div class="progress" style="height: 25px;">
                      <div class="progress-bar bg-<?= $level_class[$exam['level']] ?>" role="progressbar"
                        style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0"
                        aria-valuemax="100">
                        <?= round($percentage) ?>%
                      </div>
                    </div>
                  </td>
                  <td>
                    <a href="random_exam_result.php?id=<?= $exam['re_id'] ?>" class="btn btn-sm btn-info">
                      <i class="fas fa-eye"></i> التفاصيل
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info">لا توجد امتحانات عشوائية سابقة</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>