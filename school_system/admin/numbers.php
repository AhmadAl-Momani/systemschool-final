<?php
require_once '../config.php';
checkAuth(['admin']);

$status = '';
$msg = '';

if (isset($_SESSION['success'])) {
    $status = 'success';
    $msg = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $status = 'error';
    $msg = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأرقام | نظام المدرسة الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .content-card { background: white; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .table-modern thead th { background: #f8fafc; border: none; padding: 15px; color: var(--light-text); font-weight: 700; font-size: 0.85rem; }
        .table-modern tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .number-img { width: 60px; height: 60px; object-fit: contain; border-radius: 12px; background: #f1f5f9; padding: 5px; }
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
                <a href="reports.php" class="nav-link-custom"><i class="fas fa-chart-line"></i> التقارير</a>
                <a href="numbers.php" class="nav-link-custom active"><i class="fas fa-hashtag"></i> الأرقام</a>
            </div>
            <div class="p-4">
                <a href="../logout.php" class="nav-link-custom text-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div><h4 class="mb-0 fw-bold">إدارة الأرقام المصورة</h4></div>
            <button class="btn btn-modern" style="background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); color:white;" data-bs-toggle="modal" data-bs-target="#addNumberModal">إضافة رقم جديد</button>
        </div>

        <?php if($msg): ?>
        <script>
            Swal.fire({
                icon: '<?= $status == "success" ? "success" : "error" ?>',
                title: '<?= $status == "success" ? "نجاح" : "خطأ" ?>',
                text: '<?= $msg ?>',
                confirmButtonText: 'حسناً'
            });
        </script>
        <?php endif; ?>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>الصورة</th>
                            <th>تاريخ الإضافة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $numbers = $conn->query("SELECT * FROM numbers ORDER BY number_value");
                        while ($number = $numbers->fetch_assoc()):
                        ?>
                        <tr>
                            <td><h4 class="fw-bold mb-0"><?= $number['number_value'] ?></h4></td>
                            <td><img src="../uploads/numbers/<?= $number['image_path'] ?>" class="number-img"></td>
                            <td><span class="text-muted"><?= date('Y-m-d', strtotime($number['created_at'])) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" data-bs-toggle="modal" data-bs-target="#editNumberModal<?= $number['number_id'] ?>">تعديل</button>
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="confirmDelete(<?= $number['number_id'] ?>)">حذف</button>
                            </td>
                        </tr>

                        <!-- Modal تعديل الرقم -->
                        <div class="modal fade" id="editNumberModal<?= $number['number_id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                                    <form action="process_number.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="number_id" value="<?= $number['number_id'] ?>">
                                        <div class="modal-header border-0 p-4 pb-0">
                                            <h5 class="fw-bold">تعديل الرقم <?= $number['number_value'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">قيمة الرقم</label>
                                                <input type="number" class="form-control rounded-3" name="number_value" value="<?= $number['number_value'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">تغيير الصورة</label>
                                                <input type="file" class="form-control rounded-3" name="number_image" accept="image/*">
                                                <div class="mt-3 text-center p-3 bg-light rounded-3">
                                                    <p class="small text-muted mb-2">الصورة الحالية:</p>
                                                    <img src="../uploads/numbers/<?= $number['image_path'] ?>" width="80" class="rounded shadow-sm">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 p-4 pt-0">
                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); border:0;">حفظ التغييرات</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة رقم جديد -->
<div class="modal fade" id="addNumberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <form action="process_number.php" method="POST" enctype="multipart/form-data" id="addNumberForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold">إضافة رقم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">قيمة الرقم</label>
                        <input type="number" class="form-control rounded-3" name="number_value" id="number_value" placeholder="مثلاً: 5" required>
                        <div class="invalid-feedback" id="number_error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">صورة الرقم</label>
                        <input type="file" class="form-control rounded-3" name="number_image" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); border:0;">إضافة الرقم</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من التراجع عن حذف هذا الرقم!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احذفه!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `process_number.php?action=delete&id=${id}`;
            }
        });
    }

    document.getElementById('addNumberForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const numberValue = document.getElementById('number_value').value;
        fetch('check_number.php?number=' + numberValue)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    document.getElementById('number_value').classList.add('is-invalid');
                    document.getElementById('number_error').textContent = 'الرقم موجود مسبقاً في النظام';
                } else {
                    e.target.submit();
                }
            });
    });

    document.getElementById('number_value').addEventListener('input', function () {
        this.classList.remove('is-invalid');
    });
</script>
</body>
</html>
