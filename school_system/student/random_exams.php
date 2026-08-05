<?php
require_once '../config.php';
checkAuth(['student']);

// إذا كان هناك مستوى محدد في الرابط، نبدأ الامتحان مباشرة
if (isset($_GET['level'])) {
  $level = $_GET['level'];
  if (in_array($level, ['easy', 'medium', 'hard'])) {
    header("Location: start_random_exam.php?level=$level");
    exit();
  }
}

$student_id = $_SESSION['user']['id'];

// جلب معلومات الطالب
$student = $conn->query("
    SELECT s.*, c.grade_level, c.class_group
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = $student_id
")->fetch_assoc();

// جلب جميع الامتحانات العشوائية
$exam_history = $conn->query("
    SELECT 
        re.re_id,
        re.level,
        re.grade_level,
        re.completed_at,
        re.total_questions,
        re.correct_answers,
        re.score
    FROM random_exams re
    WHERE re.student_id = $student_id
    ORDER BY re.completed_at DESC
");

// التحقق من وجود نتائج حديثة في الجلسة
$show_results = false;
$results = [];
if (isset($_SESSION['exam_results'])) {
  $show_results = true;
  $results = $_SESSION['exam_results'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الامتحانات العشوائية - بوابة الطالب</title>
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

        /* Level Cards */
        .level-card {
            background: white;
            border: none;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            text-align: center;
            position: relative;
        }

        .level-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .level-card-header {
            padding: 2rem;
            color: white;
        }

        .bg-easy { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .bg-medium { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .bg-hard { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

        .level-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .level-card-body {
            padding: 2rem;
        }

        .level-features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
            color: #64748b;
        }

        .level-features li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .level-features li i {
            color: var(--accent-gold);
        }

        .btn-start-exam {
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 800;
            text-transform: uppercase;
            transition: all 0.3s ease;
            width: 100%;
            border: none;
        }

        .btn-easy { background: #10b981; color: white; }
        .btn-medium { background: #f59e0b; color: white; }
        .btn-hard { background: #ef4444; color: white; }

        .btn-start-exam:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        /* History Section */
        .content-box {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-top: 3rem;
        }

        .section-title {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--primary-dark);
        }

        .section-title i {
            color: var(--accent-gold);
        }

        /* Custom Table */
        .custom-table {
            width: 100%;
            border-spacing: 0 12px;
            border-collapse: separate;
        }

        .custom-table tr {
            background: var(--bg-soft);
            transition: all 0.3s ease;
        }

        .custom-table tr:hover {
            transform: scale(1.01);
            background: #e2e8f0;
        }

        .custom-table td {
            padding: 1.2rem;
            border: none;
            text-align: center;
            vertical-align: middle;
        }

        .custom-table td:first-child { border-radius: 0 15px 15px 0; }
        .custom-table td:last-child { border-radius: 15px 0 0 15px; }

        /* Progress Bar */
        .progress-wrapper {
            height: 12px;
            background: #e2e8f0;
            border-radius: 50px;
            overflow: hidden;
            width: 100px;
            margin: 0 auto;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 50px;
        }

        /* Profile Chip */
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

        /* Result Alert */
        .result-alert {
            background: var(--primary-dark);
            color: white;
            border-radius: 25px;
            padding: 2rem;
            margin-bottom: 3rem;
            border-right: 8px solid var(--accent-gold);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .result-alert { flex-direction: column; text-align: center; gap: 20px; }
        }
    </style>
</head>
<body>

    <!-- Loader -->
    <div id="loader">
        <div class="loader-circle"></div>
        <h4 class="mt-4 text-white fw-bold animate__animated animate__pulse animate__infinite">جاري تجهيز التحدي...</h4>
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
                    <li class="nav-item"><a class="nav-link active" href="random_exams.php"><i class="fas fa-dice"></i> اختبارات عشوائية</a></li>
                    <li class="nav-item"><a class="nav-link" href="results.php"><i class="fas fa-chart-bar"></i> نتائجي</a></li>
                </ul>
                   <div class="d-flex align-items-center gap-2">
                    <div class="profile-chip">
                        <div class="text-end text-white d-none d-md-block">
                            <div class="fw-bold small"><?php echo $student['stuname']; ?></div>
                            <div style="font-size: 10px; color: var(--accent-gold);"><?php echo $student['grade_level']; ?></div>
                        </div>
                        <img src="../uploads/students/<?php echo !empty($student['profile_pic']) ? $student['profile_pic'] : 'male-student.png'; ?>" alt="Profile">
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
            <h1>تحدي الذكاء العشوائي 🎲</h1>
            <p class="lead text-white-50">اختر مستواك المفضل وابدأ في تدريب عقلك الآن!</p>
        </div>
    </div>

    <div class="container mb-5">
        
        <!-- Last Result Alert -->
        <?php if ($show_results): ?>
            <div class="result-alert animate__animated animate__bounceIn">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fas fa-trophy text-warning me-2"></i> نتيجة آخر تحدي: <?php echo round($results['score']); ?>%</h4>
                    <p class="mb-0 opacity-75">لقد أجبت على <?php echo $results['correct_answers']; ?> من أصل <?php echo $results['total_questions']; ?> بشكل صحيح. أحسنت!</p>
                </div>
                <a href="random_exam_result.php" class="btn btn-warning rounded-pill px-4 fw-bold">عرض التفاصيل</a>
            </div>
        <?php endif; ?>

        <!-- Level Cards -->
        <div class="row g-4">
            <div class="col-lg-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="level-card">
                    <div class="level-card-header bg-easy">
                        <i class="fas fa-smile level-icon"></i>
                        <h3 class="fw-bold">المستوى السهل</h3>
                    </div>
                    <div class="level-card-body">
                        <ul class="level-features">
                            <li><i class="fas fa-check-circle"></i> عمليات الجمع والطرح</li>
                            <li><i class="fas fa-check-circle"></i> 10 أسئلة سريعة</li>
                            <li><i class="fas fa-check-circle"></i> أرقام بسيطة (0-9)</li>
                        </ul>
                        <a href="random_exams.php?level=easy" class="btn btn-start-exam btn-easy">ابدأ التحدي</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="level-card">
                    <div class="level-card-header bg-medium">
                        <i class="fas fa-meh level-icon"></i>
                        <h3 class="fw-bold">المستوى المتوسط</h3>
                    </div>
                    <div class="level-card-body">
                        <ul class="level-features">
                            <li><i class="fas fa-check-circle"></i> جمع، طرح، وضرب</li>
                            <li><i class="fas fa-check-circle"></i> 10 أسئلة متنوعة</li>
                            <li><i class="fas fa-check-circle"></i> تحدي متوسط السرعة</li>
                        </ul>
                        <a href="random_exams.php?level=medium" class="btn btn-start-exam btn-medium">ابدأ التحدي</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                <div class="level-card">
                    <div class="level-card-header bg-hard">
                        <i class="fas fa-frown level-icon"></i>
                        <h3 class="fw-bold">المستوى الصعب</h3>
                    </div>
                    <div class="level-card-body">
                        <ul class="level-features">
                            <li><i class="fas fa-check-circle"></i> جميع العمليات الحسابية</li>
                            <li><i class="fas fa-check-circle"></i> 10 أسئلة معقدة</li>
                            <li><i class="fas fa-check-circle"></i> اختبار للعباقرة فقط</li>
                        </ul>
                        <a href="random_exams.php?level=hard" class="btn btn-start-exam btn-hard">ابدأ التحدي</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="content-box animate__animated animate__fadeIn">
            <div class="section-title">
                <i class="fas fa-history"></i>
                <span>سجل تحدياتك السابقة</span>
            </div>
            
            <?php if ($exam_history->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>المستوى</th>
                                <th>التاريخ والوقت</th>
                                <th>النتيجة</th>
                                <th>التقدم</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($exam = $exam_history->fetch_assoc()): 
                                $pct = round($exam['score']);
                                $bg_color = ($exam['level'] == 'easy') ? '#10b981' : (($exam['level'] == 'medium') ? '#f59e0b' : '#ef4444');
                                $level_text = ($exam['level'] == 'easy') ? 'سهل' : (($exam['level'] == 'medium') ? 'متوسط' : 'صعب');
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-2" style="background-color: <?php echo $bg_color; ?>; color: white;">
                                            <?php echo $level_text; ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?php echo date('Y/m/d H:i', strtotime($exam['completed_at'])); ?></td>
                                    <td>
                                        <span class="fw-bold h5" style="color: <?php echo $bg_color; ?>"><?php echo $pct; ?>%</span>
                                        <div class="small text-muted"><?php echo $exam['correct_answers']; ?>/<?php echo $exam['total_questions']; ?></div>
                                    </td>
                                    <td>
                                        <div class="progress-wrapper">
                                            <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%; background-color: <?php echo $bg_color; ?>;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="random_exam_details.php?id=<?php echo $exam['re_id']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> التفاصيل
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-dice-d6 fa-4x text-light mb-3"></i>
                    <h4 class="text-muted">لم تخض أي تحدي بعد</h4>
                    <p class="text-muted">اختر مستوى من الأعلى وابدأ أول اختبار عشوائي لك!</p>
                </div>
            <?php endif; ?>
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
تعديل صفحة الطلاب لتصبح الصفحة الرئيسية بديزاين مميز - Manus