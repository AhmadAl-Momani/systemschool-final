<?php
require_once '../config.php';
checkAuth(['admin']);

if (!isset($_GET['id'])) {
  header("Location: parents.php");
  exit();
}

$parent_id = (int) $_GET['id'];
$parent = $conn->query("SELECT * FROM parent WHERE parent_id = $parent_id")->fetch_assoc();

if (!$parent) {
  $_SESSION['error_message'] = "ولي الأمر غير موجود";
  header("Location: parents.php");
  exit();
}

include '../header.php';
?>

<!-- الشريط الجانبي -->
<div class="col-md-3 sidebar p-3">
  <div class="list-group">
    <a href="dashboard.php" class="list-group-item list-group-item-action">
      <i class="fas fa-tachometer-alt"></i> لوحة التحكم
    </a>
    <a href="classes.php" class="list-group-item list-group-item-action">
      <i class="fas fa-chalkboard"></i> إدارة الصفوف
    </a>
    <a href="teachers.php" class="list-group-item list-group-item-action">
      <i class="fas fa-chalkboard-teacher"></i> إدارة المعلمين
    </a>
    <a href="students.php" class="list-group-item list-group-item-action">
      <i class="fas fa-users"></i> إدارة الطلاب
    </a>
    <a href="parents.php" class="list-group-item list-group-item-action active">
      <i class="fas fa-user-friends"></i> إدارة أولياء الأمور
    </a>
    <a href="reports.php" class="list-group-item list-group-item-action">
      <i class="fas fa-chart-bar"></i> التقارير والإحصائيات
    </a>
    <a href="numbers.php" class="list-group-item list-group-item-action">
      <i class="fas fa-chart-bar"></i>الارقام
    </a>
  </div>
    <div class="p-4">
                <a href="../logout.php" class="nav-link-custom text-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
</div>

<!-- المحتوى الرئيسي -->
<div class="col-md-9 p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
      <i class="fas fa-user-edit"></i> تعديل بيانات ولي الأمر:
      <?= htmlspecialchars($parent['pname']) ?>
    </h2>
    <a href="parents.php" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> رجوع
    </a>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-info-circle"></i> المعلومات الأساسية
      </h5>
    </div>
    <div class="card-body">
      <form action="process_parent.php" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="parent_id" value="<?= $parent['parent_id'] ?>">

        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">اسم ولي الأمر <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="pname" value="<?= htmlspecialchars($parent['pname']) ?>"
                required>
            </div>

            <div class="mb-3">
              <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($parent['email']) ?>"
                required>
            </div>

            <div class="mb-3">
              <label class="form-label">كلمة المرور الجديدة</label>
              <input type="password" class="form-control" name="password"
                placeholder="اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور">
              <small class="text-muted">يجب أن تحتوي كلمة المرور على الأقل على 8 أحرف</small>
            </div>
          </div>

          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">رقم الهاتف</label>
              <input type="tel" class="form-control" name="phone_num"
                value="<?= htmlspecialchars($parent['phone_num'] ?? '') ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">الرقم الوطني</label>
              <input type="text" class="form-control" name="natnum"
                value="<?= htmlspecialchars($parent['natnum'] ?? '') ?>">
            </div>

            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $parent['is_active'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_active">حساب نشط</label>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <a href="parent_children.php?id=<?= $parent['parent_id'] ?>" class="btn btn-info">
            <i class="fas fa-users"></i> عرض الأبناء
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> حفظ التغييرات
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- معلومات إضافية -->
  <div class="card mt-4">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-history"></i> السجل الزمني
      </h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <p><strong>تاريخ الإنشاء:</strong> <?= date('Y-m-d H:i', strtotime($parent['created_at'])) ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>آخر تحديث:</strong>
            <?= isset($parent['updated_at']) ? date('Y-m-d H:i', strtotime($parent['updated_at'])) : '---' ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>