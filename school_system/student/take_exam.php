<?php
require_once '../config.php';
checkAuth(['student']);

if (!isset($_SESSION['current_exam'])) {
  header("Location: exams.php");
  exit();
}

$exam = $_SESSION['current_exam'];
$remaining_time = ($exam['start_time'] + $exam['duration']) - time();

if ($remaining_time <= 0) {
  $_SESSION['error_message'] = "انتهى وقت الامتحان";
  header("Location: exams.php");
  exit();
}

// جلب الأسئلة مع خياراتها من قاعدة البيانات
$stmt = $conn->prepare("
    SELECT 
        q.question_id,
        q.question_text,
        q.question_type,
        q.points,
        o.option_id,
        o.option_text,
        o.is_correct
    FROM 
        exam_questions q
    LEFT JOIN 
        question_options o ON q.question_id = o.question_id
    WHERE 
        q.exam_id = ?
    ORDER BY 
        q.question_id, o.option_id
");
$stmt->bind_param("i", $exam['exam_id']);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
  $question_id = $row['question_id'];

  if (!isset($questions[$question_id])) {
    $questions[$question_id] = [
      'question_text' => $row['question_text'],
      'question_type' => $row['question_type'],
      'points' => $row['points'],
      'options' => []
    ];
  }

  if ($row['option_id']) {
    $questions[$question_id]['options'][] = [
      'option_id' => $row['option_id'],
      'option_text' => $row['option_text'],
      'is_correct' => $row['is_correct']
    ];
  }
}

$_SESSION['current_exam']['questions'] = $questions;

// جلب معلومات الطالب
$student = $conn->query("
    SELECT s.*, c.grade_level, c.class_group
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = {$_SESSION['user']['id']}
")->fetch_assoc();

// تحديد الألوان حسب الجنس (للتوافق مع النظام)
$gender = $_SESSION['gender'] ?? 'male';
if ($gender === 'female') {
  $primary_color = '#ec4899';
  $secondary_color = '#fbcfe8';
  $bg_color = '#fdf2f8';
} else {
  $primary_color = '#3b82f6';
  $secondary_color = '#dbeafe';
  $bg_color = '#eff6ff';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقديم الامتحان - <?php echo htmlspecialchars($exam['title']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo $primary_color; ?>;
            --secondary-color: <?php echo $secondary_color; ?>;
            --bg-color: <?php echo $bg_color; ?>;
            --primary-dark: #0f172a;
            --accent-gold: #fbbf24;
            --success-green: #10b981;
            --danger-red: #ef4444;
            --warning-orange: #f59e0b;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f1f5f9;
            color: var(--primary-dark);
            margin: 0;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: var(--primary-dark);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .navbar-brand {
            color: var(--accent-gold) !important;
            font-weight: 800;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Main Container */
        .exam-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Sidebar */
        .exam-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .sidebar-card {
            background: white;
            border-radius: 25px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .sidebar-card-header {
            background: var(--primary-dark);
            color: var(--accent-gold);
            padding: 1rem;
            margin: -1.5rem -1.5rem 1rem -1.5rem;
            border-radius: 25px 25px 0 0;
            font-weight: 700;
            text-align: center;
        }

        .timer-display {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-dark);
            font-family: 'monospace';
            padding: 1rem 0;
        }

        .timer-display.warning { color: var(--warning-orange); }
        .timer-display.danger { color: var(--danger-red); animation: pulse 1s infinite; }

        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }

        /* Questions Navigation */
        .questions-nav {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .question-btn {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            color: #64748b;
        }

        .question-btn:hover { border-color: var(--primary-color); background: var(--secondary-color); }
        .question-btn.answered { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .question-btn.current { border-color: var(--accent-gold); box-shadow: 0 0 15px rgba(251, 191, 36, 0.4); transform: scale(1.1); }

        /* Main Content */
        .exam-content {
            background: white;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            min-height: 600px;
            position: relative;
        }

        .exam-header {
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .exam-title {
            color: var(--primary-dark);
            font-weight: 800;
            font-size: 1.8rem;
            margin: 0;
        }

        /* Question Card */
        .question-card {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .question-card.active { display: block; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .question-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .badge-meta {
            padding: 6px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .badge-number { background: var(--primary-dark); color: var(--accent-gold); }
        .badge-points { background: #e2e8f0; color: #475569; }

        .question-text {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-dark);
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        /* Options */
        .options-grid {
            display: grid;
            gap: 1rem;
        }

        .option-item {
            position: relative;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 1.2rem 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .option-item:hover { border-color: var(--primary-color); background: white; transform: translateX(-5px); }

        .option-item input[type="radio"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .option-item.selected {
            border-color: var(--primary-color);
            background: var(--secondary-color);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.1);
        }

        .option-label {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            cursor: pointer;
            flex-grow: 1;
        }

        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f5f9;
        }

        .btn-nav {
            padding: 0.8rem 2rem;
            border-radius: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-prev { background: #e2e8f0; color: #475569; border: none; }
        .btn-next { background: var(--primary-dark); color: var(--accent-gold); border: none; }
        .btn-submit { background: var(--success-green); color: white; border: none; padding: 0.8rem 3rem; }

        .btn-nav:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-nav:disabled { opacity: 0.5; cursor: not-allowed; }

        @media (max-width: 992px) {
            .exam-layout { grid-template-columns: 1fr; }
            .exam-sidebar { position: static; }
            .questions-nav { grid-template-columns: repeat(8, 1fr); }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="fas fa-edit"></i>
                <span>نظام الامتحانات المطور</span>
            </span>
            <div class="text-white d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold small"><?php echo htmlspecialchars($student['stuname']); ?></div>
                    <div class="text-muted small" style="font-size: 0.7rem;"><?php echo htmlspecialchars($exam['subject']); ?></div>
                </div>
                <img src="<?php echo !empty($student['profile_pic']) ? '../uploads/students/'.$student['profile_pic'] : 'https://ui-avatars.com/api/?name='.urlencode($student['stuname']).'&background=fbbf24&color=0f172a'; ?>" 
                     class="rounded-circle border border-2 border-warning" width="40" height="40">
            </div>
        </div>
    </nav>

    <div class="exam-layout">
        <!-- Sidebar -->
        <aside class="exam-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-card-header">الوقت المتبقي</div>
                <div id="timer" class="timer-display">00:00:00</div>
            </div>

            <div class="sidebar-card">
                <div class="sidebar-card-header">التنقل بين الأسئلة</div>
                <div class="questions-nav">
                    <?php $i = 1; foreach ($questions as $q_id => $q): ?>
                        <button type="button" class="question-btn" id="nav-q-<?php echo $q_id; ?>" onclick="showQuestion(<?php echo $q_id; ?>)">
                            <?php echo $i++; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-danger w-100 mt-4 rounded-pill fw-bold" onclick="confirmSubmit()">
                    <i class="fas fa-paper-plane me-2"></i> إنهاء وتسليم
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="exam-content">
            <div class="exam-header">
                <h2 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h2>
                <div class="text-muted fw-bold">
                    <i class="fas fa-question-circle me-1"></i>
                    عدد الأسئلة: <?php echo count($questions); ?>
                </div>
            </div>

            <form id="examForm" action="submit_exam.php" method="POST">
                <?php $idx = 1; foreach ($questions as $q_id => $q): ?>
                    <div class="question-card" id="q-<?php echo $q_id; ?>" data-question-id="<?php echo $q_id; ?>">
                        <div class="question-meta">
                            <span class="badge-meta badge-number">سؤال رقم <?php echo $idx; ?></span>
                            <span class="badge-meta badge-points"><?php echo $q['points']; ?> نقاط</span>
                        </div>
                        
                        <div class="question-text"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>

                        <div class="options-grid">
                            <input type="hidden" name="questions[<?php echo $q_id; ?>][type]" value="<?php echo $q['question_type']; ?>">
                            <input type="hidden" name="questions[<?php echo $q_id; ?>][points]" value="<?php echo $q['points']; ?>">
                            
                            <?php if ($q['question_type'] == 'multiple_choice'): ?>
                                <?php foreach ($q['options'] as $opt): ?>
                                    <label class="option-item" onclick="markAnswered(<?php echo $q_id; ?>)">
                                        <input type="radio" name="questions[<?php echo $q_id; ?>][answer]" value="<?php echo htmlspecialchars($opt['option_text']); ?>">
                                        <span class="option-label"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <label class="option-item" onclick="markAnswered(<?php echo $q_id; ?>)">
                                    <input type="radio" name="questions[<?php echo $q_id; ?>][answer]" value="صح">
                                    <span class="option-label">صح</span>
                                </label>
                                <label class="option-item" onclick="markAnswered(<?php echo $q_id; ?>)">
                                    <input type="radio" name="questions[<?php echo $q_id; ?>][answer]" value="خطأ">
                                    <span class="option-label">خطأ</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php $idx++; endforeach; ?>

                <div class="nav-buttons">
                    <button type="button" id="prevBtn" class="btn-nav btn-prev" onclick="changeQuestion(-1)">
                        <i class="fas fa-chevron-right"></i> السابق
                    </button>
                    <button type="button" id="nextBtn" class="btn-nav btn-next" onclick="changeQuestion(1)">
                        التالي <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" id="submitBtn" class="btn-nav btn-submit d-none" onclick="confirmSubmit()">
                        <i class="fas fa-check-double"></i> إنهاء الامتحان
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentQIndex = 0;
        const questions = document.querySelectorAll('.question-card');
        const questionIds = Array.from(questions).map(q => q.dataset.questionId);

        function showQuestion(qId) {
            const index = questionIds.indexOf(qId.toString());
            if (index !== -1) {
                currentQIndex = index;
                updateDisplay();
            }
        }

        function changeQuestion(step) {
            currentQIndex += step;
            updateDisplay();
        }

        function updateDisplay() {
            questions.forEach((q, i) => {
                q.classList.toggle('active', i === currentQIndex);
                const navBtn = document.getElementById('nav-q-' + q.dataset.questionId);
                navBtn.classList.toggle('current', i === currentQIndex);
            });

            document.getElementById('prevBtn').disabled = (currentQIndex === 0);
            
            if (currentQIndex === questions.length - 1) {
                document.getElementById('nextBtn').classList.add('d-none');
                document.getElementById('submitBtn').classList.remove('d-none');
            } else {
                document.getElementById('nextBtn').classList.remove('d-none');
                document.getElementById('submitBtn').classList.add('d-none');
            }
        }

        function markAnswered(qId) {
            document.getElementById('nav-q-' + qId).classList.add('answered');
            // إضافة ستايل للخيار المختار
            const card = document.getElementById('q-' + qId);
            card.querySelectorAll('.option-item').forEach(item => {
                const radio = item.querySelector('input');
                item.classList.toggle('selected', radio.checked);
            });
        }

        // Timer Logic
        let timeLeft = <?php echo $remaining_time; ?>;
        const timerDisplay = document.getElementById('timer');

        function updateTimer() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;

            timerDisplay.textContent = 
                `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            if (timeLeft <= 300) timerDisplay.classList.add('warning');
            if (timeLeft <= 60) timerDisplay.classList.add('danger');

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                Swal.fire({
                    title: 'انتهى الوقت!',
                    text: 'سيتم تسليم إجاباتك تلقائياً الآن.',
                    icon: 'warning',
                    confirmButtonText: 'حسناً',
                    allowOutsideClick: false
                }).then(() => {
                    document.getElementById('examForm').submit();
                });
            }
            timeLeft--;
        }

        const timerInterval = setInterval(updateTimer, 1000);
        updateTimer();

        function confirmSubmit() {
            const total = questions.length;
            const answered = document.querySelectorAll('.question-btn.answered').length;
            
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: `لقد أجبت على ${answered} من أصل ${total} أسئلة.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'نعم، سلم الآن',
                cancelButtonText: 'تراجع'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('examForm').submit();
                }
            });
        }

        // Initialize first question
        updateDisplay();

        // منع إغلاق الصفحة بالخطأ
        window.onbeforeunload = function() {
            return "هل أنت متأكد من مغادرة الامتحان؟ لن يتم حفظ تقدمك إلا عند التسليم.";
        };
        
        document.getElementById('examForm').onsubmit = function() {
            window.onbeforeunload = null;
        };
    </script>
</body>
</html>
