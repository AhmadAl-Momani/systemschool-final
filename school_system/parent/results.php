<?php
require_once '../config.php';
checkAuth(['parent']);

$parent_id = $_SESSION['user']['id'];
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;

// التحقق من أن الطالب من أبناء ولي الأمر إذا تم تحديد طالب معين
if ($student_id > 0) {
    $check = $conn->query("
        SELECT 1 FROM parent_student 
        WHERE parent_id = $parent_id AND student_id = $student_id
    ");

    if ($check->num_rows == 0) {
        $_SESSION['error_message'] = "غير مسموح بالوصول لهذه الصفحة";
        header("Location: dashboard.php");
        exit();
    }
}

// جلب النتائج - تم استخدام LEFT JOIN مع exams لضمان ظهور النتائج حتى لو كان الامتحان عشوائياً (بدون سجل في جدول exams)
// ملاحظة: إذا كانت الامتحانات العشوائية لا تملك سجل في جدول exams، سنستخدم COALESCE لعرض اسم افتراضي
$where_clause = $student_id > 0 ? "AND er.student_id = $student_id" : "";
$results_query = "
    SELECT er.*, 
           COALESCE(e.title, 'اختبار عشوائي') as exam_title, 
           COALESCE(e.subject, 'عام') as exam_subject, 
           s.stuname, s.student_id
    FROM exam_results er
    LEFT JOIN exams e ON er.exam_id = e.exam_id
    JOIN student s ON er.student_id = s.student_id
    JOIN parent_student ps ON s.student_id = ps.student_id
    WHERE ps.parent_id = $parent_id $where_clause
    ORDER BY er.completed_at DESC
";

$results = $conn->query($results_query);

if (!$results) {
    die("خطأ في جلب النتائج: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج الامتحانات | المنصة التعليمية</title>
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

        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 60px 0;
            border-radius: 0 0 50px 50px;
            margin-bottom: -40px;
        }

        .glass-card {
            background: white;
            border: none;
            border-radius: 25px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
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

        .table-custom thead {
            background: #f8f9fa;
        }

        .table-custom th {
            border: none;
            padding: 15px;
            font-weight: 700;
            color: #666;
        }

        .table-custom td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f1f1;
        }

        .badge-result {
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 700;
        }

        .custom-footer {
            background: white;
            padding: 40px 0 20px;
            margin-top: 50px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-graduation-cap me-2"></i>منصتي التعليمية</a>
            <div class="ms-auto">
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">العودة للرئيسية</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-center">
        <div class="container">
            <h2 class="fw-bold mb-2">سجل النتائج الأكاديمية</h2>
            <p class="opacity-75">عرض تفصيلي لجميع الاختبارات (الرسمية والعشوائية)</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="section-title">قائمة النتائج</h4>
                    <?php if ($student_id > 0): ?>
                        <a href="results.php" class="btn btn-light rounded-pill px-4 shadow-sm">
                            <i class="fas fa-users me-2"></i> عرض جميع الأبناء
                        </a>
                    <?php endif; ?>
                </div>

                <div class="glass-card p-0">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">#</th>
                                    <?php if ($student_id == 0): ?>
                                        <th>الطالب</th>
                                    <?php endif; ?>
                                    <th>نوع الامتحان</th>
                                    <th>المادة</th>
                                    <th class="text-center">الدرجة</th>
                                    <th class="text-center">النسبة</th>
                                    <th>التاريخ</th>
                                    <th class="pe-4 text-end">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($results && $results->num_rows > 0): ?>
                                    <?php $counter = 1; while ($res = $results->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 text-muted"><?= $counter++ ?></td>
                                            <?php if ($student_id == 0): ?>
                                                <td class="fw-bold"><?= $res['stuname'] ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="fw-bold"><?= $res['exam_title'] ?></span>
                                                <?php if ($res['exam_title'] == 'اختبار عشوائي'): ?>
                                                    <span class="badge bg-warning-subtle text-warning ms-1" style="font-size: 0.6rem;">عشوائي</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $res['exam_subject'] ?></td>
                                            <td class="text-center fw-bold"><?= $res['total_score'] ?></td>
                                            <td class="text-center">
                                                <span class="badge-result bg-<?= $res['percentage'] >= 50 ? 'success' : 'danger' ?>-subtle text-<?= $res['percentage'] >= 50 ? 'success' : 'danger' ?>">
                                                    <?= $res['percentage'] ?>%
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?= date('Y-m-d', strtotime($res['completed_at'])) ?></td>
                                            <td class="pe-4 text-end">
                                                <a href="result.php?id=<?= $res['result_id'] ?>" class="btn btn-light btn-sm rounded-pill px-3">التفاصيل</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= $student_id == 0 ? 8 : 7 ?>" class="text-center py-5 text-muted">
                                            <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                                            <p>لا توجد نتائج مسجلة حالياً </p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
