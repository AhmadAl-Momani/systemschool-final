<?php
require_once '../config.php';

// التحقق من صلاحية الدخول
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'student') {
  redirect('login.php');
}

$student_id = $_SESSION['user']['id'];

// جلب معلومات الطالب
$student = $conn->query("
    SELECT s.*, c.grade_level, c.class_group
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = $student_id
")->fetch_assoc();

$class_id = $student['class_id'];

// جلب عدد أوراق العمل المتاحة
$worksheets_count = $conn->query("
    SELECT COUNT(*)
    FROM worksheets
    WHERE class_id = $class_id
")->fetch_row()[0];

// جلب عدد الامتحانات القادمة
$upcoming_exams = $conn->query("
    SELECT COUNT(*)
    FROM exams
    WHERE class_id = $class_id
      AND start_time > NOW()
")->fetch_row()[0];

// جلب آخر النتائج
$latest_results = $conn->query("
    (SELECT 
        'exam' AS type,
        e.title AS name,
        er.total_score AS mark,
        er.completed_at AS date
     FROM exam_results er
     JOIN exams e ON er.exam_id = e.exam_id
     WHERE er.student_id = $student_id
     ORDER BY er.completed_at DESC
     LIMIT 3)
    
    UNION ALL
    
    (SELECT
        'random' AS type,
        'اختبار عشوائي' AS name,
        re.score AS mark,
        re.completed_at AS date
     FROM random_exams re
     WHERE re.student_id = $student_id
     ORDER BY re.completed_at DESC
     LIMIT 3)
     
    ORDER BY date DESC
    LIMIT 3
");

// جلب أحدث أوراق العمل
$recent_ws = $conn->query("
    SELECT w.worksheet_id, w.title, t.tname, w.upload_date
    FROM worksheets w
    JOIN teacher t ON w.teacher_id = t.teacher_id
    WHERE w.class_id = $class_id
    ORDER BY w.upload_date DESC
    LIMIT 3
");

// نصيحة اليوم
$tips = [
    "النجاح هو مجموع جهود صغيرة تتكرر يوماً بعد يوم.",
    "لا تتوقف حتى تصبح فخوراً بنفسك.",
    "العلم نور، والجهل ظلام.. استمر في القراءة!",
    "تنظيم الوقت هو نصف النجاح، ابدأ بجدولك اليوم.",
    "كل خطوة صغيرة تقربك من حلمك الكبير."
];
$daily_tip = $tips[array_rand($tips)];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة الطالب - الرئيسية</title>
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

        /* Navbar Styling - Inspired by Login Page */
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

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e293b 100%);
            border-radius: 0 0 50px 50px;
            padding: 5rem 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: -4rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -5%;
            width: 300px;
            height: 300px;
            background: rgba(251, 191, 36, 0.05);
            border-radius: 50%;
        }

        .hero-content h1 {
            font-size: 3.2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--accent-gold);
        }

        /* Stats Cards */
        .stat-card {
            background: var(--card-bg);
            border: none;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .stat-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-blue);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }

        .stat-card:nth-child(2) .stat-icon { background: rgba(251, 191, 36, 0.1); color: var(--accent-gold); }
        .stat-card:nth-child(3) .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        /* Sections */
        .content-box {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
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
        }

        .custom-table td:first-child { border-radius: 0 15px 15px 0; }
        .custom-table td:last-child { border-radius: 15px 0 0 15px; }

        .score-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .score-high { background: #dcfce7; color: #166534; }
        .score-low { background: #fee2e2; color: #991b1b; }

        /* Worksheet Item */
        .ws-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 1.5rem;
            background: var(--bg-soft);
            border-radius: 20px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .ws-item:hover {
            background: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transform: translateX(-10px);
        }

        .ws-icon {
            width: 55px;
            height: 55px;
            background: var(--primary-dark);
            color: var(--accent-gold);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        /* Tip Box */
        .tip-container {
            background: var(--accent-gold);
            color: var(--primary-dark);
            padding: 2rem;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 2rem;
            box-shadow: 0 15px 30px rgba(251, 191, 36, 0.2);
        }

        /* Floating Profile */
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
            .hero-section h1 { font-size: 2.2rem; }
            .navbar { padding: 1rem; }
        }
    </style>
</head>
<body>

    <!-- Loader -->
    <div id="loader">
        <div class="loader-circle"></div>
        <h4 class="mt-4 text-white fw-bold animate__animated animate__pulse animate__infinite">جاري التحميل...</h4>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap fa-lg"></i>
                <span>أكاديميتي</span>
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-th-large"></i> الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="worksheets.php"><i class="fas fa-file-pdf"></i> أوراق العمل</a></li>
                    <li class="nav-item"><a class="nav-link" href="exams.php"><i class="fas fa-pen-nib"></i> الامتحانات</a></li>
                    <li class="nav-item"><a class="nav-link" href="random_exams.php"><i class="fas fa-dice"></i> اختبارات عشوائية</a></li>
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

    <!-- Hero -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 hero-content animate__animated animate__fadeInLeft">
                    <h1>مرحباً بك، <?php echo explode(' ', $student['stuname'])[0]; ?>! ✨</h1>
                    <p class="lead text-white-50 mb-4">أهلاً بك في منصتك التعليمية المتكاملة. كل ما تحتاجه للنجاح في مكان واحد.</p>
                    <div class="d-flex gap-3">
                        <a href="exams.php" class="btn btn-warning btn-lg rounded-pill px-5 fw-bold">ابدأ الدراسة الآن</a>
                        <a href="#latest" class="btn btn-outline-light btn-lg rounded-pill px-4">آخر التحديثات</a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center animate__animated animate__fadeInRight">
                    <i class="fas fa-user-graduate fa-10x text-white opacity-10"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="margin-top: -2rem;">
        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-school"></i></div>
                    <h4 class="fw-bold"><?php echo $student['grade_level']; ?></h4>
                    <p class="text-muted mb-0">صفك الدراسي</p>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <h4 class="fw-bold"><?php echo $worksheets_count; ?></h4>
                    <p class="text-muted mb-0">أوراق عمل متاحة</p>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h4 class="fw-bold"><?php echo $upcoming_exams; ?></h4>
                    <p class="text-muted mb-0">امتحانات قادمة</p>
                </div>
            </div>
        </div>

        <div class="row" id="latest">
            <!-- Results -->
            <div class="col-lg-8 mb-4">
                <div class="content-box h-100 animate__animated animate__fadeInLeft">
                    <div class="section-title">
                        <i class="fas fa-award"></i>
                        <span>آخر النتائج المحققة</span>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <tbody>
                                <?php while($row = $latest_results->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo $row['name']; ?></div>
                                        <small class="text-muted"><?php echo ($row['type'] == 'exam' ? 'امتحان صفي' : 'اختبار عشوائي'); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="score-badge <?php echo $row['mark'] >= 50 ? 'score-high' : 'score-low'; ?>">
                                            <?php echo $row['mark']; ?>%
                                        </span>
                                    </td>
                                    <td class="text-end text-muted small">
                                        <?php echo date('Y-m-d', strtotime($row['date'])); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-4">
                        <a href="results.php" class="btn btn-link text-decoration-none fw-bold text-primary">عرض جميع النتائج <i class="fas fa-arrow-left ms-1"></i></a>
                    </div>
                </div>
            </div>

            <!-- Worksheets -->
            <div class="col-lg-4 mb-4">
                <div class="content-box h-100 animate__animated animate__fadeInRight">
                    <div class="section-title">
                        <i class="fas fa-file-download"></i>
                        <span>أحدث أوراق العمل</span>
                    </div>
                    <?php while($ws = $recent_ws->fetch_assoc()): ?>
                    <a href="download_worksheet.php?id=<?php echo $ws['worksheet_id']; ?>" class="ws-item">
                        <div class="ws-icon"><i class="fas fa-file-pdf"></i></div>
                        <div>
                            <div class="fw-bold small"><?php echo $ws['title']; ?></div>
                            <div style="font-size: 11px;" class="text-muted">أ. <?php echo $ws['tname']; ?></div>
                        </div>
                    </a>
                    <?php endwhile; ?>
                    <div class="text-center mt-4">
                        <a href="worksheets.php" class="btn btn-dark rounded-pill px-4 w-100">تصفح الكل</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tip Box -->
        <div class="tip-container animate__animated animate__fadeInUp">
            <div class="display-4"><i class="fas fa-lightbulb"></i></div>
            <div>
                <h5 class="fw-bold mb-1">نصيحة اليوم لك:</h5>
                <p class="mb-0 opacity-75"><?php echo $daily_tip; ?></p>
            </div>
        </div>

        <footer class="py-5 text-center text-muted">
            <p>© 2026 أكاديميتي - جميع الحقوق محفوظة</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Loader handling
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader');
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 600);
            }, 800);
        });

        // Scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.content-box').forEach(box => observer.observe(box));
    </script>
</body>
</html>
