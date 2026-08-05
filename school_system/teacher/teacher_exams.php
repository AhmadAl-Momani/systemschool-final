<?php
require_once '../config.php';
checkAuth(['teacher']);

$teacher_id = $_SESSION['user']['id'];
$page_title = "إدارة الامتحانات";

// التحقق مما إذا كان المطلوب إدارة الأسئلة لامتحان معين
$manage_questions_id = isset($_GET['manage_questions']) ? (int)$_GET['manage_questions'] : 0;
$exam_to_manage = null;

if ($manage_questions_id > 0) {
    $exam_to_manage = $conn->query("SELECT * FROM exams WHERE exam_id = $manage_questions_id AND teacher_id = $teacher_id")->fetch_assoc();
    if (!$exam_to_manage) {
        $_SESSION['error_message'] = "الامتحان غير موجود أو لا تملك صلاحية الوصول إليه";
        header("Location: teacher_exams.php");
        exit();
    }
}

$exams = $conn->query("
    SELECT e.*, COUNT(q.question_id) as questions_count, c.grade_level, c.class_group
    FROM exams e
    LEFT JOIN exam_questions q ON e.exam_id = q.exam_id
    LEFT JOIN class c ON e.class_id = c.class_id
    WHERE e.teacher_id = $teacher_id
    GROUP BY e.exam_id
    ORDER BY e.created_at DESC
");

$assigned_classes = $conn->query("SELECT ta.*, c.grade_level, c.class_group 
                                FROM teacher_assignments ta 
                                JOIN class c ON ta.class_id = c.class_id 
                                WHERE ta.teacher_id = $teacher_id");

include 'teacher_header.php';
?>

<?php if ($manage_questions_id > 0 && $exam_to_manage): ?>
    <!-- واجهة إدارة الأسئلة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-question-circle text-primary me-2"></i> إدارة أسئلة: <?= htmlspecialchars($exam_to_manage['title']) ?></h3>
        <a href="teacher_exams.php" class="btn btn-outline-secondary px-4 rounded-pill">
            <i class="fas fa-arrow-right me-2"></i> العودة للقائمة
        </a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="content-card mb-4">
                <h5 class="fw-bold mb-3">إضافة سؤال جديد</h5>
                <form id="addQuestionForm">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="exam_id" value="<?= $manage_questions_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">نص السؤال</label>
                        <textarea name="question_text" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">نوع السؤال</label>
                        <select name="question_type" class="form-select" id="questionType" onchange="toggleQuestionOptions()">
                            <option value="multiple_choice">متعدد الخيارات</option>
                            <option value="true_false">صح / خطأ</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الدرجة</label>
                        <input type="number" name="points" class="form-control" value="1" min="1" required>
                    </div>

                    <!-- خيارات متعدد الخيارات -->
                    <div id="mcOptions">
                        <label class="form-label">الخيارات (حدد الإجابة الصحيحة)</label>
                        <?php for($i=0; $i<4; $i++): ?>
                        <div class="input-group mb-2">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="radio" name="correct_option" value="<?= $i ?>" <?= $i==0 ? 'checked' : '' ?>>
                            </div>
                            <input type="text" name="options[]" class="form-control" placeholder="الخيار <?= $i+1 ?>" <?= $i<2 ? 'required' : '' ?>>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- خيارات صح/خطأ -->
                    <div id="tfOptions" style="display:none;">
                        <label class="form-label">الإجابة الصحيحة</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="correct_answer" value="true" checked>
                                <label class="form-check-label">صح</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="correct_answer" value="false">
                                <label class="form-check-label">خطأ</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-3 rounded-pill">إضافة السؤال</button>
                </form>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="content-card">
                <h5 class="fw-bold mb-3">الأسئلة الحالية</h5>
                <div id="questionsList">
                    <!-- سيتم تحميل الأسئلة هنا عبر AJAX -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">جاري تحميل الأسئلة...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleQuestionOptions() {
        const type = document.getElementById('questionType').value;
        document.getElementById('mcOptions').style.display = type === 'multiple_choice' ? 'block' : 'none';
        document.getElementById('tfOptions').style.display = type === 'true_false' ? 'block' : 'none';
        
        // تعطيل المدخلات غير المستخدمة لمنع إرسالها
        const mcInputs = document.querySelectorAll('#mcOptions input');
        const tfInputs = document.querySelectorAll('#tfOptions input');
        
        if (type === 'multiple_choice') {
            mcInputs.forEach(i => i.disabled = false);
            tfInputs.forEach(i => i.disabled = true);
        } else {
            mcInputs.forEach(i => i.disabled = true);
            tfInputs.forEach(i => i.disabled = false);
        }
    }

    function loadQuestions() {
        fetch('load_exam_questions.php?exam_id=<?= $manage_questions_id ?>')
            .then(response => response.text())
            .then(html => {
                document.getElementById('questionsList').innerHTML = html;
            });
    }

    document.getElementById('addQuestionForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('process_question.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'نجاح',
                    text: data.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                this.reset();
                // إعادة تعيين نص السؤال يدوياً لأن reset قد لا يمسح textarea في بعض المتصفحات
                this.querySelector('textarea[name="question_text"]').value = '';
                toggleQuestionOptions();
                loadQuestions();
            } else {
                Swal.fire('خطأ', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('خطأ', 'حدث خطأ في الاتصال بالسيرفر', 'error');
        });
    };

    function deleteQuestion(qId, eId) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم حذف السؤال نهائياً!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('question_id', qId);
                formData.append('exam_id', eId);

                fetch('process_question.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadQuestions();
                    } else {
                        Swal.fire('خطأ', data.message, 'error');
                    }
                });
            }
        });
    }

    window.onload = function() {
        loadQuestions();
        toggleQuestionOptions();
    };
    </script>

<?php else: ?>
    <!-- واجهة قائمة الامتحانات -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-edit text-primary me-2"></i> إدارة الامتحانات</h3>
        <button class="btn btn-primary px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#addExamModal">
            <i class="fas fa-plus me-2"></i> امتحان جديد
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
                        <th>الأسئلة</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($exams && $exams->num_rows > 0): ?>
                        <?php while ($exam = $exams->fetch_assoc()): ?>
                        <tr>
                            <td><div class="fw-bold"><?= htmlspecialchars($exam['title']) ?></div><div class="small text-muted"><?= date('Y-m-d', strtotime($exam['start_time'])) ?></div></td>
                            <td><?= $exam['grade_level'] . ' - ' . $exam['class_group'] ?></td>
                            <td><?= htmlspecialchars($exam['subject']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= $exam['questions_count'] ?> سؤال</span></td>
                            <td>
                                <?php
                                $now = time(); $start = strtotime($exam['start_time']); $end = strtotime($exam['end_time']);
                                if ($now < $start) echo '<span class="badge bg-warning">لم يبدأ</span>';
                                elseif ($now <= $end) echo '<span class="badge bg-success">جاري</span>';
                                else echo '<span class="badge bg-secondary">منتهي</span>';
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="addQuestionsToExam(<?= $exam['exam_id'] ?>)" title="إدارة الأسئلة"><i class="fas fa-question-circle"></i></button>
                                <button class="btn btn-sm btn-outline-warning me-1" onclick="editExam(<?= $exam['exam_id'] ?>)" title="تعديل"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteExam(<?= $exam['exam_id'] ?>)" title="حذف"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد امتحانات مضافة حالياً.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal إضافة امتحان جديد -->
    <div class="modal fade" id="addExamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="process_teacher_exam.php" method="POST" class="modal-content">
                <input type="hidden" name="action" value="add">
                <div class="modal-header"><h5 class="modal-title">إضافة امتحان جديد</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row">
                    <div class="col-md-6 mb-3"><label class="form-label">عنوان الامتحان</label><input type="text" name="title" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الصف والمادة</label>
                        <select name="assignment_id" class="form-select" required onchange="updateSubject(this)">
                            <option value="">-- اختر الصف --</option>
                            <?php 
                            $assigned_classes->data_seek(0);
                            while($ac = $assigned_classes->fetch_assoc()): ?>
                            <option value="<?= $ac['class_id'] ?>" data-subject="<?= $ac['subject'] ?>"><?= $ac['grade_level'] . ' - ' . $ac['class_group'] ?> (<?= $ac['subject'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <input type="hidden" name="class_id" id="add_class_id">
                        <input type="hidden" name="subject" id="add_subject">
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">كلمة المرور</label><input type="text" name="password" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">المدة (دقائق)</label><input type="number" name="duration" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">تاريخ البدء</label><input type="datetime-local" name="start_time" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">تاريخ الانتهاء</label><input type="datetime-local" name="end_time" class="form-control" required></div>
                    <div class="col-12 mb-3"><label class="form-label">التعليمات</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary px-4">حفظ</button></div>
            </form>
        </div>
    </div>

    <script>
    function updateSubject(select) {
        const option = select.options[select.selectedIndex];
        document.getElementById('add_class_id').value = select.value;
        document.getElementById('add_subject').value = option.getAttribute('data-subject');
    }
    function addQuestionsToExam(id) { window.location.href = 'teacher_exams.php?manage_questions=' + id; }
    function editExam(id) { /* logic to load and show edit modal */ }
    function confirmDeleteExam(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم حذف الامتحان وجميع الأسئلة والنتائج المرتبطة به!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'process_teacher_exam.php?action=delete&id=' + id;
            }
        });
    }
    </script>
<?php endif; ?>

<?php include 'teacher_footer.php'; ?>
