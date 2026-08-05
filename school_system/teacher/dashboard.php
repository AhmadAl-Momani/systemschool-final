<?php
require_once '../config.php';
checkAuth(['teacher']);

$teacher_id = $_SESSION['user']['id'];
$page_title = "لوحة التحكم";

// Get assigned classes
$assigned_classes = $conn->query("SELECT ta.*, c.grade_level, c.class_group 
                                FROM teacher_assignments ta 
                                JOIN class c ON ta.class_id = c.class_id 
                                WHERE ta.teacher_id = $teacher_id");

// Stats based on assignments
$stats_students = $conn->query("SELECT COUNT(DISTINCT s.student_id) 
                              FROM student s 
                              JOIN teacher_assignments ta ON s.class_id = ta.class_id 
                              WHERE ta.teacher_id = $teacher_id")->fetch_row()[0];
$stats_exams = $conn->query("SELECT COUNT(*) FROM exams WHERE teacher_id = $teacher_id")->fetch_row()[0];
$stats_worksheets = $conn->query("SELECT COUNT(*) FROM worksheets WHERE teacher_id = $teacher_id")->fetch_row()[0];

include 'teacher_header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold">مرحباً، أ. <?= htmlspecialchars($_SESSION['user']['name']) ?> 👋</h3>
    <p class="text-muted">إليك نظرة سريعة على صفوفك وطلابك.</p>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card d-flex align-items-center">
            <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3"><i class="fas fa-user-graduate fa-2x"></i></div>
            <div><h6 class="text-muted mb-1">إجمالي الطلاب</h6><h3 class="fw-bold mb-0"><?= $stats_students ?></h3></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card d-flex align-items-center">
            <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3"><i class="fas fa-file-alt fa-2x"></i></div>
            <div><h6 class="text-muted mb-1">الامتحانات</h6><h3 class="fw-bold mb-0"><?= $stats_exams ?></h3></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card d-flex align-items-center">
            <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info me-3"><i class="fas fa-book fa-2x"></i></div>
            <div><h6 class="text-muted mb-1">أوراق العمل</h6><h3 class="fw-bold mb-0"><?= $stats_worksheets ?></h3></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="content-card mb-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-chalkboard text-primary me-2"></i> الصفوف والمواد المسندة</h5>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>الصف</th><th>المادة</th><th>عدد الطلاب</th></tr></thead>
                    <tbody>
                        <?php 
                        $assigned_classes->data_seek(0);
                        while($c = $assigned_classes->fetch_assoc()): 
                            $count = $conn->query("SELECT COUNT(*) FROM student WHERE class_id = {$c['class_id']}")->fetch_row()[0];
                        ?>
                        <tr>
                            <td><?= $c['grade_level'] . ' - ' . $c['class_group'] ?></td>
                            <td><span class="badge badge-soft-primary"><?= $c['subject'] ?></span></td>
                            <td><?= $count ?> طالب</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-card">
            <h5 class="fw-bold mb-4"><i class="fas fa-history text-primary me-2"></i> آخر النشاطات</h5>
            <div class="small">
                <?php
                $activities = $conn->query("
                    (SELECT 'exam' as type, title, created_at FROM exams WHERE teacher_id = $teacher_id)
                    UNION
                    (SELECT 'worksheet' as type, title, upload_date as created_at FROM worksheets WHERE teacher_id = $teacher_id)
                    ORDER BY created_at DESC LIMIT 5");
                if ($activities && $activities->num_rows > 0):
                    while($a = $activities->fetch_assoc()): ?>
                    <div class="d-flex mb-3 align-items-center">
                        <div class="me-3 text-<?= $a['type'] == 'exam' ? 'success' : 'info' ?>"><i class="fas fa-<?= $a['type'] == 'exam' ? 'check-circle' : 'file-pdf' ?>"></i></div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($a['title']) ?></div>
                            <div class="text-muted small"><?= date('Y-m-d', strtotime($a['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endwhile;
                else: ?>
                    <p class="text-muted">لا توجد نشاطات مؤخراً.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'teacher_footer.php'; ?>
