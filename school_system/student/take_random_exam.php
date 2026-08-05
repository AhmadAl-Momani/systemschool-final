<?php
require_once '../config.php';
checkAuth(['student']);


if (!isset($_SESSION['random_exam'])) {
  $_SESSION['error'] = "لا يوجد امتحان نشط حاليًا";
  header("Location: random_exams.php");
  exit();
}

$exam = $_SESSION['random_exam'];

// التحقق من وجود جميع البيانات المطلوبة
if (!isset($exam['level']) || !isset($exam['questions']) || !isset($exam['current_question'])) {
  $_SESSION['error'] = "بيانات الامتحان غير مكتملة";
  header("Location: random_exams.php");
  exit();
}

// تعيين القيم الافتراضية للبيانات المطلوبة
$level = in_array($exam['level'], ['easy', 'medium', 'hard']) ? $exam['level'] : 'medium';
$current_question = $exam['current_question'] ?? 0;
$questions = $exam['questions'] ?? [];

include '../header.php';
?>

<div class="container-fluid exam-container">
  <div class="row">
    <!-- قائمة الأسئلة الجانبية -->
    <div class="col-md-3 p-0 bg-dark text-white sidebar">
      <div class="sidebar-sticky pt-3">
        <div class="text-center mb-4">
          <h4>
            <i class="fas fa-stopwatch"></i>
            <span id="exam-timer">00:10:00</span>
          </h4>
          <div class="progress mt-2" style="height: 5px;">
            <div class="progress-bar bg-info" id="time-progress" style="width: 100%"></div>
          </div>
        </div>

        <div class="questions-nav mb-3">
          <h5 class="px-3 mb-3">قائمة الأسئلة</h5>
          <div class="d-flex flex-wrap px-2">
            <?php foreach ($questions as $i => $q): ?>
              <button class="btn btn-sm m-1 question-btn 
                <?= $i == $current_question ? 'btn-primary' : 'btn-outline-primary' ?>" data-qid="<?= $i ?>">
                <?= $i + 1 ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="px-3 mt-auto">
          <button id="submit-exam" class="btn btn-success w-100 py-2">
            <i class="fas fa-paper-plane"></i> تسليم الإجابات
          </button>
        </div>
      </div>
    </div>

    <!-- أسئلة الامتحان -->
    <div class="col-md-9 p-4 main-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
          <i class="fas fa-random text-primary"></i>
          امتحان عشوائي - مستوى
          <span class="badge 
            <?= $level == 'easy' ? 'bg-success' :
              ($level == 'medium' ? 'bg-warning' : 'bg-danger') ?>">
            <?= $level == 'easy' ? 'سهل' :
              ($level == 'medium' ? 'متوسط' : 'صعب') ?>
          </span>
        </h2>
      </div>

      <form id="exam-form" action="submit_random_exam.php" method="POST">
        <?php foreach ($questions as $i => $q): ?>
          <div class="card question-card mb-4 <?= $i == $current_question ? 'active' : '' ?>" id="q<?= $i ?>">
            <div class="card-header bg-light">
              <h5 class="mb-0">سؤال <?= $i + 1 ?></h5>
            </div>
            <div class="card-body">

              <div class="question-text mb-4 text-center">
                <?php
                // استعلام لجلب بيانات الصور من جدول الأرقام
                $num1_data = $conn->query("SELECT image_path FROM numbers WHERE number_value = " . intval($q['num1']))->fetch_assoc();
                $num2_data = $conn->query("SELECT image_path FROM numbers WHERE number_value = " . intval($q['num2']))->fetch_assoc();

                // تحديد مسارات الصور مع مسار أساسي ثابت
                $base_path = '/school_system/uploads/numbers/';
                $num1_img = $base_path . basename($num1_data['image_path']);
                $num2_img = $base_path . basename($num2_data['image_path']);
                ?>

                <img src="<?= $num1_img ?>" alt="رقم <?= $q['num1'] ?>" class="number-image"
                  onerror="this.src='<?= $base_path ?>default.png'">

                <span class="operation mx-3"><?= htmlspecialchars($q['operation'] ?? '+') ?></span>

                <img src="<?= $num2_img ?>" alt="رقم <?= $q['num2'] ?>" class="number-image"
                  onerror="this.src='<?= $base_path ?>default.png'">

                <span class="mx-3">= ؟</span>
              </div>

              <input type="hidden" name="questions[<?= $i ?>][correct_answer]"
                value="<?= htmlspecialchars($q['correct_answer'] ?? '') ?>">
              <input type="hidden" name="questions[<?= $i ?>][num1]" value="<?= htmlspecialchars($q['num1'] ?? '') ?>">
              <input type="hidden" name="questions[<?= $i ?>][num2]" value="<?= htmlspecialchars($q['num2'] ?? '') ?>">
              <input type="hidden" name="questions[<?= $i ?>][operation]"
                value="<?= htmlspecialchars($q['operation'] ?? '') ?>">

              <div class="form-group answer-input">
                <label for="answer-<?= $i ?>">الإجابة:</label>
                <input type="number" step="any" class="form-control form-control-lg" id="answer-<?= $i ?>"
                  name="questions[<?= $i ?>][student_answer]" value="<?= htmlspecialchars($q['student_answer'] ?? '') ?>"
                  required>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </form>
    </div>
  </div>
</div>

<style>
  .number-image {
    height: 150px;
    vertical-align: middle;
    transition: transform 0.3s;
    border: 2px solid #eee;
    border-radius: 10px;
    padding: 5px;
    background: white;
  }

  .number-image:hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
  }

  .operation {
    font-size: 2.5rem;
    vertical-align: middle;
  }

  .exam-container {
    min-height: 100vh;
  }

  .sidebar {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  .questions-nav {
    max-height: 60vh;
    overflow-y: auto;
  }

  .question-btn {
    width: 40px;
    height: 40px;
    border-radius: 50% !important;
  }

  .question-card {
    display: none;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  }

  .question-card.active {
    display: block;
    animation: fadeIn 0.5s;
  }

  .question-text {
    font-weight: bold;
    color: #2c3e50;
  }

  .answer-input {
    max-width: 300px;
    margin: 0 auto;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>

<script>
  // التنقل بين الأسئلة
  document.querySelectorAll('.question-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const qid = this.getAttribute('data-qid');
      document.querySelectorAll('.question-card').forEach(card => card.classList.remove('active'));
      document.querySelector(`#q${qid}`).classList.add('active');
      document.querySelectorAll('.question-btn').forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline-primary');
      });
      this.classList.remove('btn-outline-primary');
      this.classList.add('btn-primary');
      document.querySelector(`#q${qid}`).scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  // مؤقت الامتحان (10 دقائق)
  let timeLeft = 10 * 60;
  const timer = document.getElementById('exam-timer');
  const progressBar = document.getElementById('time-progress');

  function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    timer.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    progressBar.style.width = `${(timeLeft / (10 * 60)) * 100}%`;
    if (timeLeft <= 0) {
      document.getElementById('exam-form').submit();
    } else {
      timeLeft--;
      setTimeout(updateTimer, 1000);
    }
  }
  updateTimer();

  // تسليم الامتحان
  document.getElementById('submit-exam').addEventListener('click', function () {
    if (confirm('هل أنت متأكد أنك تريد تسليم الإجابات؟ لا يمكنك العودة بعد التسليم.')) {
      // إضافة هذا السطر لضمان إرسال النموذج بشكل صحيح
      document.getElementById('exam-form').submit();
    }
  });

  // تمييز الأسئلة المجابة
  document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function () {
      const qid = this.id.split('-')[1];
      const btn = document.querySelector(`.question-btn[data-qid="${qid}"]`);
      if (this.value.trim() !== '') {
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
      } else {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-primary');
      }
    });
  });
</script>

<?php include '../footer.php'; ?>