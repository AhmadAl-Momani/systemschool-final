<?php
require_once '../config.php';
checkAuth(['teacher']);

$teacher_id = $_SESSION['user']['id'];
$page_title = "أوراق العمل";

$worksheets = $conn->query("
    SELECT w.*, c.grade_level, c.class_group 
    FROM worksheets w
    LEFT JOIN class c ON w.class_id = c.class_id
    WHERE w.teacher_id = $teacher_id
    ORDER BY w.upload_date DESC
");

$assigned_classes = $conn->query("SELECT ta.*, c.grade_level, c.class_group 
                                FROM teacher_assignments ta 
                                JOIN class c ON ta.class_id = c.class_id 
                                WHERE ta.teacher_id = $teacher_id");

include 'teacher_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="fas fa-file-pdf text-primary me-2"></i> أوراق العمل</h3>
    <button class="btn btn-primary px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#addWorksheetModal">
        <i class="fas fa-upload me-2"></i> رفع ورقة عمل
    </button>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>الصف</th>
                    <th>المبحث</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($worksheets && $worksheets->num_rows > 0): ?>
                    <?php while ($ws = $worksheets->fetch_assoc()): ?>
                    <tr>
                        <td><div class="fw-bold"><?= htmlspecialchars($ws['title']) ?></div></td>
                        <td><?= $ws['grade_level'] . ' - ' . $ws['class_group'] ?></td>
                        <td><?= htmlspecialchars($ws['subject']) ?></td>
                        <td><?= date('Y-m-d', strtotime($ws['upload_date'])) ?></td>
                        <td>
                            <a href="../uploads/worksheets/<?= $ws['file_path'] ?>" class="btn btn-sm btn-outline-success me-1" download><i class="fas fa-download"></i></a>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $ws['worksheet_id'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">لا توجد أوراق عمل مرفوعة حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal رفع ورقة عمل جديدة -->
<div class="modal fade" id="addWorksheetModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="process_teacher_worksheet.php" method="POST" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="add">
            <div class="modal-header"><h5 class="modal-title">رفع ورقة عمل جديدة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">عنوان الورقة</label><input type="text" name="title" class="form-control" required></div>
                <div class="mb-3">
                    <label class="form-label">الصف والمادة</label>
                    <select name="assignment_data" class="form-select" required onchange="updateWSFields(this)">
                        <option value="">-- اختر الصف --</option>
                        <?php 
                        $assigned_classes->data_seek(0);
                        while($ac = $assigned_classes->fetch_assoc()): ?>
                        <option value="<?= $ac['class_id'] ?>" data-subject="<?= $ac['subject'] ?>"><?= $ac['grade_level'] . ' - ' . $ac['class_group'] ?> (<?= $ac['subject'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                    <input type="hidden" name="class_id" id="ws_class_id">
                    <input type="hidden" name="subject" id="ws_subject">
                </div>
                <div class="mb-3"><label class="form-label">الملف (PDF, Word)</label><input type="file" name="worksheet_file" class="form-control" accept=".pdf,.doc,.docx" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary px-4">رفع الآن</button></div>
        </form>
    </div>
</div>

<script>
function updateWSFields(select) {
    const option = select.options[select.selectedIndex];
    document.getElementById('ws_class_id').value = select.value;
    document.getElementById('ws_subject').value = option.getAttribute('data-subject');
}
function confirmDelete(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيتم حذف الملف نهائياً!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'process_teacher_worksheet.php?action=delete&id=' + id;
        }
    });
}
</script>

<?php include 'teacher_footer.php'; ?>
