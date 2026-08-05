<?php
require_once '../config.php';
require_once 'student_header.php';
checkAuth(['student']);

$exam_id = $_GET['exam_id'] ?? 0;

// حساب النتائج بناءً على جدول random_exam_questions
$result = $conn->query("
    SELECT 
        COUNT(*) as total_questions,
        SUM(CASE WHEN question_level = 'easy' AND grade_level = 'الصف الأول' THEN 1 ELSE 0 END) as first_grade_easy,
        SUM(CASE WHEN question_level = 'medium' AND grade_level = 'الصف الأول' THEN 1 ELSE 0 END) as first_grade_medium,
        SUM(CASE WHEN question_level = 'hard' AND grade_level = 'الصف الأول' THEN 1 ELSE 0 END) as first_grade_hard
    FROM random_exam_questions
    WHERE exam_id = $exam_id
")->fetch_assoc();

if (!$result) {
  die("حدث خطأ في حساب النتائج");
}

// حساب النسبة المئوية (هنا يمكنك تعديل طريقة الحساب حسب احتياجاتك)
$percentage = ($result['first_grade_easy'] / $result['total_questions']) * 100;

// تحديث نتيجة الامتحان في جدول random_exams
$update_query = $conn->query("
    UPDATE random_exams 
    SET 
        total_questions = {$result['total_questions']},
        score = $percentage,
        completed_at = NOW()
    WHERE re_id = $exam_id
");

if (!$update_query) {
  die("حدث خطأ في تحديث النتيجة");
}

// جلب بيانات الامتحان والطالب
$exam = $conn->query("SELECT * FROM random_exams WHERE re_id = $exam_id")->fetch_assoc();
$student = $conn->query("
    SELECT s.*, c.class_name 
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = {$exam['student_id']}
")->fetch_assoc();

include '../header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>نتيجة الامتحان العشوائي</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="../assets/css/all.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }

    .result-card {
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      margin-top: 50px;
    }

    .progress {
      height: 30px;
    }

    .progress-bar {
      font-size: 16px;
      line-height: 30px;
    }

    .result-badge {
      font-size: 1.2rem;
      padding: 10px 20px;
      margin: 10px;
    }
  </style>
</head>

<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card result-card">
          <div class="card-header bg-primary text-white text-center">
            <h3><i class="fas fa-award"></i> نتيجة الامتحان العشوائي</h3>
          </div>
          <div class="card-body text-center">
            <div class="mb-4">
              <img src="../uploads/students/<?= $student['profile_pic'] ?? 'default.png' ?>"
                class="rounded-circle border" width="120" alt="صورة الطالب">
              <h4 class="mt-3"><?= $student['stuname'] ?></h4>
              <p class="text-muted">
                <i class="fas fa-graduation-cap"></i> <?= $student['class_name'] ?>
              </p>
            </div>

            <div class="row mb-4">
              <div class="col-md-4 mb-3">
                <div class="p-3 bg-light rounded">
                  <h5>الأسئلة السهلة</h5>
                  <span class="badge bg-success result-badge">
                    <?= $result['first_grade_easy'] ?>
                  </span>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="p-3 bg-light rounded">
                  <h5>الأسئلة المتوسطة</h5>
                  <span class="badge bg-warning text-dark result-badge">
                    <?= $result['first_grade_medium'] ?>
                  </span>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="p-3 bg-light rounded">
                  <h5>الأسئلة الصعبة</h5>
                  <span class="badge bg-danger result-badge">
                    <?= $result['first_grade_hard'] ?>
                  </span>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h4>النسبة المئوية للإجابات الصحيحة</h4>
              <div class="progress">
                <div class="progress-bar progress-bar-striped bg-success" role="progressbar"
                  style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0"
                  aria-valuemax="100">
                  <?= round($percentage, 2) ?>%
                </div>
              </div>
            </div>

            <div class="d-grid gap-2 mt-4">
              <a href="random_exams.php" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-left"></i> العودة إلى قائمة الامتحانات
              </a>
              <a href="exam_details.php?exam_id=<?= $exam_id ?>" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-info-circle"></i> تفاصيل النتيجة
              </a>
            </div>
          </div>
          <div class="card-footer text-muted text-center">
            تم الانتهاء من الامتحان في: <?= date('Y-m-d H:i', strtotime($exam['completed_at'])) ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/jquery-3.6.0.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>