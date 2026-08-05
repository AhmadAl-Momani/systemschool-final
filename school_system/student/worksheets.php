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

// جلب أوراق العمل الخاصة بصف الطالب
$worksheets = $conn->query("
    SELECT w.*, t.tname as teacher_name 
    FROM worksheets w
    JOIN teacher t ON w.teacher_id = t.teacher_id
    WHERE w.class_id = $class_id
    ORDER BY w.upload_date DESC
");

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أوراق العمل - بوابة الطالب</title>
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

        /* Worksheet Card */
        .ws-card {
            background: white;
            border: none;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .ws-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .ws-card-header {
            background: var(--primary-dark);
            padding: 1.5rem;
            color: var(--accent-gold);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .ws-card-header i {
            font-size: 1.8rem;
        }

        .ws-card-body {
            padding: 2rem;
            flex-grow: 1;
        }

        .ws-title {
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
        }

        .ws-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 0.95rem;
        }

        .info-item i {
            color: var(--accent-blue);
            width: 20px;
        }

        .ws-card-footer {
            padding: 1.5rem 2rem;
            background: var(--bg-soft);
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .btn-download {
            background: var(--primary-dark);
            color: var(--accent-gold);
            border: none;
            border-radius: 15px;
            padding: 0.8rem 1.5rem;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-download:hover {
            background: var(--accent-gold);
            color: var(--primary-dark);
            transform: scale(1.02);
        }

        .status-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .empty-state i {
            font-size: 5rem;
            color: #e2e8f0;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .navbar { padding: 1rem; }
        }
    </style>
</head>
<body>

    <!-- Loader -->
    <div id="loader">
        <div class="loader-circle"></div>
        <h4 class="mt-4 text-white fw-bold animate__animated animate__pulse animate__infinite">جاري تجهيز أوراق العمل...</h4>
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
                    <li class="nav-item"><a class="nav-link active" href="worksheets.php"><i class="fas fa-file-pdf"></i> أوراق العمل</a></li>
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

    <!-- Header -->
    <div class="page-header">
        <div class="container animate__animated animate__fadeInDown">
            <h1>أوراق العمل والملفات 📚</h1>
            <p class="lead text-white-50">هنا تجد جميع المصادر التعليمية التي تحتاجها للتفوق في دراستك.</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row g-4">
            <?php if ($worksheets->num_rows > 0): ?>
                <?php while ($ws = $worksheets->fetch_assoc()): ?>
                    <?php
                    // التحقق إذا كان الطالب قد حمل الملف مسبقاً
                    $view = $conn->query("
                        SELECT downloaded_at FROM worksheet_views 
                        WHERE worksheet_id = {$ws['worksheet_id']} AND student_id = $student_id
                    ")->fetch_assoc();
                    ?>
                    <div class="col-lg-4 col-md-6 animate__animated animate__zoomIn">
                        <div class="ws-card">
                            <?php if ($view && $view['downloaded_at']): ?>
                                <div class="status-badge"><i class="fas fa-check-circle me-1"></i> تم التحميل</div>
                            <?php endif; ?>
                            
                            <div class="ws-card-header">
                                <i class="fas fa-file-pdf"></i>
                                <span class="fw-bold">ملف تعليمي</span>
                            </div>
                            
                            <div class="ws-card-body">
                                <h3 class="ws-title"><?php echo htmlspecialchars($ws['title']); ?></h3>
                                <div class="ws-info">
                                    <div class="info-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span>المعلم: <?php echo htmlspecialchars($ws['teacher_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>تاريخ الرفع: <?php echo date('Y-m-d', strtotime($ws['upload_date'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-layer-group"></i>
                                        <span>المادة: مادة الصف <?php echo $student['grade_level']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ws-card-footer">
                                <a href="download_worksheet.php?id=<?php echo $ws['worksheet_id']; ?>" class="btn-download">
                                    <i class="fas fa-cloud-download-alt"></i>
                                    تحميل الملف الآن
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 animate__animated animate__fadeIn">
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h2 class="fw-bold text-muted">لا توجد أوراق عمل حالياً</h2>
                        <p class="text-muted">سيقوم المعلمون برفع الملفات قريباً، ابقَ على اطلاع!</p>
                        <a href="dashboard.php" class="btn btn-warning rounded-pill px-5 mt-3 fw-bold">العودة للرئيسية</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="py-5 text-center text-muted">
        <p>© 2026 أكاديميتي - جميع الحقوق محفوظة</p>
    </footer>

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
    </script>
</body>
</html>
