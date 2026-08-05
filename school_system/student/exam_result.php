<?php
require_once '../config.php';
checkAuth(['student']);
// أخذ معرف الامتحان من الرابط,وأخذ معرف الطالب من الجلسة
$exam_id = (int) $_GET['id'];
$student_id = $_SESSION['user']['id'];

// استعلام معدل مع التحقق من تطابق الإجابات
//بيانات نتيجة الطالب exam_results.
//exam_results, exams, teacher. 3 tabels
$result = $conn->query("
    SELECT er.*, e.title, e.subject, e.duration, t.tname AS teacher_name,
           (SELECT SUM(points) FROM exam_questions WHERE exam_id = e.exam_id) AS max_score,
           (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.exam_id) AS total_questions
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    JOIN teacher t ON e.teacher_id = t.teacher_id
    WHERE er.exam_id = $exam_id AND er.student_id = $student_id
")->fetch_assoc();

if (!$result) {
  $_SESSION['error_message'] = "لا توجد نتيجة لهذا الامتحان";
  header("Location: exams.php");
  exit();//ذا لم توجد نتيجة لهذا الطالب في هذا الامتحان
}

// استخراج جميع إجابات الطالب مع مقارنة الصحيحة
$answers = $conn->query("
    SELECT 
        ea.*, 
        eq.question_text, 
        eq.question_type, 
        eq.points,
        eq.correct_answer,
        IF(TRIM(ea.answer) = TRIM(eq.correct_answer), 1, 0) AS is_correct,
        IF(TRIM(ea.answer) = TRIM(eq.correct_answer), eq.points, 0) AS calculated_score
    FROM exam_answers ea
    JOIN exam_questions eq ON ea.question_id = eq.question_id
    WHERE ea.result_id = {$result['result_id']}
    ORDER BY ea.question_id
");

// إعادة حساب النتائج للتأكد من صحتها
//المجموع الكلي للدرجة.
//عدد الإجابات الصحيحة والخاطئة.
//تخزين جميع الأسئلة في answers_data للعرض لاحقًا.
$total_score = 0;
$correct_answers = 0;
$wrong_answers = 0;
$answers_data = [];
while ($answer = $answers->fetch_assoc()) {
  // إصلاح مشكلة صح/خطأ: تحويل الإجابات إلى صيغة موحدة
  $student_ans = trim($answer['answer']);
  $correct_ans = trim($answer['correct_answer']);
  
  // تحويل true/false إلى صح/خطأ للمقارنة
  if ($answer['question_type'] === 'true_false') {
    if (strtolower($student_ans) === 'true' || $student_ans === 'صح') {
      $student_ans = 'صح';
    } elseif (strtolower($student_ans) === 'false' || $student_ans === 'خطأ') {
      $student_ans = 'خطأ';
    }
    
    if (strtolower($correct_ans) === 'true') {
      $correct_ans = 'صح';
    } elseif (strtolower($correct_ans) === 'false') {
      $correct_ans = 'خطأ';
    }
  }
  
  // إعادة حساب الصحة بناءً على المقارنة المصححة
  $is_correct = ($student_ans === $correct_ans) ? 1 : 0;
  $calculated_score = $is_correct ? $answer['points'] : 0;
  
  $answer['is_correct'] = $is_correct;
  $answer['calculated_score'] = $calculated_score;
  $answer['student_answer_display'] = $student_ans;
  $answer['correct_answer_display'] = $correct_ans;
  
  $answers_data[] = $answer;
  $total_score += $calculated_score;
  if ($is_correct) {
    $correct_answers++;
  } else {
    $wrong_answers++;
  }
}

// تحديث النتيجة في قاعدة البيانات إذا كانت مختلفة
$recalculated_percentage = ($result['max_score'] > 0) ? round(($total_score / $result['max_score']) * 100, 2) : 0;
if ($total_score != $result['total_score'] || $recalculated_percentage != $result['percentage']) {
  $conn->query("UPDATE exam_results 
               SET total_score = $total_score, percentage = $recalculated_percentage 
               WHERE result_id = {$result['result_id']}");
}

// جلب معلومات الطالب
$student = $conn->query("
    SELECT s.*, c.grade_level, c.class_group
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = $student_id
")->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتيجة الامتحان - بوابة الطالب</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #0f172a;
            --accent-gold: #fbbf24;
            --accent-blue: #3b82f6;
            --text-light: #f8fafc;
            --bg-soft: #f1f5f9;
            --card-bg: #ffffff;
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #ef4444;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-soft);
            color: var(--primary-dark);
            margin: 0;
            overflow-x: hidden;
        }

        /* Preloader */
        #loader {
            position: fixed;
            inset: 0;
            background: var(--primary-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.6s ease;
        }

        .loader-circle {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(251, 191, 36, 0.1);
            border-top: 8px solid var(--accent-gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* Navbar Styling */
        .navbar {
            background: var(--primary-dark);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .navbar-brand {
            color: var(--accent-gold) !important;
            font-weight: 800;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            color: var(--text-light) !important;
            font-weight: 600;
            padding: 0.8rem 1.2rem !important;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            background: rgba(251, 191, 36, 0.1);
            color: var(--accent-gold) !important;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: var(--accent-gold);
            color: var(--primary-dark) !important;
        }

        /* Header Section */
        .page-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e293b 100%);
            padding: 4rem 2rem;
            color: white;
            text-align: center;
            border-radius: 0 0 50px 50px;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .page-header h1 {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--accent-gold);
            margin-bottom: 1rem;
        }

        /* Content Box */
        .content-box {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
        }

        /* Score Display */
        .score-display {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e293b 100%);
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
        }

        .score-display .score-number {
            font-size: 4rem;
            font-weight: 800;
            color: var(--accent-gold);
            margin-bottom: 1rem;
        }

        .score-display .score-info {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Progress Bar */
        .progress-wrapper {
            position: relative;
            height: 40px;
            background: #e2e8f0;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            margin: 1.5rem 0;
        }

        .progress-bar-custom {
            height: 100%;
            border-radius: 50px;
            transition: width 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1rem;
        }

        .bg-high { background: linear-gradient(90deg, #10b981, #34d399); }
        .bg-mid { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .bg-low { background: linear-gradient(90deg, #ef4444, #f87171); }

        /* Question Card */
        .question-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 5px solid;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .question-card.correct {
            border-left-color: var(--success-green);
            background: rgba(16, 185, 129, 0.03);
        }

        .question-card.incorrect {
            border-left-color: var(--danger-red);
            background: rgba(239, 68, 68, 0.03);
        }

        .question-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .question-number {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--primary-dark);
        }

        .question-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .question-text {
            font-size: 1.1rem;
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .answer-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .answer-box {
            padding: 1rem;
            border-radius: 15px;
            background: var(--bg-soft);
            border: 2px solid transparent;
        }

        .answer-box.correct {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-green);
        }

        .answer-box.incorrect {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger-red);
        }

        .answer-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .answer-value {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .answer-box.correct .answer-value {
            color: var(--success-green);
        }

        .answer-box.incorrect .answer-value {
            color: var(--danger-red);
        }

        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e293b 100%);
            color: white;
            border: none;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .btn-back {
            background: var(--accent-gold);
            color: var(--primary-dark);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }

        .btn-back:hover {
            background: #fcd34d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(251, 191, 36, 0.3);
        }

        .profile-chip {
            background: rgba(255,255,255,0.1);
            padding: 6px 18px 6px 6px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .profile-chip img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--accent-gold);
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .score-display .score-number { font-size: 2.5rem; }
            .answer-row { grid-template-columns: 1fr; }
            .question-card-header { flex-direction: column; gap: 1rem; }
            .stats-bar { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Loader -->
    <div id="loader">
        <div class="loader-circle"></div>
        <h4 class="mt-4 text-white fw-bold animate__animated animate__pulse animate__infinite">جاري تحميل النتيجة...</h4>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap fa-lg"></i>
                <span>أكاديميتي</span>
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-th-large"></i> الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="worksheets.php"><i class="fas fa-file-pdf"></i> أوراق العمل</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php"><i class="fas fa-pen-nib"></i> الامتحانات</a></li>
                    <li class="nav-item"><a class="nav-link" href="random_exams.php"><i class="fas fa-dice"></i> اختبارات عشوائية</a></li>
                    <li class="nav-item"><a class="nav-link active" href="results.php"><i class="fas fa-chart-bar"></i> نتائجي</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <div class="profile-chip">
                        <div class="text-end text-white d-none d-md-block">
                            <div class="fw-bold small"><?php echo $student['stuname']; ?></div>
                            <div style="font-size: 10px; color: var(--accent-gold);"><?php echo $student['grade_level']; ?></div>
                        </div>
                    </div>
                    <a href="../logout.php" class="nav-link logout-btn" title="تسجيل الخروج">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="page-header">
        <div class="container animate__animated animate__fadeInDown">
            <h1>نتيجة الامتحان 📋</h1>
            <p class="lead text-white-50">تفاصيل أدائك في الامتحان</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Score Display -->
        <div class="score-display animate__animated animate__zoomIn">
            <div class="score-number"><?php echo round($recalculated_percentage); ?>%</div>
            <div class="progress-wrapper">
                <?php $bg_class = ($recalculated_percentage >= 80) ? 'bg-high' : (($recalculated_percentage >= 50) ? 'bg-mid' : 'bg-low'); ?>
                <div class="progress-bar-custom <?php echo $bg_class; ?>" style="width: <?php echo $recalculated_percentage; ?>%">
                    <?php echo round($recalculated_percentage); ?>%
                </div>
            </div>
            <div class="score-info">
                <strong><?php echo htmlspecialchars($result['title']); ?></strong> - 
                <span><?php echo htmlspecialchars($result['subject']); ?></span>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar animate__animated animate__fadeInUp">
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_score; ?></div>
                <div class="stat-label">الدرجة النهائية</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $result['max_score']; ?></div>
                <div class="stat-label">الدرجة الكاملة</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" style="color: var(--success-green);"><?php echo $correct_answers; ?></div>
                <div class="stat-label">إجابات صحيحة</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" style="color: var(--danger-red);"><?php echo $wrong_answers; ?></div>
                <div class="stat-label">إجابات خاطئة</div>
            </div>
        </div>

        <!-- Exam Info -->
        <div class="content-box animate__animated animate__fadeIn">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>المعلم:</strong> أ. <?php echo htmlspecialchars($result['teacher_name']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>تاريخ الإكمال:</strong> <?php echo date('Y-m-d H:i', strtotime($result['completed_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Questions Section -->
        <div class="content-box animate__animated animate__fadeIn">
            <h3 class="mb-4"><i class="fas fa-list"></i> تفاصيل الأسئلة والإجابات</h3>
            
            <?php
            $question_counter = 1;
            foreach ($answers_data as $answer):
                $is_correct = (bool) $answer['is_correct'];
                $score = $answer['calculated_score'];
                $student_answer = htmlspecialchars($answer['student_answer_display']);
                $correct_answer = htmlspecialchars($answer['correct_answer_display']);
                $question_text = htmlspecialchars($answer['question_text']);
            ?>
                <div class="question-card <?php echo $is_correct ? 'correct' : 'incorrect'; ?>" 
                     data-bs-toggle="modal" 
                     data-bs-target="#detailModal<?php echo $answer['question_id']; ?>">
                    
                    <div class="question-card-header">
                        <div>
                            <div class="question-number">سؤال <?php echo $question_counter++; ?></div>
                            <div class="question-text"><?php echo $question_text; ?></div>
                        </div>
                        <span class="question-badge bg-<?php echo $is_correct ? 'success' : 'danger'; ?>">
                            <?php echo $is_correct ? 'صحيح ✓' : 'خطأ ✗'; ?>
                            (<?php echo $score; ?>/<?php echo $answer['points']; ?>)
                        </span>
                    </div>

                    <div class="answer-row">
                        <div class="answer-box <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                            <div class="answer-label">
                                <i class="fas fa-user-check"></i> إجابتك:
                            </div>
                            <div class="answer-value"><?php echo $student_answer; ?></div>
                        </div>
                        <div class="answer-box correct">
                            <div class="answer-label">
                                <i class="fas fa-check-circle"></i> الإجابة الصحيحة:
                            </div>
                            <div class="answer-value"><?php echo $correct_answer; ?></div>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; font-size: 0.9rem; color: #64748b;">
                        <i class="fas fa-info-circle"></i>
                        <?php echo $answer['question_type'] === 'true_false' ? 'نوع السؤال: صح/خطأ' : 'نوع السؤال: اختيار متعدد'; ?>
                    </div>
                </div>

                <!-- Modal for Question Details -->
                <div class="modal fade" id="detailModal<?php echo $answer['question_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">تفاصيل السؤال</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2">نص السؤال:</h6>
                                    <p class="lead"><?php echo $question_text; ?></p>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger-red);">
                                            <h6 class="fw-bold mb-2"><i class="fas fa-user-check"></i> إجابتك:</h6>
                                            <p class="mb-0" style="color: var(--danger-red); font-size: 1.1rem; font-weight: 700;">
                                                <?php echo $student_answer; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded" style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--success-green);">
                                            <h6 class="fw-bold mb-2"><i class="fas fa-check-circle"></i> الإجابة الصحيحة:</h6>
                                            <p class="mb-0" style="color: var(--success-green); font-size: 1.1rem; font-weight: 700;">
                                                <?php echo $correct_answer; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2">معلومات السؤال:</h6>
                                    <ul class="list-group">
                                        <li class="list-group-item">
                                            <strong>النوع:</strong>
                                            <span class="badge bg-secondary ms-2">
                                                <?php echo $answer['question_type'] === 'true_false' ? 'صح/خطأ' : 'اختيار متعدد'; ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>الدرجات:</strong>
                                            <span class="badge bg-<?php echo $is_correct ? 'success' : 'danger'; ?> ms-2">
                                                <?php echo $score; ?> من <?php echo $answer['points']; ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>الحالة:</strong>
                                            <span class="badge bg-<?php echo $is_correct ? 'success' : 'danger'; ?> ms-2">
                                                <?php echo $is_correct ? 'إجابة صحيحة ✓' : 'إجابة خاطئة ✗'; ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Back Button -->
        <div class="text-center mb-5">
            <a href="results.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> العودة إلى النتائج
            </a>
        </div>
    </div>

    <footer class="py-5 text-center text-muted">
        <p>© 2026 أكاديميتي - جميع الحقوق محفوظة</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader');
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 600);
            }, 800);
        });
    </script>
</body>
</html>
