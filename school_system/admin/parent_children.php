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

// جلب أبناء ولي الأمر مع معلومات الصفوف
$children_query = "
    SELECT s.student_id, s.stuname, s.gender, s.profile_pic, 
           c.class_name, c.grade_level, c.section,
           ps.relationship
    FROM parent_student ps
    JOIN student s ON ps.student_id = s.student_id
    LEFT JOIN class c ON s.class_id = c.class_id
    WHERE ps.parent_id = $parent_id
    ORDER BY s.stuname
";
$children = $conn->query($children_query);

// جلب الطلاب غير المرتبطين بهذا ولي الأمر لإضافتهم
$available_students = $conn->query("
    SELECT s.student_id, s.stuname, c.class_name
    FROM student s
    LEFT JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id NOT IN (
        SELECT student_id FROM parent_student WHERE parent_id = $parent_id
    )
    ORDER BY s.stuname
");

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
</div>

<!-- المحتوى الرئيسي -->
<div class="col-md-9 p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
      <i class="fas fa-users"></i> أبناء ولي الأمر:
      <?= htmlspecialchars($parent['pname']) ?>
    </h2>
    <div>
      <a href="parents.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> رجوع
      </a>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChildModal">
        <i class="fas fa-plus"></i> إضافة ابن
      </button>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-info-circle"></i> معلومات ولي الأمر
      </h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <p><strong>الاسم:</strong> <?= htmlspecialchars($parent['pname']) ?></p>
          <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($parent['email']) ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>رقم الهاتف:</strong> <?= $parent['phone_num'] ?: '---' ?></p>
          <p><strong>الحالة:</strong>
            <span class="badge bg-<?= $parent['is_active'] ? 'success' : 'danger' ?>">
              <?= $parent['is_active'] ? 'نشط' : 'غير نشط' ?>
            </span>
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- قائمة الأبناء -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-users"></i> قائمة الأبناء (<?= $children->num_rows ?>)
      </h5>
    </div>

    <div class="card-body">
      <?php if ($children->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>الصورة</th>
                <th>اسم الطالب</th>
                <th>الصف</th>
                <th>المستوى</th>
                <th>الشعبة</th>
                <th>الجنس</th>
                <th>صلة القرابة</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <?php $count = 1;
              while ($child = $children->fetch_assoc()): ?>
                <tr>
                  <td><?= $count++ ?></td>
                  <td>
                    <?php if (!empty($child['profile_pic'])): ?>
                      <img src="<?= BASE_URL ?>uploads/students/<?= htmlspecialchars($child['profile_pic']) ?>" width="40"
                        height="40" class="rounded-circle">
                    <?php else: ?>
                      <div
                        class="no-photo bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                        style="width:40px; height:40px;">
                        <i class="fas fa-user"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($child['stuname']) ?></td>
                  <td><?= $child['class_name'] ?: '---' ?></td>
                  <td><?= $child['grade_level'] ?: '---' ?></td>
                  <td><?= $child['section'] ?: '---' ?></td>
                  <td><?= $child['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td>
                  <td><?= htmlspecialchars($child['relationship']) ?></td>
                  <td>
                    <button class="btn btn-sm btn-danger"
                      onclick="confirmRemove(<?= $parent_id ?>, <?= $child['student_id'] ?>, '<?= htmlspecialchars($child['stuname']) ?>')">
                      <i class="fas fa-user-minus"></i> إزالة
                    </button>
                    <a href="student_details.php?id=<?= $child['student_id'] ?>" class="btn btn-sm btn-info">
                      <i class="fas fa-eye"></i> عرض
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> لا يوجد أبناء مسجلين لهذا ولي الأمر
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal إضافة ابن -->
<div class="modal fade" id="addChildModal" tabindex="-1" aria-labelledby="addChildModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addChildModalLabel">
          <i class="fas fa-user-plus"></i> إضافة ابن لولي الأمر
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="process_parent.php" method="POST">
        <input type="hidden" name="action" value="add_child">
        <input type="hidden" name="parent_id" value="<?= $parent_id ?>">

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">اختر الطالب <span class="text-danger">*</span></label>
            <select class="form-select" name="student_id" required>
              <option value="">-- اختر طالب --</option>
              <?php while ($student = $available_students->fetch_assoc()): ?>
                <option value="<?= $student['student_id'] ?>">
                  <?= htmlspecialchars($student['stuname']) ?> -
                  <?= $student['class_name'] ? htmlspecialchars($student['class_name']) : 'لا يوجد صف' ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">صلة القرابة <span class="text-danger">*</span></label>
            <select class="form-select" name="relationship" required>
              <option value="أب">أب</option>
              <option value="أم">أم</option>
              <option value="وصي">وصي</option>
              <option value="أخ">أخ</option>
              <option value="جد">جد</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
          <button type="submit" class="btn btn-primary">إضافة</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function confirmRemove(parentId, studentId, studentName) {
    if (confirm(`هل أنت متأكد من إزالة الطالب ${studentName} من قائمة الأبناء؟`)) {
      window.location.href = `process_parent.php?action=remove_child&parent_id=${parentId}&student_id=${studentId}`;
    }
  }
</script>

<?php include '../footer.php'; ?>