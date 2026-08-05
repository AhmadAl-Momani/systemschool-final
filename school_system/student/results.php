<?php
require_once '../config.php';
checkAuth(['student']);

$student_id = $_SESSION['user']['id'];

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
    <title>نتائجي وإنجازاتي - بوابة الطالب</title>
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

        /* Tabs Styling */
        .nav-tabs {
            border: none;
            gap: 15px;
            margin-bottom: 2rem;
            justify-content: center;
        }

        .nav-tabs .nav-link {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 1rem 2rem;
            color: var(--primary-dark) !important;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-dark);
            color: var(--accent-gold) !important;
            transform: scale(1.05);
        }

        /* Content Box */
        .content-box {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
        }

        /* Custom Table */
        .custom-table {
            width: 100%;
            border-spacing: 0 12px;
            border-collapse: separate;
        }

        .custom-table thead th {
            background: transparent;
            border: none;
            color: #64748b;
            font-weight: 700;
            padding: 1rem;
            text-align: center;
        }

        .custom-table tbody tr {
            background: var(--bg-soft);
            transition: all 0.3s ease;
        }

        .custom-table tbody tr:hover {
            transform: scale(1.01);
            background: #e2e8f0;
        }

        .custom-table td {
            padding: 1.5rem;
            border: none;
            text-align: center;
            vertical-align: middle;
        }

        .custom-table td:first-child { border-radius: 0 20px 20px 0; }
        .custom-table td:last-child { border-radius: 20px 0 0 20px; }

        /* Progress Bar Styling */
        .progress-wrapper {
            position: relative;
            height: 35px;
            background: #e2e8f0;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
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
            font-size: 0.9rem;
        }

        .bg-high { background: linear-gradient(90deg, #10b981, #34d399); }
        .bg-mid { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .bg-low { background: linear-gradient(90deg, #ef4444, #f87171); }

        /* Random Exam Card */
        .random-card {
            background: var(--bg-soft);
            border-radius: 25px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .random-card:hover {
            background: white;
            border-color: var(--accent-gold);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transform: translateY(-5px);
        }

        .level-badge {
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            margin-bottom: 1rem;
            display: inline-block;
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

        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .nav-tabs { flex-direction: column; }
            .custom-table thead { display: none; }
            .custom-table td { display: block; text-align: right; padding: 0.8rem 1.5rem; }
            .custom-table td:first-child { border-radius: 20px 20px 0 0; background: var(--primary-dark); color: white; }
            .custom-table td:last-child { border-radius: 0 0 20px 20px; }
        }
    </style>
</head>
<body>

    <!-- Loader -->
    <div id="loader">
        <div class="loader-circle"></div>
        <h4 class="mt-4 text-white fw-bold animate__animated animate__pulse animate__infinite">جاري تحليل نتائجك...</h4>
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
            <h1>سجل الإنجازات والنتائج 🏆</h1>
            <p class="lead text-white-50">تتبع تقدمك الدراسي واحتفل بنجاحاتك المستمرة.</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Tabs -->
        <ul class="nav nav-tabs animate__animated animate__fadeInUp" id="resultsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams" type="button" role="tab">
                    <i class="fas fa-chalkboard-teacher me-2"></i> امتحانات المعلمين
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="random-tab" data-bs-toggle="tab" data-bs-target="#random" type="button" role="tab">
                    <i class="fas fa-dice me-2"></i> الاختبارات العشوائية
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Teacher Exams Tab -->
            <div class="tab-pane fade show active" id="exams" role="tabpanel">
                <div class="content-box animate__animated animate__fadeIn">
                    <?php
                    $results_query = "
                        SELECT er.result_id, er.exam_id, er.total_score, er.percentage, er.completed_at,
                               e.title, e.subject, t.tname as teacher_name
                        FROM exam_results er
                        JOIN exams e ON er.exam_id = e.exam_id
                        JOIN teacher t ON e.teacher_id = t.teacher_id
                        WHERE er.student_id = $student_id
                        ORDER BY er.completed_at DESC
                    ";
                    $results = $conn->query($results_query);

                    if ($results && $results->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>الامتحان والمادة</th>
                                        <th>المعلم</th>
                                        <th>التاريخ</th>
                                        <th>الدرجة</th>
                                        <th width="250">النسبة المئوية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $results->fetch_assoc()): 
                                        $pct = round($row['percentage']);
                                        $bg_class = ($pct >= 80) ? 'bg-high' : (($pct >= 50) ? 'bg-mid' : 'bg-low');
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></div>
                                                <small class="text-primary fw-bold"><?php echo htmlspecialchars($row['subject']); ?></small>
                                            </td>
                                            <td>أ. <?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                            <td class="text-muted small"><?php echo date('Y-m-d', strtotime($row['completed_at'])); ?></td>
                                            <td><span class="h5 fw-bold"><?php echo $row['total_score']; ?></span></td>
                                            <td>
                                                <div class="progress-wrapper">
                                                    <div class="progress-bar-custom <?php echo $bg_class; ?>" style="width: <?php echo $pct; ?>%">
                                                        <?php echo $pct; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x text-light mb-3"></i>
                            <h4 class="text-muted">لا توجد نتائج مسجلة حالياً</h4>
                            <p class="text-muted">ابدأ بتقديم امتحاناتك لتظهر نتائجك هنا.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Random Exams Tab -->
            <div class="tab-pane fade" id="random" role="tabpanel">
                <div class="content-box animate__animated animate__fadeIn">
                    <?php
                    $random_results_query = "
                        SELECT re.*, 
                               (SELECT COUNT(*) FROM random_exam_questions WHERE exam_id = re.re_id) as total_questions
                        FROM random_exams re
                        WHERE re.student_id = $student_id
                        ORDER BY re.completed_at DESC
                    ";
                    $random_results = $conn->query($random_results_query);

                    if ($random_results && $random_results->num_rows > 0): ?>
                        <div class="row g-4">
                            <?php while ($result = $random_results->fetch_assoc()):
                                $total = $result['total_questions'];
                                $correct = $result['correct_answers'];
                                $pct = ($total > 0) ? round(($correct / $total) * 100) : 0;
                                $bg_class = ($pct >= 80) ? 'bg-high' : (($pct >= 50) ? 'bg-mid' : 'bg-low');
                                
                                $level_class = ($result['level'] == 'easy') ? 'bg-success-subtle text-success' : 
                                              (($result['level'] == 'medium') ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger');
                                $level_text = ($result['level'] == 'easy') ? 'سهل' : (($result['level'] == 'medium') ? 'متوسط' : 'صعب');
                            ?>
                                <div class="col-md-6">
                                    <div class="random-card">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <span class="level-badge <?php echo $level_class; ?>">مستوى: <?php echo $level_text; ?></span>
                                                <h5 class="fw-bold mb-0">اختبار عشوائي</h5>
                                                <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($result['completed_at'])); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <div class="h3 fw-bold mb-0 <?php echo str_replace('bg-', 'text-', $bg_class); ?>"><?php echo $pct; ?>%</div>
                                                <small class="text-muted"><?php echo $correct; ?> من <?php echo $total; ?> صحيحة</small>
                                            </div>
                                        </div>
                                        <div class="progress-wrapper" style="height: 15px;">
                                            <div class="progress-bar-custom <?php echo $bg_class; ?>" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-dice fa-4x text-light mb-3"></i>
                            <h4 class="text-muted">لم تقم بأي اختبارات عشوائية بعد</h4>
                            <p class="text-muted">جرب مهاراتك الآن في قسم الاختبارات العشوائية!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
