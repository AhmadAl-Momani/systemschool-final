<?php
require_once '../config.php';
checkAuth(['parent']);

$parent_id = $_SESSION['user']['id'];

// جلب معلومات الأولاد
$children = $conn->query("
    SELECT s.student_id, s.stuname, s.gender, s.profile_pic, 
           c.grade_level, c.class_group
    FROM parent_student ps
    JOIN student s ON ps.student_id = s.student_id
    JOIN class c ON s.class_id = c.class_id
    WHERE ps.parent_id = $parent_id
");

// جلب آخر النتائج
$latest_results = $conn->query("
    SELECT er.*, e.title, e.subject, s.stuname
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    JOIN student s ON er.student_id = s.student_id
    JOIN parent_student ps ON s.student_id = ps.student_id
    WHERE ps.parent_id = $parent_id
    ORDER BY er.completed_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة ولي الأمر | المنصة التعليمية</title>
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
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #667eea !important;
        }

        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 60px 0;
            border-radius: 0 0 50px 50px;
            margin-bottom: -50px;
        }

        /* Card Styling */
        .glass-card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s;
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-5px);
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 25px;
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

        /* Student Avatar */
        .student-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid #eee;
            padding: 3px;
            object-fit: cover;
        }

        /* Result Badge */
        .result-badge {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Footer */
        .custom-footer {
            background: white;
            padding: 40px 0 20px;
            margin-top: 80px;
            border-top: 1px solid #eee;
        }

        .btn-primary-custom {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 12px;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        .btn-primary-custom:hover {
            opacity: 0.9;
            color: white;
        }

        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #2ecc71;
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-graduation-cap me-2"></i>منصتي التعليمية</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-link">مرحباً، <?= $_SESSION['user']['name'] ?></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="children.php">أبنائي</a></li>
                    <li class="nav-item"><a class="nav-link" href="results.php">النتائج</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-power-off"></i></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="fw-bold mb-3">بوابة ولي الأمر الذكية</h1>
            <p class="lead opacity-75">تابع رحلة أبنائك التعليمية لحظة بلحظة بكل سهولة</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container" style="margin-top: 20px;">
        <div class="row g-4">
            
            <!-- قائمة الأبناء - تصميم أفقي -->
            <div class="col-12">
                <h4 class="section-title">أبنائي المسجلين</h4>
                <div class="row g-3">
                    <?php if ($children && $children->num_rows > 0): ?>
                        <?php while ($child = $children->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="glass-card p-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploads/students/<?= $child['profile_pic'] ?? ($child['gender'] == 'male' ? 'male-student.png' : 'female-student.png') ?>" 
                                             class="student-circle me-3" alt="Student">
                                        <div>
                                            <h5 class="mb-1 fw-bold"><?= $child['stuname'] ?></h5>
                                            <span class="badge bg-light text-dark"><?= $child['grade_level'] ?></span>
                                            <span class="badge bg-light text-dark"><?= $child['class_group'] ?></span>
                                        </div>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-between align-items-center">
                                        <span class="text-muted small"><span class="status-dot"></span> نشط حالياً</span>
                                        <a href="child.php?id=<?= $child['student_id'] ?>" class="btn btn-sm btn-primary-custom">الملف الدراسي</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="glass-card p-5 text-center">
                                <i class="fas fa-user-slash fa-3x text-light mb-3"></i>
                                <p class="text-muted">لا يوجد أبناء مسجلين في حسابك حالياً</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- آخر النتائج والتنبيهات -->
            <div class="col-lg-8">
                <h4 class="section-title">آخر النتائج الدراسية</h4>
                <div class="glass-card p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">الطالب</th>
                                    <th>المادة / الاختبار</th>
                                    <th>التاريخ</th>
                                    <th class="text-center">النتيجة</th>
                                    <th class="pe-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($latest_results && $latest_results->num_rows > 0): ?>
                                    <?php while ($result = $latest_results->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="fw-bold"><?= $result['stuname'] ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= $result['subject'] ?></div>
                                                <small class="text-muted"><?= $result['title'] ?></small>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($result['completed_at'])) ?></td>
                                            <td class="text-center">
                                                <div class="result-badge mx-auto" style="background: <?= $result['percentage'] >= 50 ? '#e8f5e9' : '#ffebee' ?>; color: <?= $result['percentage'] >= 50 ? '#2e7d32' : '#c62828' ?>;">
                                                    <?= $result['percentage'] ?>%
                                                </div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="result_details.php?id=<?= $result['result_id'] ?>" class="btn btn-light btn-sm rounded-pill px-3">التفاصيل</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">لا توجد نتائج مسجلة بعد</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- إحصائيات سريعة -->
            <div class="col-lg-4">
                <h4 class="section-title">نظرة عامة</h4>
                <div class="glass-card p-4 mb-4 text-center">
                    <div class="display-5 fw-bold text-primary mb-1"><?= $children->num_rows ?></div>
                    <div class="text-muted">عدد الأبناء</div>
                </div>
                <div class="glass-card p-4 text-center" style="background: var(--primary-gradient); color: white;">
                    <i class="fas fa-bell fa-2x mb-3"></i>
                    <h5>تنبيهات النظام</h5>
                    <p class="small opacity-75">سيتم إرسال إشعارات فورية عند صدور أي نتائج جديدة لأبنائك.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="custom-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <h5 class="fw-bold mb-1">المنصة التعليمية المتكاملة</h5>
                    <p class="text-muted small mb-0">جميع الحقوق محفوظة &copy; <?= date('Y') ?></p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <a href="#" class="text-muted me-3 text-decoration-none">سياسة الخصوصية</a>
                    <a href="#" class="text-muted text-decoration-none">الدعم الفني</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
