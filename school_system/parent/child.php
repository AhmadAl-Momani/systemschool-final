<?php
require_once '../config.php';
checkAuth(['parent']);

$parent_id = $_SESSION['user']['id'];
$student_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// التحقق من أن الطالب من أبناء ولي الأمر
$check = $conn->query("
    SELECT 1 FROM parent_student 
    WHERE parent_id = $parent_id AND student_id = $student_id
");

if ($check->num_rows == 0) {
    $_SESSION['error_message'] = "غير مسموح بالوصول لهذه الصفحة";
    header("Location: dashboard.php");
    exit();
}

// جلب معلومات الطالب - تم تنظيف الاستعلام من class_name و section
$student_query = $conn->query("
    SELECT s.*, c.class_group, c.grade_level
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = $student_id
");

if (!$student_query) {
    die("خطأ في جلب بيانات الطالب: " . $conn->error);
}

$student = $student_query->fetch_assoc();

// جلب نتائج الطالب
$results = $conn->query("
    SELECT er.*, e.title, e.subject
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    WHERE er.student_id = $student_id
    ORDER BY er.completed_at DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الطالب | <?= $student['stuname'] ?></title>
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
            padding: 80px 0;
            border-radius: 0 0 50px 50px;
            margin-bottom: -60px;
        }

        .glass-card {
            background: white;
            border: none;
            border-radius: 25px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .profile-header {
            text-align: center;
            padding: 30px;
            background: #fff;
            border-bottom: 1px solid #eee;
        }

        .student-large-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: -95px;
            background: white;
            object-fit: cover;
        }

        .info-label {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .info-value {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .info-box {
            padding: 15px;
            border-radius: 15px;
            background: #f8f9fa;
            height: 100%;
            border: 1px solid #eee;
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

        .percentage-circle {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            border: 3px solid;
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

    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-graduation-cap me-2"></i>منصتي التعليمية</a>
            <div class="ms-auto">
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">العودة للرئيسية</a>
            </div>
        </div>
    </nav>

    <div class="hero-section text-center">
        <div class="container">
            <h2 class="fw-bold mb-2">ملف الطالب الدراسي</h2>
            <p class="opacity-75">عرض المعلومات الشخصية والنتائج الأكاديمية</p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <div class="glass-card">
                    <div class="profile-header">
                        <img src="../uploads/students/<?= $student['profile_pic'] ?? ($student['gender'] == 'male' ? 'male-student.png' : 'female-student.png') ?>" 
                             class="student-large-avatar" alt="Student">
                        <h3 class="fw-bold mt-3 mb-1"><?= $student['stuname'] ?></h3>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">طالب نشط</span>
                    </div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-4 col-6">
                                <div class="info-box">
                                    <div class="info-label">الصف الدراسي</div>
                                    <div class="info-value"><?= $student['grade_level'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="info-box">
                                    <div class="info-label">المجموعة</div>
                                    <div class="info-value"><?= $student['class_group'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12">
                                <div class="info-box">
                                    <div class="info-label">رقم الهوية</div>
                                    <div class="info-value"><?= $student['natnum'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="fw-bold mb-3 mt-4"><i class="fas fa-poll-h me-2 text-primary"></i> سجل النتائج</h4>
                <div class="glass-card p-0">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>المادة الدراسية</th>
                                    <th>نوع الاختبار</th>
                                    <th>التاريخ</th>
                                    <th class="text-center">النسبة</th>
                                    <th class="text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($results && $results->num_rows > 0): ?>
                                    <?php while ($result = $results->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?= $result['subject'] ?></td>
                                            <td><?= $result['title'] ?></td>
                                            <td class="text-muted small"><?= date('Y-m-d', strtotime($result['completed_at'])) ?></td>
                                            <td class="text-center">
                                                <div class="percentage-circle mx-auto" 
                                                     style="border-color: <?= $result['percentage'] >= 50 ? '#2ecc71' : '#e74a3b' ?>; 
                                                            color: <?= $result['percentage'] >= 50 ? '#2ecc71' : '#e74a3b' ?>;">
                                                    <?= $result['percentage'] ?>%
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($result['percentage'] >= 50): ?>
                                                    <span class="badge bg-success-subtle text-success rounded-pill px-3">ناجح</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">راسب</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-info-circle d-block mb-2 fa-2x opacity-25"></i>
                                            لا توجد نتائج مسجلة لهذا الطالب حالياً
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

    <footer class="custom-footer">
        <div class="container text-center">
            <p class="text-muted small mb-0">جميع الحقوق محفوظة &copy; <?= date('Y') ?> | المنصة التعليمية</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
