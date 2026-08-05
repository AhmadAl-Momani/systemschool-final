<?php
require_once '../config.php';
checkAuth(['teacher']);

$teacher_id = $_SESSION['user']['id'];
$page_title = "نتائج الطلاب";

// جلب جميع امتحانات المعلم
$exams = $conn->query("SELECT exam_id, title FROM exams WHERE teacher_id = $teacher_id ORDER BY created_at DESC");

// جلب نتائج الطلاب لآخر امتحان
$last_exam_id = $conn->query("SELECT exam_id FROM exams WHERE teacher_id = $teacher_id ORDER BY created_at DESC LIMIT 1")->fetch_row()[0] ?? 0;

// جلب نتائج الامتحان المحدد
$selected_exam_id = $_GET['exam_id'] ?? $last_exam_id;
$results_query = $conn->query("
    SELECT s.stuname, er.* 
    FROM exam_results er
    JOIN student s ON er.student_id = s.student_id
    WHERE er.exam_id = $selected_exam_id
    ORDER BY er.percentage DESC
");

// تحويل النتائج إلى مصفوفة للعد
$results = [];
if ($results_query) {
  $results = $results_query->fetch_all(MYSQLI_ASSOC);
}

include 'teacher_header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold"><i class="fas fa-chart-bar text-primary me-2"></i> نتائج الطلاب</h3>
</div>

<div class="content-card mb-4">
    <h5 class="fw-bold mb-3">تصفية النتائج</h5>
    <form method="GET" class="row align-items-end">
        <div class="col-md-6">
            <label class="form-label">اختر الامتحان</label>
            <select class="form-select" name="exam_id" onchange="this.form.submit()">
                <option value="">-- اختر الامتحان --</option>
                <?php 
                $exams->data_seek(0);
                while ($exam = $exams->fetch_assoc()): ?>
                <option value="<?= $exam['exam_id'] ?>" <?= ($selected_exam_id == $exam['exam_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($exam['title']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<div class="content-card">
    <h5 class="fw-bold mb-3">نتائج الامتحان</h5>
    <?php if (!empty($results)): ?>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الطالب</th>
                    <th>الدرجة الكاملة</th>
                    <th>النسبة المئوية</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1;
                foreach ($results as $result): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><div class="fw-bold"><?= htmlspecialchars($result['stuname']) ?></div></td>
                    <td><?= $result['total_score'] ?> / <?= $result['max_score'] ?></td>
                    <td style="width: 200px;">
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?= $result['percentage'] >= 50 ? 'bg-success' : 'bg-danger' ?>"
                            role="progressbar" style="width: <?= $result['percentage'] ?>%"
                            aria-valuenow="<?= $result['percentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="small mt-1"><?= $result['percentage'] ?>%</div>
                    </td>
                    <td><?= date('Y-m-d', strtotime($result['completed_at'])) ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info show-exam-details" 
                                data-exam-id="<?= $result['exam_id'] ?>" 
                                data-result-id="<?= $result['result_id'] ?>">
                            <i class="fas fa-eye"></i> تفاصيل الإجابات
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info mb-0">
        لا توجد نتائج متاحة للعرض لهذا الامتحان.
    </div>
    <?php endif; ?>
</div>

<!-- Modal تفاصيل الإجابات (للمعلم) -->
<div class="modal fade" id="examResultModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">تفاصيل إجابات الطالب</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">جاري تحميل التفاصيل...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* إضافة ستايلات تفاصيل الأسئلة المتوافقة مع لوحة المعلم */
    .question-detail-item {
        background: #f8fafc;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-right: 4px solid #e2e8f0;
    }
    .question-detail-item.correct { border-right-color: #10b981; }
    .question-detail-item.incorrect { border-right-color: #ef4444; }
    
    .answer-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 5px;
    }
    .badge-correct { background: #dcfce7; color: #166534; }
    .badge-incorrect { background: #fee2e2; color: #991b1b; }
    .badge-actual { background: #e0f2fe; color: #075985; }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('.show-exam-details').on('click', function() {
            const examId = $(this).data('exam-id');
            const resultId = $(this).data('result-id');
            
            $('#examResultModal').modal('show');
            $('#resultDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">جاري تحميل التفاصيل...</p></div>');
            
            $.ajax({
                url: '../student/get_exam_result_details.php',
                type: 'GET',
                data: { exam_id: examId, result_id: resultId },
                success: function(response) {
                    $('#resultDetailsContent').html(response);
                },
                error: function() {
                    $('#resultDetailsContent').html('<div class="alert alert-danger">حدث خطأ أثناء تحميل البيانات.</div>');
                }
            });
        });
    });
</script>

<?php include 'teacher_footer.php'; ?>
