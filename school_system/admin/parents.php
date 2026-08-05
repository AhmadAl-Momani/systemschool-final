<?php
require_once '../config.php';

// 1. التحقق من صلاحية الدخول
checkAuth(['admin']);

$status = '';
$msg = '';

// 2. دمج منطق المعالجة (إضافة، تعديل، حذف)
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- إضافة ولي أمر (مع طالب جديد اختياري) ---
    if ($action == 'add') {
        // بيانات ولي الأمر
        $pname = $_POST['pname'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $phone_num = $_POST['phone_num'];
        $natnum = $_POST['natnum'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // بيانات الطالب (إذا تم تفعيل الخيار)
        $add_student = isset($_POST['add_student_with_parent']);
        $stuname = $_POST['stuname'];

        // استخدام المعاملات (Transactions) لضمان تكامل البيانات
        $conn->begin_transaction();

        try {
            // التحقق من عدم تكرار بريد ولي الأمر
            $check_stmt = $conn->prepare("SELECT parent_id FROM parent WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception('هذا البريد الإلكتروني لولي الأمر مسجل بالفعل.');
            }

            // الخطوة 1: إضافة ولي الأمر
            $sql_parent = "INSERT INTO parent (pname, email, password, phone_num, natnum, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_parent = $conn->prepare($sql_parent);
            $stmt_parent->bind_param("sssssi", $pname, $email, $password, $phone_num, $natnum, $is_active);
            $stmt_parent->execute();
            $parent_id = $conn->insert_id; // الحصول على ID ولي الأمر الجديد

            // الخطوة 2: إضافة الطالب وربطه (إذا تم تفعيل الخيار وكان اسم الطالب غير فارغ)
            if ($add_student && !empty($stuname)) {
                $student_natnum = $_POST['student_natnum'];
                $gender = $_POST['gender'];
                $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;

                $sql_student = "INSERT INTO student (stuname, natnum, gender, class_id, parent_email) VALUES (?, ?, ?, ?, ?)";
                $stmt_student = $conn->prepare($sql_student);
                // نستخدم بريد ولي الأمر للربط المبدئي
                $stmt_student->bind_param("sssis", $stuname, $student_natnum, $gender, $class_id, $email);
                $stmt_student->execute();
                $student_id = $conn->insert_id;

                // الخطوة 3: إنشاء الربط في جدول parent_student
                $sql_link = "INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)";
                $stmt_link = $conn->prepare($sql_link);
                $stmt_link->bind_param("ii", $parent_id, $student_id);
                $stmt_link->execute();
            }

            // إذا تمت كل العمليات بنجاح
            $conn->commit();
            $status = 'success';
            $msg = 'تمت إضافة ولي الأمر ' . ($add_student && !empty($stuname) ? 'والطالب ' : '') . 'بنجاح.';

        } catch (Exception $e) {
            // في حالة حدوث أي خطأ، يتم التراجع عن كل العمليات
            $conn->rollback();
            $status = 'error';
            $msg = 'فشل الإجراء: ' . $e->getMessage();
        }
    }
    
    // --- تعديل بيانات ولي أمر ---
    if ($action == 'edit') {
        $parent_id = $_POST['parent_id'];
        $pname = $_POST['pname'];
        $email = $_POST['email'];
        $phone_num = $_POST['phone_num'];
        $natnum = $_POST['natnum'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // تحديث كلمة المرور فقط إذا تم إدخالها
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $sql = "UPDATE parent SET pname=?, email=?, password=?, phone_num=?, natnum=?, is_active=? WHERE parent_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssii", $pname, $email, $password, $phone_num, $natnum, $is_active, $parent_id);
        } else {
            $sql = "UPDATE parent SET pname=?, email=?, phone_num=?, natnum=?, is_active=? WHERE parent_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $pname, $email, $phone_num, $natnum, $is_active, $parent_id);
        }

        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'تم تحديث بيانات ولي الأمر بنجاح.';
        } else {
            $status = 'error';
            $msg = 'حدث خطأ أثناء التحديث.';
        }
    }
}

// --- منطق الحذف ---
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $parent_id = $_GET['id'];
    
    $conn->begin_transaction();
    try {
        // حذف الارتباطات أولاً
        $stmt1 = $conn->prepare("DELETE FROM parent_student WHERE parent_id = ?");
        $stmt1->bind_param("i", $parent_id);
        $stmt1->execute();

        // حذف ولي الأمر
        $stmt2 = $conn->prepare("DELETE FROM parent WHERE parent_id = ?");
        $stmt2->bind_param("i", $parent_id);
        $stmt2->execute();

        $conn->commit();
        $status = 'success';
        $msg = 'تم حذف ولي الأمر وجميع ارتباطاته بنجاح.';
    } catch (Exception $e) {
        $conn->rollback();
        $status = 'error';
        $msg = 'فشل الحذف بسبب خطأ في قاعدة البيانات.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة أولياء الأمور | نظام المدرسة الذكي</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- 3. تطبيق نفس التصميم الحديث -->
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
        #student_fields { border-top: 2px dashed #e2e8f0; margin-top: 20px; padding-top: 20px; }
        @media (max-width: 992px) { .sidebar-container { display: none; } .main-content { margin-right: 0; width: 100%; } }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- الشريط الجانبي -->
    <div class="sidebar-container">
        <div class="sidebar-glass animate__animated animate__fadeInRight">
            <div class="sidebar-header">
                <div class="logo-box"><i class="fas fa-graduation-cap"></i></div>
                <h5 class="fw-bold mb-0">نظام المدرسة</h5>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-grid-2"></i> لوحة التحكم</a>
                <a href="classes.php" class="nav-link-custom"><i class="fas fa-layer-group"></i> إدارة الصفوف</a>
                <a href="teachers.php" class="nav-link-custom"><i class="fas fa-user-tie"></i> المعلمين</a>
                <a href="students.php" class="nav-link-custom"><i class="fas fa-user-graduate"></i> الطلاب</a>
                <a href="parents.php" class="nav-link-custom active"><i class="fas fa-users-between-lines"></i> أولياء الأمور</a>
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

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <div class="top-header animate__animated animate__fadeInDown">
            <div>
                <h4 class="mb-0 fw-bold">إدارة أولياء الأمور</h4>
                <p class="text-muted mb-0 small">إضافة وتعديل بيانات أولياء أمور الطلاب</p>
            </div>
            <button class="btn btn-modern btn-add" data-bs-toggle="modal" data-bs-target="#addParentModal">
                <i class="fas fa-plus me-2"></i> إضافة ولي أمر
            </button>
        </div>

        <!-- جدول أولياء الأمور -->
        <div class="content-card animate__animated animate__fadeInUp">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ولي الأمر</th>
                            <th>الهاتف</th>
                            <th>الحالة</th>
                            <th class="text-center">عدد الأبناء</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT p.*, COUNT(ps.student_id) as children_count 
                                  FROM parent p 
                                  LEFT JOIN parent_student ps ON p.parent_id = ps.parent_id 
                                  GROUP BY p.parent_id ORDER BY p.pname";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($row['pname']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($row['email']) ?></div>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($row['phone_num'] ?: '---') ?></td>
                            <td><span class="badge rounded-pill bg-opacity-10 <?= $row['is_active'] ? 'text-success bg-success' : 'text-danger bg-danger' ?>"><?= $row['is_active'] ? 'نشط' : 'غير نشط' ?></span></td>
                            <td class="text-center fw-bold"><?= $row['children_count'] ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light text-primary me-2" onclick='editParent(<?php echo json_encode($row); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" onclick="confirmDelete(<?= $row['parent_id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <footer class="footer-modern"><p class="mb-0">نظام إدارة المدرسة الذكي &copy; <?php echo date('Y'); ?></p></footer>
    </div>
</div>

<!-- Modal إضافة ولي أمر وطالب -->
<div class="modal fade" id="addParentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="parents.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">إضافة ولي أمر جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold text-primary">بيانات ولي الأمر</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">الاسم الكامل <span class="text-danger">*</span></label><input type="text" class="form-control" name="pname" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label><input type="email" class="form-control" name="email" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">كلمة المرور <span class="text-danger">*</span></label><input type="password" class="form-control" name="password" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">رقم الهاتف</label><input type="tel" class="form-control" name="phone_num"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">الرقم الوطني</label><input type="text" class="form-control" name="natnum"></div>
                        <div class="col-md-6 mb-3 d-flex align-items-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="is_active" checked><label class="form-check-label">حساب نشط</label></div></div>
                    </div>
                    
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="add_student_with_parent" id="add_student_toggle">
                        <label class="form-check-label fw-bold" for="add_student_toggle">إضافة طالب جديد مع ولي الأمر</label>
                    </div>

                    <div id="student_fields" style="display:none;">
                        <h6 class="fw-bold text-primary mt-3">بيانات الطالب الجديد</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">اسم الطالب <span class="text-danger">*</span></label><input type="text" class="form-control" name="stuname"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">الرقم الوطني للطالب</label><input type="text" class="form-control" name="student_natnum"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">الجنس</label><select class="form-select" name="gender"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
                            <div class="col-md-6 mb-3"><label class="form-label">الصف الدراسي</label>
                                <select class="form-select" name="class_id">
                                    <option value="">-- غير محدد --</option>
                                    <?php $classes = $conn->query("SELECT class_id, grade_level, class_group FROM class ORDER BY grade_level, class_group");
                                    while ($class = $classes->fetch_assoc()): ?>
                                        <option value="<?= $class['class_id']; ?>"><?= htmlspecialchars($class['grade_level'] . ' - ' . $class['class_group']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-modern btn-add rounded-pill px-4">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تعديل ولي أمر -->
<div class="modal fade" id="editParentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="parents.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_parent_id" name="parent_id">
                <div class="modal-header"><h5 class="fw-bold mb-0">تعديل بيانات ولي الأمر</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">الاسم الكامل <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_pname" name="pname" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label><input type="email" class="form-control" id="edit_email" name="email" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">كلمة مرور جديدة</label><input type="password" class="form-control" name="password" placeholder="اتركها فارغة لعدم التغيير"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">رقم الهاتف</label><input type="tel" class="form-control" id="edit_phone_num" name="phone_num"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">الرقم الوطني</label><input type="text" class="form-control" id="edit_natnum" name="natnum"></div>
                        <div class="col-md-6 mb-3 d-flex align-items-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active"><label class="form-check-label" for="edit_is_active">حساب نشط</label></div></div>
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

    // إظهار/إخفاء حقول الطالب في نافذة الإضافة
    document.getElementById('add_student_toggle').addEventListener('change', function() {
        const studentFields = document.getElementById('student_fields');
        studentFields.style.display = this.checked ? 'block' : 'none';
        // جعل حقل اسم الطالب مطلوباً فقط عند إظهار الحقول
        studentFields.querySelector('[name="stuname"]').required = this.checked;
    });

    // فتح نافذة التعديل وتعبئة البيانات
    const editModal = new bootstrap.Modal(document.getElementById('editParentModal'));
    function editParent(parentData) {
        document.getElementById('edit_parent_id').value = parentData.parent_id;
        document.getElementById('edit_pname').value = parentData.pname;
        document.getElementById('edit_email').value = parentData.email;
        document.getElementById('edit_phone_num').value = parentData.phone_num;
        document.getElementById('edit_natnum').value = parentData.natnum;
        document.getElementById('edit_is_active').checked = parentData.is_active == 1;
        editModal.show();
    }

    // تأكيد الحذف
    function confirmDelete(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم حذف ولي الأمر وجميع ارتباطاته بالطلاب!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'نعم، احذفه!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'parents.php?action=delete&id=' + id;
            }
        });
    }
</script>
</body>
</html>
