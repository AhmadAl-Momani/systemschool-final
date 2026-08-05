<?php
require_once '../config.php';

// التحقق من صلاحية الدخول
checkAuth(['admin']);

$status = '';
$msg = '';

// --- منطق المعالجة (Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'add') {
        $grade_level = $_POST['grade_level'];
        $class_group = $_POST['class_group'];

        $sql = "INSERT INTO class (grade_level, class_group) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $grade_level, $class_group);

        if ($stmt->execute()) {
            $status = 'success';
            $msg = "تم إضافة الصف بنجاح";
        } else {
            $status = 'error';
            $msg = "حدث خطأ أثناء الإضافة";
        }
    }

    if ($action == 'edit') {
        $class_id = $_POST['class_id'];
        $grade_level = $_POST['grade_level'];
        $class_group = $_POST['class_group'];

        $sql = "UPDATE class SET grade_level = ?, class_group = ? WHERE class_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $grade_level, $class_group, $class_id);

        if ($stmt->execute()) {
            $status = 'success';
            $msg = "تم تحديث بيانات الصف بنجاح";
        } else {
            $status = 'error';
            $msg = "حدث خطأ أثناء التحديث";
        }
    }
}

// معالجة الحذف مع التحقق من وجود طلاب (حل مشكلة Foreign Key)
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $class_id = $_GET['id'];

    // 1. التحقق أولاً إذا كان هناك طلاب مرتبطين بهذا الصف
    $check_sql = "SELECT COUNT(*) FROM student WHERE class_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $class_id);
    $check_stmt->execute();
    $student_count = $check_stmt->get_result()->fetch_row()[0];

    if ($student_count > 0) {
        // إذا وجد طلاب، نمنع الحذف ونظهر رسالة تنبيه
        $status = 'error';
        $msg = "لا يمكن حذف هذا الصف لأنه يحتوي على ($student_count) طلاب. يرجى نقل الطلاب أو حذفهم أولاً.";
    } else {
        // إذا لم يوجد طلاب، نقوم بالحذف
        try {
            $sql = "DELETE FROM class WHERE class_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $class_id);
            
            if ($stmt->execute()) {
                $status = 'success';
                $msg = "تم حذف الصف بنجاح";
            } else {
                $status = 'error';
                $msg = "حدث خطأ غير متوقع أثناء الحذف";
            }
        } catch (mysqli_sql_exception $e) {
            $status = 'error';
            $msg = "فشل الحذف بسبب قيود قاعدة البيانات";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصفوف | نظام المدرسة الذكي</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- CSS Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.1) 0px, transparent 50%);
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        .dashboard-wrapper { display: flex; min-height: 100vh; }

        /* Sidebar Style */
        .sidebar-container { width: var(--sidebar-width); padding: 20px; position: fixed; height: 100vh; z-index: 1000; }
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
        .sidebar-header { padding: 30px 20px; text-align: center; }
        .logo-box {
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            width: 50px; height: 50px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px; color: white; font-size: 1.5rem;
            box-shadow: 0 8px 15px rgba(99, 102, 241, 0.3);
        }
        .nav-menu { flex-grow: 1; padding: 10px; }
        .nav-link-custom {
            display: flex; align-items: center; padding: 14px 20px;
            color: var(--light-text); text-decoration: none;
            border-radius: 16px; margin-bottom: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-weight: 500;
        }
        .nav-link-custom i { margin-left: 15px; font-size: 1.2rem; }
        .nav-link-custom:hover { background: rgba(99, 102, 241, 0.08); color: var(--primary-accent); transform: translateX(-5px); }
        .nav-link-custom.active {
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            color: white; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }
        .nav-link-custom.active i { color: white; }

        /* Main Content Area */
        .main-content { flex-grow: 1; margin-right: var(--sidebar-width); padding: 30px; width: calc(100% - var(--sidebar-width)); }

        /* Top Header */
        .top-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; background: var(--glass-bg); backdrop-filter: blur(10px);
            padding: 15px 30px; border-radius: 20px; border: 1px solid var(--glass-border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        /* Stats Cards */
        .stat-card-mini {
            background: white; border-radius: 20px; padding: 20px;
            border: 1px solid #f1f5f9; display: flex; align-items: center;
            gap: 15px; transition: all 0.3s;
        }
        .stat-card-mini:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .mini-icon {
            width: 45px; height: 45px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }

        /* Content Card */
        .content-card {
            background: white; border-radius: 24px; padding: 30px;
            border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        .card-header-flex {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;
        }

        /* Table Style */
        .table-modern thead th {
            background: #f8fafc; border: none; padding: 15px;
            color: var(--light-text); font-weight: 700; font-size: 0.85rem;
        }
        .table-modern tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        /* Modal Style */
        .modal-content { border-radius: 24px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 25px; }
        .modal-body { padding: 25px; }
        .form-control, .form-select {
            border-radius: 12px; padding: 12px 15px; border: 2px solid #f1f5f9;
            transition: all 0.3s;
        }
        .form-control:focus { border-color: var(--primary-accent); box-shadow: none; }

        .btn-modern {
            padding: 10px 25px; border-radius: 12px; font-weight: 600; transition: all 0.3s;
        }
        .btn-add {
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            color: white; border: none; box-shadow: 0 8px 15px rgba(99, 102, 241, 0.2);
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(99, 102, 241, 0.3); color: white; }

        .badge-class { background: #eff6ff; color: #3b82f6; padding: 6px 12px; border-radius: 8px; font-weight: 600; }
        .badge-group { background: #f5f3ff; color: #8b5cf6; padding: 6px 12px; border-radius: 8px; font-weight: 600; }
        .badge-count { background: #f0fdf4; color: #22c55e; padding: 6px 12px; border-radius: 8px; font-weight: 600; }

        /* Footer */
        .footer-modern { margin-top: 40px; text-align: center; padding: 20px; color: var(--light-text); font-size: 0.9rem; }

        @media (max-width: 992px) {
            .sidebar-container { display: none; }
            .main-content { margin-right: 0; width: 100%; }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <div class="sidebar-container">
        <div class="sidebar-glass animate__animated animate__fadeInRight">
            <div class="sidebar-header">
                <div class="logo-box"><i class="fas fa-graduation-cap"></i></div>
                <h5 class="fw-bold mb-0">نظام المدرسة</h5>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-grid-2"></i> لوحة التحكم</a>
                <a href="classes.php" class="nav-link-custom active"><i class="fas fa-layer-group"></i> إدارة الصفوف</a>
                <a href="teachers.php" class="nav-link-custom"><i class="fas fa-user-tie"></i> المعلمين</a>
                <a href="students.php" class="nav-link-custom"><i class="fas fa-user-graduate"></i> الطلاب</a>
                <a href="parents.php" class="nav-link-custom"><i class="fas fa-users-between-lines"></i> أولياء الأمور</a>
                <a href="buses.php" class="nav-link-custom"><i class="fas fa-bus"></i> إدارة الحافلات</a>
                <a href="assignments.php" class="nav-link-custom"><i class="fas fa-tasks"></i> تعيين المعلمين</a>
                <a href="reports.php" class="nav-link-custom"><i class="fas fa-chart-line"></i> التقارير</a>
                   <a href="numbers.php" class="nav-link-custom">
                    <i class="fas fa-hashtag"></i> الأرقام
                </a>
            </div>
            <div class="p-4">
                <a href="../logout.php" class="nav-link-custom text-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-header animate__animated animate__fadeInDown">
            <div>
                <h4 class="mb-0 fw-bold">إدارة الصفوف والشعب</h4>
                <p class="text-muted mb-0 small">تنظيم الهيكل الدراسي للمدرسة</p>
            </div>
            <button class="btn btn-modern btn-add" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="fas fa-plus me-2"></i> إضافة صف جديد
            </button>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <div class="stat-card-mini">
                    <div class="mini-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fas fa-school"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">إجمالي الصفوف</div>
                        <div class="h4 mb-0 fw-bold"><?php echo $conn->query("SELECT COUNT(DISTINCT grade_level) FROM class")->fetch_row()[0]; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="stat-card-mini">
                    <div class="mini-icon" style="background: #f5f3ff; color: #8b5cf6;"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">إجمالي الشعب</div>
                        <div class="h4 mb-0 fw-bold"><?php echo $conn->query("SELECT COUNT(*) FROM class")->fetch_row()[0]; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <div class="stat-card-mini">
                    <div class="mini-icon" style="background: #f0fdf4; color: #22c55e;"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">إجمالي الطلاب</div>
                        <div class="h4 mb-0 fw-bold"><?php echo $conn->query("SELECT COUNT(*) FROM student")->fetch_row()[0]; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="content-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            <div class="card-header-flex">
                <h5 class="fw-bold mb-0"><i class="fas fa-list-ul me-2 text-primary"></i> قائمة الصفوف الدراسية</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصف الدراسي</th>
                            <th>الشعبة</th>
                            <th>عدد الطلاب</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.class_id, c.grade_level, c.class_group, 
                                COUNT(s.student_id) as students_count
                                FROM class c
                                LEFT JOIN student s ON c.class_id = s.class_id
                                GROUP BY c.class_id
                                ORDER BY c.grade_level, c.class_group";
                        $result = $conn->query($sql);
                        $count = 1;
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="fw-bold text-muted"><?php echo $count++; ?></td>
                            <td><span class="badge-class"><?php echo htmlspecialchars($row['grade_level']); ?></span></td>
                            <td><span class="badge-group">شعبة (<?php echo htmlspecialchars($row['class_group']); ?>)</span></td>
                            <td><span class="badge-count"><?php echo $row['students_count']; ?> طالب</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light text-warning me-2" onclick="editClass(
                                    <?php echo $row['class_id']; ?>, 
                                    '<?php echo addslashes($row['grade_level']); ?>', 
                                    '<?php echo addslashes($row['class_group']); ?>'
                                )" data-bs-toggle="modal" data-bs-target="#editClassModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" onclick="confirmDelete(<?php echo $row['class_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="footer-modern">
            <p class="mb-0">نظام إدارة المدرسة الذكي &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>
</div>

<!-- Modal إضافة صف -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="fw-bold mb-0">إضافة صف وشعبة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-4">
                        <label class="form-label fw-bold">اختر الصف الدراسي</label>
                        <select class="form-select" name="grade_level" required>
                            <option value="الصف الأول">الصف الأول</option>
                            <option value="الصف الثاني">الصف الثاني</option>
                            <option value="الصف الثالث">الصف الثالث</option>
                            <option value="الصف الرابع">الصف الرابع</option>
                            <option value="الصف الخامس">الصف الخامس</option>
                            <option value="الصف السادس">الصف السادس</option>
                            <option value="الصف السابع">الصف السابع</option>
                            <option value="الصف الثامن">الصف الثامن</option>
                            <option value="الصف التاسع">الصف التاسع</option>
                            <option value="الصف العاشر">الصف العاشر</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">الشعبة</label>
                        <select class="form-select" name="class_group" required>
                            <option value="أ">أ</option>
                            <option value="ب">ب</option>
                            <option value="ج">ج</option>
                            <option value="د">د</option>
                            <option value="هـ">هـ</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-modern btn-add rounded-pill px-4">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تعديل صف -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="fw-bold mb-0">تعديل بيانات الصف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="editClassId" name="class_id">
                    <div class="mb-4">
                        <label class="form-label fw-bold">الصف الدراسي</label>
                        <select class="form-select" id="editGradeLevel" name="grade_level" required>
                            <option value="الصف الأول">الصف الأول</option>
                            <option value="الصف الثاني">الصف الثاني</option>
                            <option value="الصف الثالث">الصف الثالث</option>
                            <option value="الصف الرابع">الصف الرابع</option>
                            <option value="الصف الخامس">الصف الخامس</option>
                            <option value="الصف السادس">الصف السادس</option>
                            <option value="الصف السابع">الصف السابع</option>
                            <option value="الصف الثامن">الصف الثامن</option>
                            <option value="الصف التاسع">الصف التاسع</option>
                            <option value="الصف العاشر">الصف العاشر</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">الشعبة</label>
                        <select class="form-select" id="editClassGroup" name="class_group" required>
                            <option value="أ">أ</option>
                            <option value="ب">ب</option>
                            <option value="ج">ج</option>
                            <option value="د">د</option>
                            <option value="هـ">هـ</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-modern btn-add rounded-pill px-4">تحديث التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // عرض رسائل النجاح أو الخطأ من PHP باستخدام SweetAlert2
    <?php if ($status): ?>
        Swal.fire({
            icon: '<?php echo $status; ?>',
            title: '<?php echo ($status == "success" ? "تمت العملية بنجاح" : "تنبيه!"); ?>',
            text: '<?php echo $msg; ?>',
            confirmButtonText: 'حسناً',
            confirmButtonColor: '#6366f1'
        }).then(() => {
            // تنظيف الرابط من معاملات الأكشن بعد عرض الرسالة
            if (window.location.search.includes('action=delete')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    <?php endif; ?>

    function editClass(id, level, group) {
        document.getElementById('editClassId').value = id;
        document.getElementById('editGradeLevel').value = level;
        document.getElementById('editClassGroup').value = group;
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم حذف الصف نهائياً إذا لم يكن يحتوي على طلاب!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'نعم، احذف الآن',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?action=delete&id=' + id;
            }
        })
    }
</script>
</body>
</html>
