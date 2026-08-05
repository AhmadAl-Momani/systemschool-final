<?php
require_once '../config.php';
checkAuth(['parent']);

$result_id = (int) $_GET['id'];
$parent_id = $_SESSION['user']['id'];

// التحقق من أن النتيجة تخص أحد أبناء ولي الأمر
$check = $conn->query("
    SELECT 1 FROM exam_results er
    JOIN parent_student ps ON er.student_id = ps.student_id
    WHERE er.result_id = $result_id AND ps.parent_id = $parent_id
");

if ($check->num_rows == 0) {
  $_SESSION['error_message'] = "غير مسموح بالوصول لهذه الصفحة";
  header("Location: dashboard.php");
  exit();
}

// جلب معلومات النتيجة
$result = $conn->query("
    SELECT er.*, e.title, e.subject, s.stuname
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    JOIN student s ON er.student_id = s.student_id
    WHERE er.result_id = $result_id
")->fetch_assoc();

// جلب الإجابات التفصيلية
$answers = $conn->query("
    SELECT ea.*, eq.question_text, eq.question_type, eq.points
    FROM exam_answers ea
    JOIN exam_questions eq ON ea.question_id = eq.question_id
    WHERE ea.result_id = $result_id
");

include '../header.php';
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 p-3 bg-light">
      <?php include 'sidebar.php'; ?>
    </div>

    <div class="col-md-9 p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-check"></i> نتيجة الامتحان: <?= $result['title'] ?></h2>
        <a href="results.php?student_id=<?= $result['student_id'] ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> العودة للنتائج
        </a>
      </div>

      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-info text-white">
              <h5 class="mb-0">معلومات النتيجة</h5>
            </div>
            <div class="card-body">
              <table class="table table-bordered">
                <tr>
                  <th>الطالب</th>
                  <td><?= $result['stuname'] ?></td>
                </tr>
                <tr>
                  <th>الامتحان</th>
                  <td><?= $result['title'] ?></td>
                </tr>
                <tr>
                  <th>المادة</th>
                  <td><?= $result['subject'] ?></td>
                </tr>
                <tr>
                  <th>الدرجة</th>
                  <td><?= $result['total_score'] ?></td>
                </tr>
                <tr>
                  <th>النسبة</th>
                  <td>
                    <span class="badge bg-<?= $result['percentage'] >= 50 ? 'success' : 'danger' ?>">
                      <?= $result['percentage'] ?>%
                    </span>
                  </td>
                </tr>
                <tr>
                  <th>التقييم</th>
                  <td>
                    <?php 
                    $score_out_of_10 = ($result['total_score'] / $result['max_score']) * 10;
                    if ($score_out_of_10 >= 0 && $score_out_of_10 <= 4) {
                        echo '<span class="badge bg-danger">ابنه ضعيف وبحاجة إلى متابعة</span>';
                    } elseif ($score_out_of_10 > 4 && $score_out_of_10 <= 7) {
                        echo '<span class="badge bg-warning">متوسط وبحاجة إلى دعم ومتابعة</span>';
                    } else {
                        echo '<span class="badge bg-success">ابنه ممتاز</span>';
                    }
                    ?>
                  </td>
                </tr>
                <tr>
                  <th>التاريخ</th>
                  <td><?= date('Y-m-d H:i', strtotime($result['completed_at'])) ?></td>
                </tr>
              </table>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">ملخص النتيجة</h5>
            </div>
            <div class="card-body text-center">
              <div class="display-2 mb-3 text-<?= $result['percentage'] >= 50 ? 'success' : 'danger' ?>">
                <?= $result['percentage'] ?>%
              </div>
              <div class="progress mb-3" style="height: 30px;">
                <div class="progress-bar bg-<?= $result['percentage'] >= 50 ? 'success' : 'danger' ?>"
                  role="progressbar" style="width: <?= $result['percentage'] ?>%"
                  aria-valuenow="<?= $result['percentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
              <p class="lead">
                <?= $result['total_score'] ?> من <?= $result['max_score'] ?>
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0">تفاصيل الإجابات</h5>
        </div>
        <div class="card-body">
          <?php while ($answer = $answers->fetch_assoc()): ?>
            <div class="mb-4 p-3 border rounded <?= $answer['is_correct'] ? 'bg-light-success' : 'bg-light-danger' ?>">
              <div class="d-flex justify-content-between align-items-start">
                <h5>سؤال <?= $answer['question_id'] ?></h5>
                <span class="badge bg-<?= $answer['is_correct'] ? 'success' : 'danger' ?>">
                  <?= $answer['is_correct'] ? 'إجابة صحيحة' : 'إجابة خاطئة' ?>
                  (<?= $answer['score'] ?> / <?= $answer['points'] ?>)
                </span>
              </div>
              <p class="lead"><?= htmlspecialchars($answer['question_text']) ?></p>

              <div class="mt-3">
                <h6>إجابة الطالب:</h6>
                <p class="<?= $answer['is_correct'] ? 'text-success' : 'text-danger' ?>">
                  <strong><?= htmlspecialchars($answer['answer']) ?></strong>
                </p>
              </div>

              <div class="mt-3">
                <strong>نوع السؤال:</strong>
                <span class="badge bg-secondary">
                  <?= $answer['question_type'] == 'true_false' ? 'صح/خطأ' : 'اختيار متعدد' ?>
                </span>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>