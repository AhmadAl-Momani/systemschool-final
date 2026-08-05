<?php
require_once '../config.php';

// 1. التحقق من صلاحية الدخول
checkAuth(['admin']);

$status = '';
$msg = '';

// 2. دمج منطق المعالجة (إضافة، تعديل، حذف) في نفس الصفحة
// =======================================================

// --- منطق الإضافة والتعديل ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- إضافة معلم جديد ---
    if ($action == 'add') {
        // استقبال البيانات من النموذج
        $tname = $_POST['tname'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // تشفير كلمة المرور
        $phone = $_POST['phone'];
        $subject = $_POST['subject'];
        $qualification = $_POST['qualification'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // التحقق من عدم تكرار البريد الإلكتروني
        $check_stmt = $conn->prepare("SELECT teacher_id FROM teacher WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $status = 'error';
            $msg = 'هذا البريد الإلكتروني مسجل بالفعل.';
        } else {
            $sql = "INSERT INTO teacher (tname, email, password, phone, subject, qualification, gender, address, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $tname, $email, $password, $phone, $subject, $qualification, $gender, $address, $is_active);

            if ($stmt->execute()) {
                $status = 'success';
                $msg = 'تم إضافة المعلم بنجاح.';
            } else {
                $status = 'error';
                $msg = 'حدث خطأ أثناء إضافة المعلم: ' . $conn->error;
            }
        }
    }

    // --- تعديل بيانات معلم ---
    if ($action == 'edit') {
        $teacher_id = $_POST['teacher_id'];
        $tname = $_POST['tname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $subject = $_POST['subject'];
        $qualification = $_POST['qualification'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // التحقق من عدم تكرار البريد الإلكتروني لمعلم آخر
        $check_stmt = $conn->prepare("SELECT teacher_id FROM teacher WHERE email = ? AND teacher_id != ?");
        $check_stmt->bind_param("si", $email, $teacher_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $status = 'error';
            $msg = 'هذا البريد الإلكتروني مستخدم من قبل معلم آخر.';
        } else {
            // تحديث كلمة المرور فقط إذا تم إدخال كلمة جديدة
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $sql = "UPDATE teacher SET tname=?, email=?, password=?, phone=?, subject=?, qualification=?, gender=?, address=?, is_active=? WHERE teacher_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssii", $tname, $email, $password, $phone, $subject, $qualification, $gender, $address, $is_active, $teacher_id);
            } else {
                $sql = "UPDATE teacher SET tname=?, email=?, phone=?, subject=?, qualification=?, gender=?, address=?, is_active=? WHERE teacher_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssii", $tname, $email, $phone, $subject, $qualification, $gender, $address, $is_active, $teacher_id);
            }

            if ($stmt->execute()) {
                $status = 'success';
                $msg = 'تم تحديث بيانات المعلم بنجاح.';
            } else {
                $status = 'error';
                $msg = 'حدث خطأ أثناء تحديث البيانات: ' . $conn->error;
            }
        }
    }
}

// --- منطق الحذف ---
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $teacher_id = $_GET['id'];
    
    // ملاحظة: يجب التحقق إذا كان المعلم مرتبط بصفوف أو مواد قبل الحذف لمنع أخطاء قاعدة البيانات
    // حالياً، سنقوم بالحذف مباشرة. يمكنك إضافة تحقق مشابه لصفحة الصفوف لاحقاً.
    $sql = "DELETE FROM teacher WHERE teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);

    if ($stmt->execute()) {
        $status = 'success';
        $msg = 'تم حذف المعلم بنجاح.';
    } else {
        $status = 'error';
        $msg = 'فشل الحذف. قد يكون المعلم مرتبطاً ببيانات أخرى.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المعلمين | نظام المدرسة الذكي</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- 3. تطبيق نفس التصميم الحديث المستخدم في الصفحات الأخرى -->
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85 );
            --glass-border: rgba(255, 255, 255, 0.4);
            --primary-accent: #6366f1;
            --secondary-accent: #a855f7;
            --dark-text: #1e293b;
            --light-text: #64748b;
            --sidebar-width: 280px;
        }
        body {
            background: #f8fafc;
            background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.1) 0px, transparent 50%), radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.1) 0px, transparent 50%);
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }
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
        .nav-link-custom.active i { color: white; }
        .main-content { flex-grow: 1; margin-right: var(--sidebar-width); padding: 30px; width: calc(100% - var(--sidebar-width)); }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--glass-bg); backdrop-filter: blur(10px); padding: 15px 30px; border-radius: 20px; border: 1px solid var(--glass-border); box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .content-card { background: white; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .table-modern thead th { background: #f8fafc; border: none; padding: 15px; color: var(--light-text); font-weight: 700; font-size: 0.85rem; }
        .table-modern tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .modal-content { border-radius: 24px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
        .modal-header, .modal-footer { border-color: #f1f5f9; padding: 25px; }
        .form-control, .form-select { border-radius: 12px; padding: 12px 15px; border: 2px solid #f1f5f9; transition: all 0.3s; }
        .form-control:focus { border-color: var(--primary-accent); box-shadow: none; }
        .btn-modern { padding: 10px 25px; border-radius: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-add { background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); color: white; border: none; box-shadow: 0 8px 15px rgba(99, 102, 241, 0.2); }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(99, 102, 241, 0.3); color: white; }
        .footer-modern { margin-top: 40px; text-align: center; padding: 20px; color: var(--light-text); font-size: 0.9rem; }
        @media (max-width: 992px) { .sidebar-container { display: none; } .main-content { margin-right: 0; width: 100%; } }
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
            <a href="classes.php" class="nav-link-custom"><i class="fas fa-layer-group"></i> إدارة الصفوف</a>
            <a href="teachers.php" class="nav-link-custom active"><i class="fas fa-user-tie"></i> المعلمين</a>
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

 
    <div class="main-content">
        <div class="top-header animate__animated animate__fadeInDown">
            <div>
                <h4 class="mb-0 fw-bold">إدارة المعلمين</h4>
                <p class="text-muted mb-0 small">إضافة وتعديل بيانات الكادر التعليمي</p>
            </div>
            <button class="btn btn-modern btn-add" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                <i class="fas fa-plus me-2"></i> إضافة معلم
            </button>
        </div>

    
        <div class="content-card animate__animated animate__fadeInUp">
            <div class="card-header-flex">
                <h5 class="fw-bold mb-0"><i class="fas fa-list-ul me-2 text-primary"></i> قائمة المعلمين</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المعلم</th>
                            <th>البريد الإلكتروني</th>
                            <th>المادة</th>
                            <th>الهاتف</th>
                            <th>الحالة</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM teacher ORDER BY tname";
                        $result = $conn->query($sql);
                        $count = 1;
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="fw-bold text-muted"><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['tname']); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['subject']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td>
                                <span class="badge rounded-pill bg-opacity-10 <?php echo $row['is_active'] ? 'text-success bg-success' : 'text-danger bg-danger'; ?>">
                                    <?php echo $row['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light text-primary me-2" onclick='editTeacher(<?php echo json_encode($row); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" onclick="confirmDelete(<?php echo $row['teacher_id']; ?>)">
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

<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="teachers.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">إضافة معلم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">كلمة المرور <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">رقم الهاتف</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">المادة الأساسية <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">المؤهل العلمي</label>
                            <input type="text" class="form-control" name="qualification">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الجنس</label>
                            <select class="form-select" name="gender">
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">العنوان</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="add_is_active" name="is_active" checked>
                                <label class="form-check-label" for="add_is_active">تفعيل حساب المعلم</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-modern btn-add rounded-pill px-4">حفظ المعلم</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="teachers.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_teacher_id" name="teacher_id">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">تعديل بيانات المعلم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_tname" name="tname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">كلمة مرور جديدة</label>
                            <input type="password" class="form-control" name="password" placeholder="اتركها فارغة لعدم التغيير">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">رقم الهاتف</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">المادة الأساسية <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_subject" name="subject" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">المؤهل العلمي</label>
                            <input type="text" class="form-control" id="edit_qualification" name="qualification">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الجنس</label>
                            <select class="form-select" id="edit_gender" name="gender">
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">العنوان</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">حساب نشط</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-modern btn-add rounded-pill px-4">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

    <?php if ($status ): ?>
        Swal.fire({
            icon: '<?php echo $status; ?>',
            title: '<?php echo ($status == "success" ? "تمت العملية" : "خطأ!"); ?>',
            text: '<?php echo addslashes($msg); ?>',
            confirmButtonText: 'حسناً',
            confirmButtonColor: '#6366f1'
        }).then(() => {
            if (window.location.search.includes('action=delete')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    <?php endif; ?>

    const editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
    function editTeacher(teacherData) {
        document.getElementById('edit_teacher_id').value = teacherData.teacher_id;
        document.getElementById('edit_tname').value = teacherData.tname;
        document.getElementById('edit_email').value = teacherData.email;
        document.getElementById('edit_phone').value = teacherData.phone;
        document.getElementById('edit_subject').value = teacherData.subject;
        document.getElementById('edit_qualification').value = teacherData.qualification;
        document.getElementById('edit_gender').value = teacherData.gender;
        document.getElementById('edit_address').value = teacherData.address;
        document.getElementById('edit_is_active').checked = teacherData.is_active == 1;
        
        editModal.show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من التراجع عن هذا الإجراء!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'نعم، احذفه!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'teachers.php?action=delete&id=' + id;
            }
        })
    }
</script>
</body>
</html>