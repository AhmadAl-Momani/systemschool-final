<?php
require_once '../config.php';
checkAuth(['teacher']);

$teacher_id = $_SESSION['user']['id'];
$page_title = "طلاب الصفوف المسندة";

// جلب الصفوف المسندة للمعلم
$assigned_classes = $conn->query("
    SELECT ta.class_id, c.grade_level, c.class_group, ta.subject
    FROM teacher_assignments ta
    JOIN class c ON ta.class_id = c.class_id
    WHERE ta.teacher_id = $teacher_id
");

// الحصول على الصف المختار من الفلتر
$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// إذا لم يتم اختيار صف، نختار أول صف مسند تلقائياً
if ($selected_class_id == 0 && $assigned_classes->num_rows > 0) {
    $assigned_classes->data_seek(0);
    $first_class = $assigned_classes->fetch_assoc();
    $selected_class_id = $first_class['class_id'];
}

// جلب الطلاب بناءً على الصف المختار
$students = null;
if ($selected_class_id > 0) {
    $students = $conn->query("
        SELECT s.* 
        FROM student s 
        WHERE s.class_id = $selected_class_id
        ORDER BY s.stuname
    ");
}

include 'teacher_header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold"><i class="fas fa-user-graduate text-primary me-2"></i> طلابي</h3>
    <p class="text-muted">عرض الطلاب المسجلين في الصفوف المسندة إليك.</p>
</div>

<div class="content-card mb-4">
    <h5 class="fw-bold mb-3">تصفية حسب الصف</h5>
    <form method="GET" class="row align-items-end">
        <div class="col-md-6">
            <label class="form-label">اختر الصف</label>
            <select class="form-select" name="class_id" onchange="this.form.submit()">
                <option value="">-- اختر الصف --</option>
                <?php 
                $assigned_classes->data_seek(0);
                while ($ac = $assigned_classes->fetch_assoc()): ?>
                <option value="<?= $ac['class_id'] ?>" <?= ($selected_class_id == $ac['class_id']) ? 'selected' : '' ?>>
                    <?= $ac['grade_level'] . ' - ' . $ac['class_group'] ?> (<?= $ac['subject'] ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<div class="content-card">
    <h5 class="fw-bold mb-3">قائمة الطلاب</h5>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الصورة</th>
                    <th>اسم الطالب</th>
                    <th>الجنس</th>
                    <th>بريد ولي الأمر</th>
                    <th class="text-end">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students && $students->num_rows > 0): ?>
                    <?php $count = 1; while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td>
                            <?php if (!empty($student['profile_pic'])): ?>
                                <img src="../uploads/students/<?= $student['profile_pic'] ?>" class="student-img" alt="Student">
                            <?php else: ?>
                                <div class="no-photo-circle">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><div class="fw-bold"><?= htmlspecialchars($student['stuname']) ?></div></td>
                        <td>
                            <span class="badge bg-light text-dark rounded-pill px-3">
                                <?= $student['gender'] == 'male' ? 'ذكر' : 'أنثى' ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= $student['parent_email'] ?: '---' ?></td>
                        <td class="text-end">
                            <a href="student_results.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fas fa-chart-line me-1"></i> النتائج
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i>
                            <p>لا يوجد طلاب مسجلين في هذا الصف حالياً</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'teacher_footer.php'; ?>
