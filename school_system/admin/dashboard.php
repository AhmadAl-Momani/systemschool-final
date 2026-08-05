<?php
require_once '../config.php';

// التحقق من صلاحية الدخول
checkAuth(['admin']);

// جلب الإحصائيات
$students_count = $conn->query("SELECT COUNT(*) FROM student")->fetch_row()[0];
$teachers_count = $conn->query("SELECT COUNT(*) FROM teacher")->fetch_row()[0];
$classes_count = $conn->query("SELECT COUNT(*) FROM class")->fetch_row()[0];
$exams_count = $conn->query("SELECT COUNT(*) FROM stuexam")->fetch_row()[0];

// ملاحظة: تم دمج الهيدر والفوتر هنا لتصميم متكامل ومختلف كلياً
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الاحترافية | نظام المدرسة</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- CSS Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --primary-accent: #6366f1;
            --secondary-accent: #a855f7;
            --dark-text: #1e293b;
            --light-text: #64748b;
            --sidebar-width: 280px;
        }

        body {
            background: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        /* Layout Structure */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Floating Glass Style */
        .sidebar-container {
            width: var(--sidebar-width);
            padding: 20px;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .sidebar-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            height: 100%;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
        }

        .logo-box {
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 8px 15px rgba(99, 102, 241, 0.3);
        }

        .nav-menu {
            flex-grow: 1;
            padding: 10px;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: var(--light-text);
            text-decoration: none;
            border-radius: 16px;
            margin-bottom: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }

        .nav-link-custom i {
            margin-left: 15px;
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .nav-link-custom:hover {
            background: rgba(99, 102, 241, 0.08);
            color: var(--primary-accent);
            transform: translateX(-5px);
        }

        .nav-link-custom.active {
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            color: white;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }

        .nav-link-custom.active i {
            color: white;
        }

        /* Main Content Area */
        .main-content {
            flex-grow: 1;
            margin-right: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }

        /* Top Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar-circle {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-accent);
            font-weight: bold;
        }

        /* Stats Grid - Modern Cards */
        .stat-card-new {
            background: white;
            border-radius: 24px;
            padding: 25px;
            border: 1px solid #f1f5f9;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stat-card-new:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        .stat-icon-box {
            width: 55px;
            height: 55px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 20px;
        }

        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-purple { background: #f5f3ff; color: #8b5cf6; }
        .icon-green { background: #f0fdf4; color: #22c55e; }
        .icon-orange { background: #fff7ed; color: #f97316; }

        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            color: var(--dark-text);
            margin-bottom: 5px;
        }

        .stat-label-new {
            color: var(--light-text);
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Table Section */
        .content-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            margin-top: 30px;
        }

        .card-title-new {
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-modern thead th {
            background: #f8fafc;
            border: none;
            padding: 18px;
            color: var(--light-text);
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 12px;
        }

        .table-modern tbody td {
            padding: 20px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .student-row:hover {
            background: #fcfdff;
        }

        .badge-modern {
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        /* Footer */
        .footer-modern {
            margin-top: 50px;
            text-align: center;
            padding: 20px;
            color: var(--light-text);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar-container { display: none; }
            .main-content { margin-right: 0; width: 100%; }
        }

        /* Animations */
        .stagger-1 { animation: fadeInUp 0.5s both; animation-delay: 0.1s; }
        .stagger-2 { animation: fadeInUp 0.5s both; animation-delay: 0.2s; }
        .stagger-3 { animation: fadeInUp 0.5s both; animation-delay: 0.3s; }
        .stagger-4 { animation: fadeInUp 0.5s both; animation-delay: 0.4s; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <div class="sidebar-container">
        <div class="sidebar-glass animate__animated animate__fadeInRight">
            <div class="sidebar-header">
                <div class="logo-box">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h5 class="fw-bold mb-0">نظام المدرسة</h5>
            </div>

            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link-custom active">
                    <i class="fas fa-grid-2"></i> لوحة التحكم
                </a>
                <a href="classes.php" class="nav-link-custom">
                    <i class="fas fa-layer-group"></i> إدارة الصفوف
                </a>
                <a href="teachers.php" class="nav-link-custom">
                    <i class="fas fa-user-tie"></i> المعلمين
                </a>
                <a href="students.php" class="nav-link-custom">
                    <i class="fas fa-user-graduate"></i> الطلاب
                </a>
                <a href="parents.php" class="nav-link-custom">
                    <i class="fas fa-users-between-lines"></i> أولياء الأمور
                </a>
                                <a href="buses.php" class="nav-link-custom"><i class="fas fa-bus"></i> إدارة الحافلات</a>

                <a href="assignments.php" class="nav-link-custom "><i class="fas fa-tasks"></i> تعيين المعلمين</a>

                <a href="reports.php" class="nav-link-custom">
                    <i class="fas fa-chart-line"></i> التقارير
                </a>
                <a href="numbers.php" class="nav-link-custom">
                    <i class="fas fa-hashtag"></i> الأرقام
                </a>
            </div>

            <div class="p-4">
                <a href="../logout.php" class="nav-link-custom text-danger">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header animate__animated animate__fadeInDown">
            <div class="search-box d-none d-md-block">
                <h4 class="mb-0 fw-bold">أهلاً بك، مدير النظام 👋</h4>
                <p class="text-muted mb-0 small">إليك ملخص ما يحدث في مدرستك اليوم</p>
            </div>
            <div class="user-profile">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold">أدمن المدرسة</div>
                    <div class="text-muted small">مسؤول عام</div>
                </div>
                <div class="avatar-circle">AD</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-3 stagger-1">
                <div class="stat-card-new">
                    <div class="stat-icon-box icon-blue">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-number"><?php echo $students_count; ?></div>
                    <div class="stat-label-new">إجمالي الطلاب</div>
                    <div class="mt-3 small text-success">
                        <i class="fas fa-arrow-up me-1"></i> +12% هذا الشهر
                    </div>
                </div>
            </div>
            <div class="col-md-3 stagger-2">
                <div class="stat-card-new">
                    <div class="stat-icon-box icon-purple">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-number"><?php echo $teachers_count; ?></div>
                    <div class="stat-label-new">المعلمين</div>
                    <div class="mt-3 small text-muted">كادر تعليمي نشط</div>
                </div>
            </div>
            <div class="col-md-3 stagger-3">
                <div class="stat-card-new">
                    <div class="stat-icon-box icon-green">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-number"><?php echo $classes_count; ?></div>
                    <div class="stat-label-new">الصفوف الدراسية</div>
                    <div class="mt-3 small text-muted">موزعة على المجموعات</div>
                </div>
            </div>
            <div class="col-md-3 stagger-4">
                <div class="stat-card-new">
                    <div class="stat-icon-box icon-orange">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div class="stat-number"><?php echo $exams_count; ?></div>
                    <div class="stat-label-new">الامتحانات</div>
                    <div class="mt-3 small text-warning">تحت المراجعة</div>
                </div>
            </div>
        </div>

        <div class="content-card animate__animated animate__fadeInUp">
            <div class="card-title-new">
                <span><i class="fas fa-clock-rotate-left me-2 text-primary"></i> آخر الطلاب المنضمين</span>
             
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>اسم الطالب</th>
                            <th>الصف الدراسي</th>
                            <th>تاريخ الانضمام</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT s.student_id, s.stuname, c.grade_level, c.class_group, s.created_at 
                                FROM student s JOIN class c ON s.class_id = c.class_id 
                                ORDER BY s.created_at DESC LIMIT 5";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr class="student-row">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                        <?php echo mb_substr($row['stuname'], 0, 1, 'utf-8'); ?>
                                    </div>
                                    <span class="fw-bold"><?php echo $row['stuname']; ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="text-dark fw-medium"><?php echo $row['grade_level']; ?></span>
                                <span class="text-muted small">(<?php echo $row['class_group']; ?>)</span>
                            </td>
                            <td>
                                <span class="text-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                            </td>
                            <td>
                                <span class="badge-modern bg-success bg-opacity-10 text-success">نشط</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Integrated -->
        <footer class="footer-modern">
            <p class="mb-0">نظام إدارة المدرسة الذكي &copy; <?php echo date('Y'); ?> | صمم بكل حب لتطوير التعليم</p>
            <div class="mt-2">
                <a href="#" class="text-decoration-none text-muted me-3 small">الدعم الفني</a>
                <a href="#" class="text-decoration-none text-muted me-3 small">سياسة الخصوصية</a>
                <a href="#" class="text-decoration-none text-muted small">شروط الاستخدام</a>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // إضافة تأثيرات تفاعلية بسيطة
    document.querySelectorAll('.stat-card-new').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.querySelector('.stat-icon-box').classList.add('animate__animated', 'animate__bounceIn');
        });
        card.addEventListener('mouseleave', () => {
            card.querySelector('.stat-icon-box').classList.remove('animate__animated', 'animate__bounceIn');
        });
    });
</script>
</body>
</html>
