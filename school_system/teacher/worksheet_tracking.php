<?php
require_once '../config.php';
checkAuth(['teacher']);

$teacher_id = $_SESSION['user']['id'];
$page_title = "تتبع أوراق العمل";

// جلب أوراق العمل الخاصة بالمعلم
$worksheets = $conn->query("
    SELECT w.worksheet_id, w.title, c.grade_level, c.class_group 
    FROM worksheets w
    JOIN class c ON w.class_id = c.class_id
    WHERE w.teacher_id = $teacher_id
    ORDER BY w.upload_date DESC
");

// الحصول على ورقة العمل المختارة
$selected_ws_id = isset($_GET['ws_id']) ? (int)$_GET['ws_id'] : 0;

// جلب سجلات التحميل لورقة العمل المختارة
$downloads = null;
if ($selected_ws_id > 0) {
    $downloads = $conn->query("
        SELECT s.stuname, wv.downloaded_at, c.grade_level, c.class_group
        FROM worksheet_views wv
        JOIN student s ON wv.student_id = s.student_id
        JOIN class c ON s.class_id = c.class_id
        WHERE wv.worksheet_id = $selected_ws_id AND wv.downloaded_at IS NOT NULL
        ORDER BY wv.downloaded_at DESC
    ");
}

include 'teacher_header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold"><i class="fas fa-history text-primary me-2"></i> تتبع تحميل أوراق العمل</h3>
    <p class="text-muted">تعرف على الطلاب الذين قاموا بتحميل أوراق العمل الخاصة بك.</p>
</div>

<div class="content-card mb-4">
    <h5 class="fw-bold mb-3">اختر ورقة العمل</h5>
    <form method="GET" class="row align-items-end">
        <div class="col-md-6">
            <select class="form-select" name="ws_id" onchange="this.form.submit()">
                <option value="">-- اختر ورقة العمل --</option>
                <?php while ($ws = $worksheets->fetch_assoc()): ?>
                <option value="<?= $ws['worksheet_id'] ?>" <?= ($selected_ws_id == $ws['worksheet_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ws['title']) ?> (<?= $ws['grade_level'] ?> - <?= $ws['class_group'] ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<div class="content-card">
    <h5 class="fw-bold mb-3">قائمة الطلاب الذين قاموا بالتحميل</h5>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الطالب</th>
                    <th>الصف</th>
                    <th>تاريخ التحميل</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($downloads && $downloads->num_rows > 0): ?>
                    <?php $count = 1; while ($row = $downloads->fetch_assoc()): ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td><div class="fw-bold"><?= htmlspecialchars($row['stuname']) ?></div></td>
                        <td><?= $row['grade_level'] ?> - <?= $row['class_group'] ?></td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <i class="far fa-clock me-1"></i> <?= date('Y-m-d H:i', strtotime($row['downloaded_at'])) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <?php if ($selected_ws_id > 0): ?>
                                <i class="fas fa-info-circle fa-3x mb-3 opacity-25"></i>
                                <p>لا توجد سجلات تحميل لهذه الورقة حتى الآن.</p>
                            <?php else: ?>
                                <i class="fas fa-mouse-pointer fa-3x mb-3 opacity-25"></i>
                                <p>يرجى اختيار ورقة عمل من القائمة أعلاه لعرض السجلات.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'teacher_footer.php'; ?>
