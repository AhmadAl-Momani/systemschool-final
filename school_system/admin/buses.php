<?php
require_once '../config.php';
checkAuth(['admin']);

$status = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'add') {
        $bus_number = $_POST['bus_number'];
        $area = $_POST['area'];
        $sql = "INSERT INTO buses (bus_number, area) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $bus_number, $area);
        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'تم إضافة الحافلة بنجاح.';
        } else {
            $status = 'error';
            $msg = 'حدث خطأ أثناء الإضافة.';
        }
    }
    if ($action == 'edit') {
        $bus_id = $_POST['bus_id'];
        $bus_number = $_POST['bus_number'];
        $area = $_POST['area'];
        $sql = "UPDATE buses SET bus_number=?, area=? WHERE bus_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $bus_number, $area, $bus_id);
        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'تم تحديث بيانات الحافلة بنجاح.';
        } else {
            $status = 'error';
            $msg = 'حدث خطأ أثناء التحديث.';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $bus_id = $_GET['id'];
    $sql = "DELETE FROM buses WHERE bus_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bus_id);
    if ($stmt->execute()) {
        $status = 'success';
        $msg = 'تم حذف الحافلة بنجاح.';
    } else {
        $status = 'error';
        $msg = 'فشل الحذف.';
    }
}

$buses = $conn->query("SELECT * FROM buses ORDER BY bus_id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الحافلات | نظام المدرسة الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
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
                <a href="buses.php" class="nav-link-custom active"><i class="fas fa-bus"></i> إدارة الحافلات</a>
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
    <div class="main-content">
        <div class="top-header">
            <div><h4 class="mb-0 fw-bold">إدارة الحافلات</h4></div>
            <button class="btn btn-modern btn-add" data-bs-toggle="modal" data-bs-target="#addBusModal">إضافة حافلة</button>
        </div>
        <div class="content-card">
            <table class="table table-modern">
                <thead><tr><th>رقم الحافلة</th><th>المنطقة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php while($row = $buses->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['bus_number'] ?></td>
                        <td><?= $row['area'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='editBus(<?= json_encode($row) ?>)'>تعديل</button>
                            <a href="?action=delete&id=<?= $row['bus_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addBusModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="add">
            <div class="modal-header"><h5 class="modal-title">إضافة حافلة جديدة</h5></div>
            <div class="modal-body">
                <div class="mb-3"><label>رقم الحافلة</label><input type="text" name="bus_number" class="form-control" required></div>
                <div class="mb-3"><label>المنطقة</label><input type="text" name="area" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">حفظ</button></div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editBusModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="bus_id" id="edit_bus_id">
            <div class="modal-header"><h5 class="modal-title">تعديل بيانات الحافلة</h5></div>
            <div class="modal-body">
                <div class="mb-3"><label>رقم الحافلة</label><input type="text" name="bus_number" id="edit_bus_number" class="form-control" required></div>
                <div class="mb-3"><label>المنطقة</label><input type="text" name="area" id="edit_area" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">تحديث</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editBus(bus) {
    document.getElementById('edit_bus_id').value = bus.bus_id;
    document.getElementById('edit_bus_number').value = bus.bus_number;
    document.getElementById('edit_area').value = bus.area;
    new bootstrap.Modal(document.getElementById('edit_busModal')).show();
}
</script>
<?php if($status): ?>
<script>Swal.fire('<?= $status == 'success' ? 'نجاح' : 'خطأ' ?>', '<?= $msg ?>', '<?= $status ?>');</script>
<?php endif; ?>
</body>
</html>
