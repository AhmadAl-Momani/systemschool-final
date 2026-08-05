<?php
require_once '../config.php';
require_once 'student_header.php';
checkAuth(['student']);

// إزالة session_start() لأنها موجودة بالفعل في student_header.php
$exam_id = $_GET['exam_id'] ?? 0;

if (!isset($_SESSION['random_exam'])) {
  die("لا يوجد امتحان نشط");
}

// زيادة عداد الأسئلة
$_SESSION['random_exam']['current_question']++;

// إذا انتهت الأسئلة
if ($_SESSION['random_exam']['current_question'] >= 10) {
  header("Location: finish_random_exam.php?exam_id=$exam_id");
  exit;
}

// جلب معلومات الطالب
$student_id = $_SESSION['user']['id'];
$student = $conn->query("
    SELECT s.*, c.class_name, c.class_group
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = $student_id
")->fetch_assoc();

// توليد سؤال جديد
$range = [
  'easy' => ['min' => 1, 'max' => 20],
  'medium' => ['min' => 1, 'max' => 50],
  'hard' => ['min' => 1, 'max' => 100]
][$_SESSION['random_exam']['level']];

$random_number = rand($range['min'], $range['max']);
$number = $conn->query("SELECT * FROM numbers WHERE number_value = $random_number")->fetch_assoc();

if (!$number) {
  $image_path = "number_$random_number.png";
  $conn->query("INSERT INTO numbers (number_value, image_path) VALUES ($random_number, '$image_path')");
  $number = ['number_value' => $random_number, 'image_path' => $image_path];
}

include '../header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الامتحان العشوائي</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="../assets/css/all.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }

    .sidebar {
      background-color: #343a40;
      color: white;
      min-height: 100vh;
    }

    .list-group-item {
      background-color: transparent;
      color: white;
      border: none;
    }

    .list-group-item:hover,
    .list-group-item.active {
      background-color: #495057;
    }

    .question-container {
      padding: 20px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    #timer {
      background-color: #dc3545;
      padding: 5px 10px;
      border-radius: 5px;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-3 sidebar p-3">
        <div class="text-center mb-4">
          <img src="../uploads/students/<?php
          echo !empty($student['profile_pic'])
            ? $student['profile_pic']
            : (($student['gender'] == 'male')
              ? 'male-student.png'
              : 'female-student.png');
          ?>" class="rounded-circle" width="100" alt="صورة الطالب">
          <h5 class="mt-2"><?php echo $student['stuname']; ?></h5>
          <p class="text-muted"><?php echo $student['class_name'] . ' ' . $student['class_group']; ?></p>
        </div>

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
            <i class="fas fa-clipboard-list"></i> الامتحانات العشوائية
          </a>
          <a href="results.php" class="list-group-item list-group-item-action">
            <i class="fas fa-chart-line"></i> النتائج
          </a>
          <a href="profile.php" class="list-group-item list-group-item-action">
            <i class="fas fa-user"></i> الملف الشخصي
          </a>
        </div>
      </div>

      <div class="col-md-9 p-4">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h4>
              الامتحان العشوائي -
              <?= $_SESSION['random_exam']['level'] === 'easy' ? 'مبتدئ' :
                ($_SESSION['random_exam']['level'] === 'medium' ? 'متوسط' : 'محترف') ?>
              (<?= $_SESSION['random_exam']['grade_level'] ?>)
            </h4>
            <div id="timer" class="float-start">15:00</div>
          </div>

          <div class="card-body">
            <div class="question-container text-center">
              <h3>ما هو هذا الرقم؟</h3>
              <img src="<?= BASE_URL ?>uploads/numbers/<?= $number['image_path'] ?>" class="img-fluid my-4"
                style="max-height: 200px;">

              <div class="row justify-content-center">
                <?php
                $options = [$number['number_value']];
                while (count($options) < 4) {
                  $rand_num = rand($range['min'], $range['max']);
                  if (!in_array($rand_num, $options)) {
                    $options[] = $rand_num;
                  }
                }
                shuffle($options);

                foreach ($options as $option): ?>
                  <div class="col-md-3 mb-3">
                    <button class="btn btn-outline-primary btn-lg w-100 answer-btn" data-number="<?= $option ?>">
                      <?= $option ?>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="progress mt-4">
              <div class="progress-bar" role="progressbar"
                style="width: <?= ($_SESSION['random_exam']['current_question'] / 10) * 100 ?>%">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/jquery-3.6.0.min.js"></script>
  <script>
    let timeLeft = <?= $_SESSION['random_exam']['duration'] - (time() - $_SESSION['random_exam']['start_time']) ?>;

    function updateTimer() {
      const minutes = Math.floor(timeLeft / 60);
      const seconds = timeLeft % 60;
      document.getElementById('timer').innerHTML = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

      if (timeLeft <= 0) {
        finishExam();
      } else {
        timeLeft--;
        setTimeout(updateTimer, 1000);
      }
    }

    function finishExam() {
      window.location.href = 'finish_random_exam.php?exam_id=<?= $exam_id ?>';
    }

    $('.answer-btn').click(function () {
      const selectedNumber = $(this).data('number');
      const correctNumber = <?= $number['number_value'] ?>;
      const isCorrect = (selectedNumber == correctNumber) ? 1 : 0;

      // إضافة تأثيرات بصرية
      $(this).removeClass('btn-outline-primary');
      if (isCorrect) {
        $(this).addClass('btn-success');
      } else {
        $(this).addClass('btn-danger');
        // إظهار الإجابة الصحيحة
        $('.answer-btn').each(function () {
          if ($(this).data('number') == correctNumber) {
            $(this).removeClass('btn-outline-primary').addClass('btn-success');
          }
        });
      }

      // تعطيل الأزرار بعد الاختيار
      $('.answer-btn').prop('disabled', true);

      // إرسال الإجابة بعد تأخير بسيط
      setTimeout(function () {
        $.ajax({
          url: 'save_random_answer.php',
          method: 'POST',
          data: {
            exam_id: <?= $exam_id ?>,
            question_index: <?= $_SESSION['random_exam']['current_question'] ?>,
            number_id: <?= $number['number_id'] ?? 0 ?>,
            selected_number: selectedNumber,
            is_correct: isCorrect
          },
          success: function () {
            window.location.href = 'next_question.php?exam_id=<?= $exam_id ?>';
          }
        });
      }, 1000);
    });

    updateTimer();
  </script>
</body>

</html>