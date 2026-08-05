<?php
require_once '../config.php';
checkAuth(['admin']);

$status = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'add') {
        $teacher_id = $_POST['teacher_id'];
        $class_id = $_POST['class_id'];
        $subject = $_POST['subject'];
        $sql = "INSERT INTO teacher_assignments (teacher_id, class_id, subject) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $teacher_id, $class_id, $subject);
        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'تم تعيين المعلم بنجاح.';
        } else {
            $status = 'error';
            $msg = 'حدث خطأ أثناء التعيين.';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $assignment_id = $_GET['id'];
    $sql = "DELETE FROM teacher_assignments WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    if ($stmt->execute()) {
        $status = 'success';
        $msg = 'تم حذف التعيين بنجاح.';
    } else {
        $status = 'error';
        $msg = 'فشل الحذف.';
    }
}

$teachers = $conn->query("SELECT * FROM teacher ORDER BY tname");
$classes = $conn->query("SELECT * FROM class ORDER BY grade_level, class_group");
$assignments = $conn->query("SELECT ta.*, t.tname, c.grade_level, c.class_group 
                            FROM teacher_assignments ta 
                            JOIN teacher t ON ta.teacher_id = t.teacher_id 
                            JOIN class c ON ta.class_id = c.class_id 
                            ORDER BY t.tname");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين المعلمين | نظام المدرسة الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --glass-bg: rgba(255, 255, 255, 0.85 ); --glass-border: rgba(255, 255, 255, 0.4); --primary-accent: #6366f1; --secondary-accent: #a855f7; --dark-text: #1e293b; --light-text: #64748b; --sidebar-width: 280px; }
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
        .content-card { background: white; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .table-modern thead th { background: #f8fafc; border: none; padding: 15px; color: var(--light-text); font-weight: 700; font-size: 0.85rem; }
        .table-modern tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .btn-modern { padding: 10px 25px; border-radius: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-add { background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); color: white; border: none; box-shadow: 0 8px 15px rgba(99, 102, 241, 0.2); }
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
                <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-grid-2"></i> لوحة التحكم</a>
                <a href="classes.php" class="nav-link-custom"><i class="fas fa-layer-group"></i> إدارة الصفوف</a>
                <a href="teachers.php" class="nav-link-custom"><i class="fas fa-user-tie"></i> المعلمين</a>
                <a href="students.php" class="nav-link-custom"><i class="fas fa-user-graduate"></i> الطلاب</a>
                <a href="parents.php" class="nav-link-custom"><i class="fas fa-users-between-lines"></i> أولياء الأمور</a>
                <a href="buses.php" class="nav-link-custom"><i class="fas fa-bus"></i> إدارة الحافلات</a>
                <a href="assignments.php" class="nav-link-custom active"><i class="fas fa-tasks"></i> تعيين المعلمين</a>
                <a href="reports.php" class="nav-link-custom"><i class="fas fa-chart-line"></i> التقارير</a>
                <a href="numbers.php" class="nav-link-custom"><i class="fas fa-hashtag"></i> الأرقام
                </a>

            </div>
              <div class="p-4">
                <a href="../logout.php" class="nav-link-custom text-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="top-header">
            <div><h4 class="mb-0 fw-bold">تعيين المعلمين للصفوف والمواد</h4></div>
            <button class="btn btn-modern btn-add" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">إضافة تعيين جديد</button>
        </div>
        <div class="content-card">
            <table class="table table-modern">
                <thead><tr><th>المعلم</th><th>الصف</th><th>المبحث</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php while($row = $assignments->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['tname'] ?></td>
                        <td><?= $row['grade_level'] . ' - ' . $row['class_group'] ?></td>
                        <td><?= $row['subject'] ?></td>
                        <td>
                            <a href="?action=delete&id=<?= $row['assignment_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="add">
            <div class="modal-header"><h5 class="modal-title">إضافة تعيين جديد</h5></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>المعلم</label>
                    <select name="teacher_id" class="form-select" required>
                        <?php while($t = $teachers->fetch_assoc()): ?>
                        <option value="<?= $t['teacher_id'] ?>"><?= $t['tname'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>الصف</label>
                    <select name="class_id" class="form-select" required>
                        <?php while($c = $classes->fetch_assoc()): ?>
                        <option value="<?= $c['class_id'] ?>"><?= $c['grade_level'] . ' - ' . $c['class_group'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>المبحث</label>
                    <input type="text" name="subject" class="form-control" required placeholder="مثلاً: لغة عربية">
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">حفظ</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if($status): ?>
<script>Swal.fire('<?= $status == 'success' ? 'نجاح' : 'خطأ' ?>', '<?= $msg ?>', '<?= $status ?>');</script>
<?php endif; ?>
</body>
</html>
