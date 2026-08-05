<?php
require_once '../config.php';
checkAuth(['teacher']);

// التحقق من وجود معرف الطالب
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: teacher_students.php");
  exit();
}

$student_id = (int) $_GET['id'];
$teacher_id = $_SESSION['user']['id'];

// جلب معلومات الطالب الأساسية
$student = $conn->query("
    SELECT s.student_id, s.stuname, s.gender, s.profile_pic, 
           c.class_name, c.class_id
    FROM student s
    LEFT JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = $student_id
")->fetch_assoc();

// التحقق من أن الطالب في صف المعلم
if (
  !$student || !$conn->query("
    SELECT 1 FROM teacher 
    WHERE teacher_id = $teacher_id 
    AND class_id = {$student['class_id']}
")->num_rows
) {
  $_SESSION['error_message'] = "لا تملك صلاحية عرض نتائج هذا الطالب";
  header("Location: teacher_students.php");
  exit();
}

// جلب نتائج الطالب في الامتحانات
$results = $conn->query("
    SELECT e.exam_id, e.title, e.subject, 
           er.total_score, er.percentage, er.completed_at
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    WHERE er.student_id = $student_id
    AND e.teacher_id = $teacher_id
    ORDER BY er.completed_at DESC
");

// جلب نتائج الطالب في الامتحانات العشوائية
$random_results = $conn->query("
    SELECT re_id, level, score, completed_at
    FROM random_exams
    WHERE student_id = $student_id
    ORDER BY completed_at DESC
");

include '../header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include 'sidebar.php'; ?>

    <div class="col-md-9 p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2>
            <i class="fas fa-chart-line"></i> نتائج الطالب:
            <?= htmlspecialchars($student['stuname']) ?>
          </h2>
          <h5 class="text-muted">
            الصف: <?= htmlspecialchars($student['class_name']) ?>
          </h5>
        </div>
        <a href="teacher_students.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> رجوع
        </a>
      </div>

      <!-- معلومات الطالب -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-2 text-center">
              <?php if (!empty($student['profile_pic'])): ?>
                <img src="../uploads/students/<?= $student['profile_pic'] ?>" class="img-thumbnail" width="120">
              <?php else: ?>
                <div
                  class="no-photo bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                  style="width:120px;height:120px;margin:0 auto;">
                  <i class="fas fa-user fa-3x"></i>
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-10">
              <div class="row">
                <div class="col-md-4">
                  <p><strong>اسم الطالب:</strong> <?= htmlspecialchars($student['stuname']) ?></p>
                </div>
                <div class="col-md-4">
                  <p><strong>الصف:</strong> <?= htmlspecialchars($student['class_name']) ?></p>
                </div>
                <div class="col-md-4">
                  <p><strong>الجنس:</strong> <?= $student['gender'] == 'male' ? 'ذكر' : 'أنثى' ?></p>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-4">
                  <p><strong>عدد الامتحانات:</strong> <?= $results->num_rows ?></p>
                </div>
                <div class="col-md-4">
                  <?php
                  $avg_percentage = $conn->query("
                                        SELECT AVG(percentage) as avg_percentage
                                        FROM exam_results
                                        WHERE student_id = $student_id
                                    ")->fetch_assoc()['avg_percentage'];
                  ?>
                  <p><strong>المعدل العام:</strong>
                    <?= $avg_percentage ? number_format($avg_percentage, 2) . '%' : '---' ?>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- نتائج الامتحانات الرسمية -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="fas fa-clipboard-list"></i> نتائج الامتحانات الرسمية
          </h5>
        </div>
        <div class="card-body">
          <?php if ($results->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead class="table-dark">
                  <tr>
                    <th>#</th>
                    <th>اسم الامتحان</th>
                    <th>المادة</th>
                    <th>الدرجة</th>
                    <th>النسبة</th>
                    <th>التاريخ</th>
                    <th>التفاصيل</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $count = 1;
                  while ($result = $results->fetch_assoc()): ?>
                    <tr>
                      <td><?= $count++ ?></td>
                      <td><?= htmlspecialchars($result['title']) ?></td>
                      <td><?= htmlspecialchars($result['subject']) ?></td>
                      <td><?= $result['total_score'] ?></td>
                      <td>
                        <div class="progress" style="height: 25px;">
                          <div class="progress-bar <?= $result['percentage'] >= 50 ? 'bg-success' : 'bg-danger' ?>"
                            role="progressbar" style="width: <?= $result['percentage'] ?>%"
                            aria-valuenow="<?= $result['percentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $result['percentage'] ?>%
                          </div>
                        </div>
                      </td>
                      <td><?= date('Y-m-d', strtotime($result['completed_at'])) ?></td>
                      <td>
                        <a href="exam_details.php?exam_id=<?= $result['exam_id'] ?>&student_id=<?= $student_id ?>"
                          class="btn btn-sm btn-info">
                          <i class="fas fa-eye"></i> عرض
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">
              لا توجد نتائج امتحانات مسجلة لهذا الطالب بعد
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- نتائج الامتحانات العشوائية -->
      <div class="card">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0">
            <i class="fas fa-random"></i> نتائج الامتحانات العشوائية
          </h5>
        </div>
        <div class="card-body">
          <?php if ($random_results->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead class="table-dark">
                  <tr>
                    <th>#</th>
                    <th>المستوى</th>
                    <th>الدرجة</th>
                    <th>التاريخ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $count = 1;
                  while ($result = $random_results->fetch_assoc()): ?>
                    <tr>
                      <td><?= $count++ ?></td>
                      <td>
                        <span class="badge bg-<?=
                          $result['level'] == 'easy' ? 'success' :
                          ($result['level'] == 'medium' ? 'warning' : 'danger')
                          ?>">
                          <?= $result['level'] == 'easy' ? 'سهل' :
                            ($result['level'] == 'medium' ? 'متوسط' : 'صعب') ?>
                        </span>
                      </td>
                      <td><?= $result['score'] ?></td>
                      <td><?= date('Y-m-d', strtotime($result['completed_at'])) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">
              لا توجد نتائج امتحانات عشوائية مسجلة لهذا الطالب بعد
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- رسوم بيانية -->
      <div class="card mt-4">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="fas fa-chart-pie"></i> تحليل النتائج
          </h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <canvas id="performanceChart" height="250"></canvas>
            </div>
            <div class="col-md-6">
              <canvas id="subjectChart" height="250"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // رسم بياني لأداء الطالب
  const ctx1 = document.getElementById('performanceChart').getContext('2d');
  const performanceChart = new Chart(ctx1, {
    type: 'line',
    data: {
      labels: [
        <?php
        $dates = $conn->query("
                SELECT DATE(completed_at) as date 
                FROM exam_results 
                WHERE student_id = $student_id 
                ORDER BY completed_at
            ");
        while ($date = $dates->fetch_assoc()) {
          echo "'" . $date['date'] . "',";
        }
        ?>
      ],
      datasets: [{
        label: 'النسبة المئوية',
        data: [
          <?php
          $percentages = $conn->query("
                    SELECT percentage 
                    FROM exam_results 
                    WHERE student_id = $student_id 
                    ORDER BY completed_at
                ");
          while ($perc = $percentages->fetch_assoc()) {
            echo $perc['percentage'] . ",";
          }
          ?>
        ],
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'تطور أداء الطالب',
          font: {
            size: 16
          }
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          title: {
            display: true,
            text: 'النسبة المئوية'
          }
        }
      }
    }
  });

  // رسم بياني للمواد
  const ctx2 = document.getElementById('subjectChart').getContext('2d');
  const subjectChart = new Chart(ctx2, {
    type: 'bar',
    data: {
      labels: [
        <?php
        $subjects = $conn->query("
                SELECT subject, AVG(percentage) as avg_percentage
                FROM exam_results
                WHERE student_id = $student_id
                GROUP BY subject
            ");
        while ($subject = $subjects->fetch_assoc()) {
          echo "'" . $subject['subject'] . "',";
        }
        ?>
      ],
      datasets: [{
        label: 'المعدل حسب المادة',
        data: [
          <?php
          $subjects = $conn->query("
                    SELECT subject, AVG(percentage) as avg_percentage
                    FROM exam_results
                    WHERE student_id = $student_id
                    GROUP BY subject
                ");
          while ($subject = $subjects->fetch_assoc()) {
            echo number_format($subject['avg_percentage'], 2) . ",";
          }
          ?>
        ],
        backgroundColor: [
          'rgba(255, 99, 132, 0.7)',
          'rgba(54, 162, 235, 0.7)',
          'rgba(255, 206, 86, 0.7)',
          'rgba(75, 192, 192, 0.7)',
          'rgba(153, 102, 255, 0.7)'
        ],
        borderColor: [
          'rgba(255, 99, 132, 1)',
          'rgba(54, 162, 235, 1)',
          'rgba(255, 206, 86, 1)',
          'rgba(75, 192, 192, 1)',
          'rgba(153, 102, 255, 1)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'الأداء حسب المادة',
          font: {
            size: 16
          }
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          title: {
            display: true,
            text: 'المعدل'
          }
        }
      }
    }
  });
</script>

<?php include '../footer.php'; ?>