<?php
require_once '../config.php';
checkAuth(['admin']);

// إحصائيات سريعة
$students_count = $conn->query("SELECT COUNT(*) FROM student")->fetch_row()[0];
$teachers_count = $conn->query("SELECT COUNT(*) FROM teacher")->fetch_row()[0];
$parents_count = $conn->query("SELECT COUNT(*) FROM parent")->fetch_row()[0];
$exams_count = $conn->query("SELECT COUNT(*) FROM exams")->fetch_row()[0];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير والإحصائيات | نظام المدرسة الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --glass-bg: rgba(255, 255, 255, 0.85); --glass-border: rgba(255, 255, 255, 0.4); --primary-accent: #6366f1; --secondary-accent: #a855f7; --dark-text: #1e293b; --light-text: #64748b; --sidebar-width: 280px; }
        body { background: #f8fafc; background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.1) 0px, transparent 50%), radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.1) 0px, transparent 50%); font-family: 'Tajawal', sans-serif; min-height: 100vh; margin: 0; overflow-x: hidden; }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar-container { width: var(--sidebar-width); padding: 20px; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar-glass { background: var(--glass-bg); backdrop-filter: blur(15px); border: 1px solid var(--glass-border); height: 100%; border-radius: 24px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .sidebar-header { padding: 30px 20px; text-align: center; }
        .logo-box { background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: white; font-size: 1.5rem; box-shadow: 0 8px 15px rgba(99, 102, 241, 0.3); }
        .nav-menu { flex-grow: 1; padding: 10px; }
        .nav-link-custom { display: flex; align-items: center; padding: 14px 20px; color: var(--light-text); text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-weight: 500; }
        .nav-link-custom i { margin-left: 15px; font-size: 1.2rem; }
        .nav-link-custom:hover { background: rgba(99, 102, 241, 0.08); color: var(--primary-accent); transform: translateX(-5px); }
        .nav-link-custom.active { background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); color: white; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2); }
        .main-content { flex-grow: 1; margin-right: var(--sidebar-width); padding: 30px; width: calc(100% - var(--sidebar-width)); }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--glass-bg); backdrop-filter: blur(10px); padding: 15px 30px; border-radius: 20px; border: 1px solid var(--glass-border); box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .stat-card { background: white; border-radius: 24px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 15px; }
        .content-card { background: white; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); height: 100%; }
        .table-modern thead th { background: #f8fafc; border: none; padding: 15px; color: var(--light-text); font-weight: 700; font-size: 0.85rem; }
        .table-modern tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <div class="sidebar-container">
        <div class="sidebar-glass">
            <div class="sidebar-header">
                <div class="logo-box"><i class="fas fa-graduation-cap"></i></div>
                <h5 class="fw-bold mb-0">نظام المدرسة</h5>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-th-large"></i> لوحة التحكم</a>
                <a href="classes.php" class="nav-link-custom"><i class="fas fa-layer-group"></i> إدارة الصفوف</a>
                <a href="teachers.php" class="nav-link-custom"><i class="fas fa-user-tie"></i> المعلمين</a>
                <a href="students.php" class="nav-link-custom"><i class="fas fa-user-graduate"></i> الطلاب</a>
                <a href="parents.php" class="nav-link-custom"><i class="fas fa-users-between-lines"></i> أولياء الأمور</a>
                <a href="buses.php" class="nav-link-custom"><i class="fas fa-bus"></i> إدارة الحافلات</a>
                <a href="assignments.php" class="nav-link-custom"><i class="fas fa-tasks"></i> تعيين المعلمين</a>
                <a href="reports.php" class="nav-link-custom active"><i class="fas fa-chart-line"></i> التقارير</a>
                <a href="numbers.php" class="nav-link-custom"><i class="fas fa-hashtag"></i> الأرقام</a>
            </div>
            <div class="p-4">
                <a href="../logout.php" class="nav-link-custom text-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div><h4 class="mb-0 fw-bold">التقارير والإحصائيات العامة</h4></div>
            <div class="text-muted small">تحديث تلقائي للبيانات</div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-user-graduate"></i></div>
                    <div class="text-muted small fw-bold">إجمالي الطلاب</div>
                    <h2 class="fw-bold mb-0"><?= $students_count ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-tie"></i></div>
                    <div class="text-muted small fw-bold">إجمالي المعلمين</div>
                    <h2 class="fw-bold mb-0"><?= $teachers_count ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-users"></i></div>
                    <div class="text-muted small fw-bold">أولياء الأمور</div>
                    <h2 class="fw-bold mb-0"><?= $parents_count ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-file-signature"></i></div>
                    <div class="text-muted small fw-bold">الامتحانات المضافة</div>
                    <h2 class="fw-bold mb-0"><?= $exams_count ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="content-card">
                    <h5 class="fw-bold mb-4"><i class="fas fa-chart-pie me-2 text-primary"></i>توزيع الطلاب حسب الصفوف</h5>
                    <canvas id="classesChart" height="300"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="content-card">
                    <h5 class="fw-bold mb-4"><i class="fas fa-chart-bar me-2 text-secondary"></i>أداء الطلاب في آخر الامتحانات</h5>
                    <canvas id="performanceChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-star me-2 text-warning"></i>أفضل الطلاب أداءً (المعدل العام)</h5>
                <a href="students.php" class="btn btn-sm btn-light rounded-pill px-3">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>الصف</th>
                            <th>عدد الامتحانات</th>
                            <th>المعدل العام</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $top_students = $conn->query("
                            SELECT s.stuname, c.grade_level, c.class_group, 
                            COUNT(er.result_id) as exams_done,
                            AVG(er.percentage) as avg_score
                            FROM student s
                            JOIN class c ON s.class_id = c.class_id
                            LEFT JOIN exam_results er ON s.student_id = er.student_id
                            GROUP BY s.student_id
                            ORDER BY avg_score DESC
                            LIMIT 5
                        ");
                        while($row = $top_students->fetch_assoc()):
                        ?>
                        <tr>
                            <td><span class="fw-bold"><?= htmlspecialchars($row['stuname']) ?></span></td>
                            <td><?= $row['grade_level'] . ' - ' . $row['class_group'] ?></td>
                            <td><?= $row['exams_done'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 8px; border-radius: 10px;">
                                        <div class="progress-bar bg-primary" style="width: <?= $row['avg_score'] ?: 0 ?>%"></div>
                                    </div>
                                    <span class="fw-bold"><?= number_format($row['avg_score'], 1) ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php if($row['avg_score'] >= 90): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">ممتاز</span>
                                <?php elseif($row['avg_score'] >= 75): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">جيد جداً</span>
                                <?php elseif($row['avg_score'] > 0): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">مقبول</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted rounded-pill px-3">لا توجد بيانات</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // بيانات توزيع الصفوف من قاعدة البيانات
    <?php
    $class_data = $conn->query("
        SELECT c.grade_level, COUNT(s.student_id) as count 
        FROM class c 
        LEFT JOIN student s ON c.class_id = s.class_id 
        GROUP BY c.grade_level
    ");
    $labels = []; $counts = [];
    while($r = $class_data->fetch_assoc()) {
        $labels[] = $r['grade_level'];
        $counts[] = $r['count'];
    }
    ?>

    const classesCtx = document.getElementById('classesChart').getContext('2d');
    new Chart(classesCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                data: <?= json_encode($counts) ?>,
                backgroundColor: ['#6366f1', '#a855f7', '#f43f5e', '#fbbf24', '#10b981', '#3b82f6'],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom', labels: { font: { family: 'Tajawal', weight: 'bold' }, padding: 20 } } },
            cutout: '70%'
        }
    });

    // بيانات الأداء العام (توزيع العلامات)
    <?php
    $perf = $conn->query("
        SELECT 
            SUM(CASE WHEN percentage >= 90 THEN 1 ELSE 0 END) as excellent,
            SUM(CASE WHEN percentage >= 75 AND percentage < 90 THEN 1 ELSE 0 END) as very_good,
            SUM(CASE WHEN percentage >= 50 AND percentage < 75 THEN 1 ELSE 0 END) as good,
            SUM(CASE WHEN percentage < 50 THEN 1 ELSE 0 END) as failed
        FROM exam_results
    ")->fetch_assoc();
    ?>

    const perfCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(perfCtx, {
        type: 'bar',
        data: {
            labels: ['ممتاز (90+)', 'جيد جداً (75-89)', 'مقبول (50-74)', 'ضعيف (<50)'],
            datasets: [{
                label: 'عدد الطلاب',
                data: [<?= (int)$perf['excellent'] ?>, <?= (int)$perf['very_good'] ?>, <?= (int)$perf['good'] ?>, <?= (int)$perf['failed'] ?>],
                backgroundColor: ['#10b981', '#3b82f6', '#fbbf24', '#f43f5e'],
                borderRadius: 10
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });
</script>
</body>
</html>
