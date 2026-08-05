<?php
require_once '../config.php';
checkAuth(['admin']);

$status = '';
$msg = '';

function upload_photo($file_input_name, $current_photo = '') {
    $upload_dir = '../uploads/students/';
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        if (!empty($current_photo) && file_exists($upload_dir . $current_photo)) {
            unlink($upload_dir . $current_photo);
        }
        $file_name = time() . '_' . basename($_FILES[$file_input_name]['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $target_file)) {
            return $file_name;
        }
    }
    return $current_photo;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'add') {
        $stuname = $_POST['stuname'];
        $natnum = $_POST['natnum'];
        $gender = $_POST['gender'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        $parent_email = $_POST['parent_email'];
        $father_phone = $_POST['father_phone'];
        $mother_phone = $_POST['mother_phone'];
        $area = $_POST['area'];
        $bus_id = !empty($_POST['bus_id']) ? $_POST['bus_id'] : null;
        $profile_pic = upload_photo('profile_pic');

        $sql = "INSERT INTO student (stuname, natnum, gender, class_id, parent_email, profile_pic, father_phone, mother_phone, area, bus_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssississi", $stuname, $natnum, $gender, $class_id, $parent_email, $profile_pic, $father_phone, $mother_phone, $area, $bus_id);

        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'تم إضافة الطالب بنجاح.';
        } else {
            $status = 'error';
            $msg = 'حدث خطأ أثناء الإضافة: ' . $conn->error;
        }
    }

    if ($action == 'edit') {
        $student_id = $_POST['student_id'];
        $stuname = $_POST['stuname'];
        $natnum = $_POST['natnum'];
        $gender = $_POST['gender'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        $parent_email = $_POST['parent_email'];
        $father_phone = $_POST['father_phone'];
        $mother_phone = $_POST['mother_phone'];
        $area = $_POST['area'];
        $bus_id = !empty($_POST['bus_id']) ? $_POST['bus_id'] : null;
        $current_photo = $_POST['current_photo'];

        if (isset($_POST['remove_photo'])) {
            if (!empty($current_photo) && file_exists('../uploads/students/' . $current_photo)) {
                unlink('../uploads/students/' . $current_photo);
            }
            $profile_pic = '';
        } else {
            $profile_pic = upload_photo('profile_pic', $current_photo);
        }

        $sql = "UPDATE student SET stuname=?, natnum=?, gender=?, class_id=?, parent_email=?, profile_pic=?, father_phone=?, mother_phone=?, area=?, bus_id=? WHERE student_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssississii", $stuname, $natnum, $gender, $class_id, $parent_email, $profile_pic, $father_phone, $mother_phone, $area, $bus_id, $student_id);

        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'تم تحديث بيانات الطالب بنجاح.';
        } else {
            $status = 'error';
            $msg = 'حدث خطأ أثناء التحديث: ' . $conn->error;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $student_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT profile_pic FROM student WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['profile_pic']) && file_exists('../uploads/students/' . $row['profile_pic'])) {
            unlink('../uploads/students/' . $row['profile_pic']);
        }
    }
    $delete_stmt = $conn->prepare("DELETE FROM student WHERE student_id = ?");
    $delete_stmt->bind_param("i", $student_id);
    if ($delete_stmt->execute()) {
        $status = 'success';
        $msg = 'تم حذف الطالب بنجاح.';
    } else {
        $status = 'error';
        $msg = 'فشل الحذف.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلاب | نظام المدرسة الذكي</title>
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
        .avatar-img { width: 40px; height: 40px; object-fit: cover; border-radius: 12px; }
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
                <a href="students.php" class="nav-link-custom active"><i class="fas fa-user-graduate"></i> الطلاب</a>
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
        <div class="top-header">
            <div><h4 class="mb-0 fw-bold">إدارة الطلاب</h4></div>
            <button class="btn btn-modern btn-add" style="background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); color:white;" data-bs-toggle="modal" data-bs-target="#addStudentModal">إضافة طالب</button>
        </div>
        <div class="content-card">
            <table class="table table-modern">
                <thead><tr><th>الطالب</th><th>الصف</th><th>المنطقة</th><th>رقم الحافلة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php
                    $sql = "SELECT s.*, c.grade_level, c.class_group, b.bus_number 
                            FROM student s 
                            LEFT JOIN class c ON s.class_id = c.class_id 
                            LEFT JOIN buses b ON s.bus_id = b.bus_id 
                            ORDER BY s.student_id DESC";
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if($row['profile_pic']): ?><img src="../uploads/students/<?= $row['profile_pic'] ?>" class="avatar-img me-2"><?php endif; ?>
                                <?= $row['stuname'] ?>
                            </div>
                        </td>
                        <td><?= $row['grade_level'] . ' - ' . $row['class_group'] ?></td>
                        <td><?= $row['area'] ?></td>
                        <td><?= $row['bus_number'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='editStudent(<?= json_encode($row) ?>)'>تعديل</button>
                            <a href="?action=delete&id=<?= $row['student_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals for Add/Edit Student with new fields -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-header"><h5 class="modal-title">إضافة طالب جديد</h5></div>
            <div class="modal-body row">
                <div class="col-md-6 mb-3"><label>الاسم</label><input type="text" name="stuname" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label>الرقم الوطني</label><input type="text" name="natnum" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>الجنس</label><select name="gender" class="form-select"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
                <div class="col-md-6 mb-3"><label>بريد ولي الأمر</label><input type="email" name="parent_email" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>رقم هاتف الأب</label><input type="text" name="father_phone" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>رقم هاتف الأم</label><input type="text" name="mother_phone" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>المنطقة</label><input type="text" name="area" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>الحافلة</label>
                    <select name="bus_id" class="form-select">
                        <option value="">-- بدون حافلة --</option>
                        <?php 
                        $buses = $conn->query("SELECT * FROM buses");
                        while($b = $buses->fetch_assoc()): ?>
                        <option value="<?= $b['bus_id'] ?>"><?= $b['bus_number'] ?> - <?= $b['area'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label>الصف</label>
                    <select name="class_id" class="form-select">
                        <option value="">-- غير محدد --</option>
                        <?php 
                        $classes = $conn->query("SELECT * FROM class");
                        while($c = $classes->fetch_assoc()): ?>
                        <option value="<?= $c['class_id'] ?>"><?= $c['grade_level'] ?> - <?= $c['class_group'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label>الصورة الشخصية</label><input type="file" name="profile_pic" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">حفظ</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_student_id">
            <input type="hidden" name="current_photo" id="edit_current_photo">
            <div class="modal-header"><h5 class="modal-title">تعديل بيانات الطالب</h5></div>
            <div class="modal-body row">
                <div class="col-md-6 mb-3"><label>الاسم</label><input type="text" name="stuname" id="edit_stuname" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label>الرقم الوطني</label><input type="text" name="natnum" id="edit_natnum" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>الجنس</label><select name="gender" id="edit_gender" class="form-select"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
                <div class="col-md-6 mb-3"><label>بريد ولي الأمر</label><input type="email" name="parent_email" id="edit_parent_email" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>رقم هاتف الأب</label><input type="text" name="father_phone" id="edit_father_phone" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>رقم هاتف الأم</label><input type="text" name="mother_phone" id="edit_mother_phone" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>المنطقة</label><input type="text" name="area" id="edit_area" class="form-control"></div>
                <div class="col-md-6 mb-3"><label>الحافلة</label>
                    <select name="bus_id" id="edit_bus_id" class="form-select">
                        <option value="">-- بدون حافلة --</option>
                        <?php 
                        $buses = $conn->query("SELECT * FROM buses");
                        while($b = $buses->fetch_assoc()): ?>
                        <option value="<?= $b['bus_id'] ?>"><?= $b['bus_number'] ?> - <?= $b['area'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label>الصف</label>
                    <select name="class_id" id="edit_class_id" class="form-select">
                        <option value="">-- غير محدد --</option>
                        <?php 
                        $classes = $conn->query("SELECT * FROM class");
                        while($c = $classes->fetch_assoc()): ?>
                        <option value="<?= $c['class_id'] ?>"><?= $c['grade_level'] ?> - <?= $c['class_group'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label>تغيير الصورة</label><input type="file" name="profile_pic" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">تحديث</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editStudent(s) {
    document.getElementById('edit_student_id').value = s.student_id;
    document.getElementById('edit_stuname').value = s.stuname;
    document.getElementById('edit_natnum').value = s.natnum;
    document.getElementById('edit_gender').value = s.gender;
    document.getElementById('edit_parent_email').value = s.parent_email;
    document.getElementById('edit_father_phone').value = s.father_phone;
    document.getElementById('edit_mother_phone').value = s.mother_phone;
    document.getElementById('edit_area').value = s.area;
    document.getElementById('edit_bus_id').value = s.bus_id || '';
    document.getElementById('edit_class_id').value = s.class_id || '';
    document.getElementById('edit_current_photo').value = s.profile_pic || '';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}
</script>
<?php if($status): ?>
<script>Swal.fire('<?= $status == 'success' ? 'نجاح' : 'خطأ' ?>', '<?= $msg ?>', '<?= $status ?>');</script>
<?php endif; ?>
</body>
</html>
