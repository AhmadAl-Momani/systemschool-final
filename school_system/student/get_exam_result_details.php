<?php
require_once '../config.php';
checkAuth(['student', 'teacher']);

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$result_id = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;

if (!$exam_id || !$result_id) {
    echo '<div class="alert alert-danger">معلومات غير كافية.</div>';
    exit;
}

// جلب تفاصيل النتيجة والامتحان
$result_info = $conn->query("
    SELECT er.*, e.title, e.subject 
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    WHERE er.result_id = $result_id
")->fetch_assoc();

if (!$result_info) {
    echo '<div class="alert alert-danger">لم يتم العثور على النتيجة.</div>';
    exit;
}

// جلب الأسئلة وإجابات الطالب
$questions_query = "
    SELECT 
        q.question_id, q.question_text, q.question_type, q.points, q.correct_answer,
        ea.answer AS student_answer, ea.is_correct, ea.score
    FROM exam_questions q
    LEFT JOIN exam_answers ea ON q.question_id = ea.question_id AND ea.result_id = $result_id
    WHERE q.exam_id = $exam_id
    ORDER BY q.question_id ASC
";
$questions = $conn->query($questions_query);
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="p-3 bg-light rounded-4 text-center">
            <h6 class="text-muted mb-1">الدرجة النهائية</h6>
            <h3 class="fw-bold text-primary mb-0"><?php echo $result_info['total_score']; ?> / <?php echo $result_info['max_score']; ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded-4 text-center">
            <h6 class="text-muted mb-1">النسبة المئوية</h6>
            <h3 class="fw-bold text-success mb-0"><?php echo $result_info['percentage']; ?>%</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded-4 text-center">
            <h6 class="text-muted mb-1">تاريخ التقديم</h6>
            <h6 class="fw-bold mb-0"><?php echo date('Y-m-d H:i', strtotime($result_info['completed_at'])); ?></h6>
        </div>
    </div>
</div>

<h5 class="fw-bold mb-3">تفاصيل الأسئلة:</h5>

<?php 
$q_num = 1;
while ($q = $questions->fetch_assoc()): 
    $item_class = $q['is_correct'] ? 'correct' : 'incorrect';
    $icon = $q['is_correct'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
    
    // جلب الخيارات إذا كان السؤال اختيار من متعدد
    $correct_display = $q['correct_answer'];
    if ($q['question_type'] == 'multiple_choice') {
        $opt_query = $conn->query("SELECT option_text FROM question_options WHERE question_id = {$q['question_id']} AND is_correct = 1");
        if ($opt = $opt_query->fetch_assoc()) {
            $correct_display = $opt['option_text'];
        }
    } else {
        // توحيد عرض صح/خطأ
        if (strtolower($correct_display) == 'true') $correct_display = 'صح';
        if (strtolower($correct_display) == 'false') $correct_display = 'خطأ';
    }
?>
    <div class="question-detail-item <?php echo $item_class; ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="fw-bold mb-0">سؤال <?php echo $q_num++; ?>: <?php echo htmlspecialchars($q['question_text']); ?></h6>
            <span class="badge bg-light text-dark border"><?php echo $q['score']; ?> / <?php echo $q['points']; ?> نقطة</span>
        </div>
        
        <div class="mt-3">
            <div class="mb-2">
                <span class="text-muted small d-block">إجابتك:</span>
                <span class="answer-badge <?php echo $q['is_correct'] ? 'badge-correct' : 'badge-incorrect'; ?>">
                    <i class="fas <?php echo $icon; ?> me-1"></i>
                    <?php echo htmlspecialchars($q['student_answer'] ?? 'لم يتم الإجابة'); ?>
                </span>
            </div>
            
            <?php if (!$q['is_correct']): ?>
            <div>
                <span class="text-muted small d-block">الإجابة الصحيحة:</span>
                <span class="answer-badge badge-actual">
                    <i class="fas fa-check-circle text-info me-1"></i>
                    <?php echo htmlspecialchars($correct_display); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endwhile; ?>
