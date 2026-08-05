<?php
require_once '../config.php';
checkAuth(['parent']);

$parent_id = $_SESSION['user']['id'];

// جلب معلومات الأولاد - تم إصلاح الاستعلام بإزالة الأعمدة غير الموجودة (class_name, section)
$children_query = $conn->query("
    SELECT s.student_id, s.stuname, s.gender, s.profile_pic, 
           c.grade_level, c.class_group
    FROM parent_student ps
    JOIN student s ON ps.student_id = s.student_id
    JOIN class c ON s.class_id = c.class_id
    WHERE ps.parent_id = $parent_id
");

if (!$children_query) {
    die("خطأ في جلب بيانات الأبناء: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أبنائي | المنصة التعليمية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');

        :root {
            --main-bg: #f0f2f5;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --accent-color: #ff6b6b;
            --text-dark: #2d3436;
            --card-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--main-bg);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }

        /* Navbar Styling */
        .custom-navbar {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            font-weight: 600;
            color: var(--text-dark) !important;
            margin: 0 10px;
        }

        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 60px 0;
            border-radius: 0 0 50px 50px;
            margin-bottom: -40px;
        }

        /* Student Card */
        .student-card {
            background: white;
            border: none;
            border-radius: 25px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            text-align: center;
            padding: 30px;
            height: 100%;
        }

        .student-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .student-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid #f8f9fa;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            object-fit: cover;
        }

        .btn-action {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            width: 100%;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .btn-view {
            background: #f0f2f5;
            color: var(--text-dark);
            border: none;
        }

        .btn-view:hover {
            background: #e2e6ea;
        }

        .btn-results {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }

        .btn-results:hover {
            opacity: 0.9;
            color: white;
        }

        /* Footer */
        .custom-footer {
            background: white;
            padding: 40px 0 20px;
            margin-top: 80px;
            border-top: 1px solid #eee;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            right: 0;
            width: 40px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-graduation-cap me-2"></i>منصتي التعليمية</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link active" href="children.php">أبنائي</a></li>
                    <li class="nav-item"><a class="nav-link" href="results.php">النتائج</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">خروج</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-center">
        <div class="container">
            <h2 class="fw-bold mb-2">قائمة الأبناء</h2>
            <p class="opacity-75">إدارة ومتابعة الملفات الدراسية لجميع أبنائك المسجلين</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                
                <h4 class="section-title">أبنائي</h4>
                
                <div class="row g-4">
                    <?php if ($children_query && $children_query->num_rows > 0): ?>
                        <?php while ($child = $children_query->fetch_assoc()): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="student-card">
                                    <img src="../uploads/students/<?= $child['profile_pic'] ?? ($child['gender'] == 'male' ? 'male-student.png' : 'female-student.png') ?>" 
                                         class="student-avatar" alt="Student">
                                    <h4 class="fw-bold mb-1"><?= $child['stuname'] ?></h4>
                                    <p class="text-muted mb-4">
                                        <i class="fas fa-school me-1"></i> <?= $child['grade_level'] ?> - <?= $child['class_group'] ?>
                                    </p>
                                    
                                    <div class="actions mt-3">
                                        <a href="child.php?id=<?= $child['student_id'] ?>" class="btn btn-action btn-view">
                                            <i class="fas fa-user-circle me-2"></i> عرض الملف الشخصي
                                        </a>
                                        <a href="results.php?student_id=<?= $child['student_id'] ?>" class="btn btn-action btn-results">
                                            <i class="fas fa-chart-bar me-2"></i> عرض النتائج
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="student-card p-5">
                                <i class="fas fa-users-slash fa-4x text-light mb-3"></i>
                                <h5 class="text-muted">لا يوجد أبناء مسجلين حالياً</h5>
                                <p class="small text-muted">يرجى التواصل مع الإدارة في حال وجود خطأ</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="custom-footer">
        <div class="container text-center">
            <p class="text-muted small mb-0">جميع الحقوق محفوظة &copy; <?= date('Y') ?> | المنصة التعليمية</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
