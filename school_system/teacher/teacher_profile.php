<?php
require_once '../config.php';
checkAuth(['teacher']);

$teacher_id = $_SESSION['user']['id'];
$page_title = "الملف الشخصي";

$teacher = $conn->query("SELECT * FROM teacher WHERE teacher_id = $teacher_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $tname = $conn->real_escape_string($_POST['tname']);
  $email = $conn->real_escape_string($_POST['email']);
  $phone = $conn->real_escape_string($_POST['phone']);
  $subject = $conn->real_escape_string($_POST['subject']);
  $qualification = $conn->real_escape_string($_POST['qualification']);
  $address = $conn->real_escape_string($_POST['address']);

  $password_update = '';
  if (!empty($_POST['password'])) {
    $password_update = ", password = '" . password_hash($_POST['password'], PASSWORD_DEFAULT) . "'";
  }

  $sql = "UPDATE teacher SET 
            tname = '$tname',
            email = '$email',
            phone = '$phone',
            subject = '$subject',
            qualification = '$qualification',
            address = '$address'
            $password_update
            WHERE teacher_id = $teacher_id";

  if ($conn->query($sql)) {
    $_SESSION['success_message'] = "تم تحديث الملف الشخصي بنجاح";
    header("Location: teacher_profile.php");
    exit();
  } else {
    $_SESSION['error_message'] = "حدث خطأ أثناء التحديث: " . $conn->error;
  }
}

include 'teacher_header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold"><i class="fas fa-user-cog text-primary me-2"></i> الملف الشخصي</h3>
</div>

<div class="content-card">
    <form method="POST">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="tname"
                    value="<?= htmlspecialchars($teacher['tname']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email"
                    value="<?= htmlspecialchars($teacher['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">كلمة المرور الجديدة</label>
                    <input type="password" class="form-control" name="password"
                    placeholder="اتركه فارغاً إذا كنت لا تريد التغيير">
                </div>
                <div class="mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="tel" class="form-control" name="phone"
                    value="<?= htmlspecialchars($teacher['phone']) ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">المادة <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subject"
                    value="<?= htmlspecialchars($teacher['subject']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">المؤهل العلمي</label>
                    <input type="text" class="form-control" name="qualification"
                    value="<?= htmlspecialchars($teacher['qualification']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">الجنس</label>
                    <select class="form-select" name="gender" disabled>
                        <option><?= $teacher['gender'] == 'male' ? 'ذكر' : 'أنثى' ?></option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">العنوان</label>
                    <textarea class="form-control" name="address"
                    rows="2"><?= htmlspecialchars($teacher['address']) ?></textarea>
                </div>
            </div>
        </div>
        <hr>
        <button type="submit" class="btn btn-primary px-4 rounded-pill">
            <i class="fas fa-save me-2"></i> حفظ التغييرات
        </button>
    </form>
</div>

<?php include 'teacher_footer.php'; ?>
