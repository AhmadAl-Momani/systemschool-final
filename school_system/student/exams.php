<?php
require_once '../config.php';
checkAuth(['student']);

$student_id = $_SESSION['user']['id'];

// جلب معلومات الطالب مع مراعاة تغيير class_name إلى grade_level
$student = $conn->query("
    SELECT s.*, c.grade_level, c.class_group
    FROM student s
    JOIN class c ON s.class_id = c.class_id
    WHERE s.student_id = $student_id
")->fetch_assoc();

$class_id = $_SESSION['user']['class_id'] ?? $conn->query("SELECT class_id FROM student WHERE student_id = $student_id")->fetch_row()[0];

// استعلام الامتحانات
$exams_query = "
    SELECT 
        e.exam_id, e.title, e.subject, e.description, e.duration,
        e.start_time, e.end_time, e.password, e.created_at,
        t.tname AS teacher_name,
        er.result_id, er.total_score, er.max_score, er.percentage, er.completed_at,
        (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.exam_id) AS questions_count
    FROM exams e
    JOIN teacher t ON e.teacher_id = t.teacher_id
    LEFT JOIN exam_results er ON e.exam_id = er.exam_id AND er.student_id = $student_id
    WHERE e.class_id IS NULL OR e.class_id = $class_id
    ORDER BY e.start_time DESC
";

$exams = $conn->query($exams_query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الامتحانات الرسمية - بوابة الطالب</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #0f172a;
            --accent-gold: #fbbf24;
            --accent-blue: #3b82f6;
            --text-light: #f8fafc;
            --bg-soft: #f1f5f9;
            --card-bg: #ffffff;
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #ef4444;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-soft);
            color: var(--primary-dark);
            margin: 0;
            overflow-x: hidden;
        }

        /* Preloader */
        #loader {
            position: fixed;
            inset: 0;
            background: var(--primary-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.6s ease;
        }

        .loader-circle {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(251, 191, 36, 0.1);
            border-top: 8px solid var(--accent-gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* Navbar Styling */
        .navbar {
            background: var(--primary-dark);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .navbar-brand {
            color: var(--accent-gold) !important;
            font-weight: 800;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            color: var(--text-light) !important;
            font-weight: 600;
            padding: 0.8rem 1.2rem !important;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            background: rgba(251, 191, 36, 0.1);
            color: var(--accent-gold) !important;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: var(--accent-gold);
            color: var(--primary-dark) !important;
        }

        /* Header Section */
        .page-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e293b 100%);
            padding: 4rem 2rem;
            color: white;
            text-align: center;
            border-radius: 0 0 50px 50px;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .page-header h1 {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--accent-gold);
            margin-bottom: 1rem;
        }

        /* Exam Card */
        .exam-card {
            background: white;
            border: none;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .exam-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .exam-card-header {
            background: var(--primary-dark);
            padding: 1.5rem;
            color: var(--accent-gold);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .exam-status-badge {
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-active { background: var(--success-green); color: white; }
        .status-upcoming { background: var(--warning-orange); color: white; }
        .status-ended { background: #64748b; color: white; }

        .exam-card-body {
            padding: 2rem;
            flex-grow: 1;
        }

        .exam-title {
            font-weight: 800;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .exam-subject {
            color: var(--accent-blue);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: block;
        }

        .exam-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .info-item i {
            color: var(--accent-gold);
            width: 18px;
        }

        .exam-card-footer {
            padding: 1.5rem 2rem;
            background: var(--bg-soft);
            border-top: 1px solid rgba(0,0,0,0.05);
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            border: none;
            border-radius: 15px;
            padding: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-start { background: var(--primary-dark); color: var(--accent-gold); }
        .btn-start:hover { background: var(--accent-gold); color: var(--primary-dark); }
        
        .btn-result { background: var(--success-green); color: white; }
        .btn-result:hover { opacity: 0.9; transform: scale(1.02); }

        .btn-details { background: #e2e8f0; color: var(--primary-dark); }
        .btn-details:hover { background: #cbd5e1; }

        /* Result Badge on Card */
        .result-overlay {
            position: absolute;
            top: 60px;
            right: 20px;
            background: white;
            padding: 10px 20px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border: 2px solid var(--success-green);
            z-index: 5;
        }

        .result-overlay .score {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--success-green);
            display: block;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 30px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--primary-dark);
            color: var(--accent-gold);
            padding: 1.5rem 2rem;
            border: none;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 15px;
            padding: 0.8rem 1.2rem;
            border: 2px solid #e2e8f0;
        }

        .form-control:focus {
            border-color: var(--accent-gold);
            box-shadow: none;
        }

        /* Profile Chip */
        .profile-chip {
            background: rgba(255,255,255,0.1);
            padding: 6px 18px 6px 6px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .profile-chip img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--accent-gold);
        }

        /* Question Details in Modal */
        .question-detail-item {
            background: #f8fafc;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-right: 4px solid #e2e8f0;
        }
        .question-detail-item.correct { border-right-color: var(--success-green); }
        .question-detail-item.incorrect { border-right-color: var(--danger-red); }
        
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

        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .exam-info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Loader -->
    <div id="loader">
        <div class="loader-circle"></div>
        <h4 class="mt-4 text-white fw-bold animate__animated animate__pulse animate__infinite">جاري تجهيز الامتحانات...</h4>
    </div>

    <!-- Navbar -->

        <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap fa-lg"></i>
                <span>أكاديميتي</span>
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link " href="dashboard.php"><i class="fas fa-th-large"></i> الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="worksheets.php"><i class="fas fa-file-pdf"></i> أوراق العمل</a></li>
                    <li class="nav-item"><a class="nav-link active" href="exams.php"><i class="fas fa-pen-nib"></i> الامتحانات</a></li>
                    <li class="nav-item"><a class="nav-link" href="random_exams.php"><i class="fas fa-dice"></i> اختبارات عشوائية</a></li>
                    <li class="nav-item"><a class="nav-link" href="results.php"><i class="fas fa-chart-bar"></i> نتائجي</a></li>
                </ul>
                 <div class="d-flex align-items-center gap-2">
                    <div class="profile-chip">
                        <div class="text-end text-white d-none d-md-block">
                            <div class="fw-bold small"><?php echo $student['stuname']; ?></div>
                            <div style="font-size: 10px; color: var(--accent-gold);"><?php echo $student['grade_level']; ?></div>
                        </div>
                        <img src="../uploads/students/<?php echo !empty($student['profile_pic']) ? $student['profile_pic'] : 'male-student.png'; ?>" alt="Profile">
                    </div>
                    <a href="../logout.php" class="nav-link logout-btn" title="تسجيل الخروج">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>


    <div class="page-header">
        <div class="container">
            <h1 class="animate__animated animate__fadeInDown">الامتحانات الرسمية</h1>
            <p class="lead text-white-50 animate__animated animate__fadeInUp">اختبر مهاراتك وحقق النجاح في مسيرتك التعليمية</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row g-4">
            <?php if ($exams->num_rows > 0): ?>
                <?php while ($exam = $exams->fetch_assoc()): 
                    $now = time();
                    $start_time = strtotime($exam['start_time']);
                    $end_time = strtotime($exam['end_time']);
                    
                    $status_class = '';
                    $status_text = '';
                    $can_start = false;

                    if ($now < $start_time) {
                        $status_class = 'status-upcoming';
                        $status_text = 'قريباً';
                    } elseif ($now > $end_time) {
                        $status_class = 'status-ended';
                        $status_text = 'منتهي';
                    } else {
                        $status_class = 'status-active';
                        $status_text = 'متاح الآن';
                        $can_start = true;
                    }

                    // إذا كان الطالب قد قدم الامتحان بالفعل
                    if ($exam['result_id']) {
                        $can_start = false;
                    }
                ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="exam-card animate__animated animate__fadeIn">
                            <div class="exam-card-header">
                                <span class="exam-status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                                <i class="fas fa-bookmark text-white-50"></i>
                            </div>
                            
                            <?php if ($exam['result_id']): ?>
                                <div class="result-overlay">
                                    <span class="text-muted small fw-bold">الدرجة</span>
                                    <span class="score"><?php echo $exam['total_score']; ?> / <?php echo $exam['max_score']; ?></span>
                                    <div class="progress mt-1" style="height: 4px; width: 60px; margin: 0 auto;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $exam['percentage']; ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="exam-card-body">
                                <span class="exam-subject"><?php echo htmlspecialchars($exam['subject']); ?></span>
                                <h3 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h3>
                                
                                <div class="exam-info-grid">
                                    <div class="info-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span><?php echo htmlspecialchars($exam['teacher_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo $exam['duration']; ?> دقيقة</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-question-circle"></i>
                                        <span><?php echo $exam['questions_count']; ?> سؤال</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?php echo date('m/d', $start_time); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="exam-card-footer">
                                <?php if ($can_start): ?>
                                    <button type="button" class="btn-action btn-start" data-bs-toggle="modal" data-bs-target="#startExam<?php echo $exam['exam_id']; ?>">
                                        <i class="fas fa-play-circle"></i> بدء الامتحان
                                    </button>
                                <?php elseif ($exam['result_id']): ?>
                                    <button type="button" class="btn-action btn-result show-exam-details" data-exam-id="<?php echo $exam['exam_id']; ?>" data-result-id="<?php echo $exam['result_id']; ?>">
                                        <i class="fas fa-poll"></i> التفاصيل
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn-action btn-details" data-bs-toggle="modal" data-bs-target="#examDetails<?php echo $exam['exam_id']; ?>">
                                    <i class="fas fa-info-circle"></i> معلومات
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal بدء الامتحان -->
                    <div class="modal fade" id="startExam<?php echo $exam['exam_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">تأكيد دخول الامتحان</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="start_exam.php" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
                                        <div class="text-center mb-4">
                                            <div class="p-4 bg-light rounded-circle d-inline-block mb-3">
                                                <i class="fas fa-user-shield fa-3x text-primary"></i>
                                            </div>
                                            <h4>هل أنت مستعد؟</h4>
                                            <p class="text-muted">يرجى إدخال كلمة مرور الامتحان المقدمة من المعلم للمتابعة.</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">كلمة المرور</label>
                                            <input type="password" name="password" class="form-control text-center" placeholder="••••••••" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" class="btn btn-dark rounded-pill px-4">ابدأ الآن</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal معلومات الامتحان -->
                    <div class="modal fade" id="examDetails<?php echo $exam['exam_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">معلومات الامتحان</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-light rounded-4">
                                                <h6 class="text-muted mb-3">معلومات عامة</h6>
                                                <p><strong>المادة:</strong> <?php echo htmlspecialchars($exam['subject']); ?></p>
                                                <p><strong>المعلم:</strong> <?php echo htmlspecialchars($exam['teacher_name']); ?></p>
                                                <p><strong>المدة:</strong> <?php echo $exam['duration']; ?> دقيقة</p>
                                                <p><strong>الأسئلة:</strong> <?php echo $exam['questions_count']; ?> سؤال</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-light rounded-4">
                                                <h6 class="text-muted mb-3">المواعيد</h6>
                                                <p><strong>وقت البدء:</strong> <?php echo date('Y-m-d H:i', $start_time); ?></p>
                                                <p><strong>وقت الانتهاء:</strong> <?php echo date('Y-m-d H:i', $end_time); ?></p>
                                                <p><strong>الحالة:</strong> <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></p>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <h6 class="fw-bold mb-2">تعليمات الامتحان:</h6>
                                            <div class="p-3 border rounded-4 bg-white">
                                                <?php echo $exam['description'] ? nl2br(htmlspecialchars($exam['description'])) : 'لا توجد تعليمات خاصة لهذا الامتحان.'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-dark rounded-pill px-4" data-bs-dismiss="modal">إغلاق</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5 bg-white rounded-5 shadow-sm">
                        <i class="fas fa-clipboard-check fa-5x text-light mb-4"></i>
                        <h2 class="fw-bold text-muted">لا توجد امتحانات حالياً</h2>
                        <p class="text-muted">استغل هذا الوقت في مراجعة دروسك وأوراق العمل!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal تفاصيل الإجابات -->
    <div class="modal fade" id="examResultModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">تفاصيل إجابات الامتحان</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="resultDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">جاري تحميل التفاصيل...</p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-dark rounded-pill px-4" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-5 text-center text-muted">
        <p>© 2026 أكاديميتي - جميع الحقوق محفوظة</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader');
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 600);
            }, 800);
        });

        $(document).ready(function() {
            $('.show-exam-details').on('click', function() {
                const examId = $(this).data('exam-id');
                const resultId = $(this).data('result-id');
                
                $('#examResultModal').modal('show');
                $('#resultDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">جاري تحميل التفاصيل...</p></div>');
                
                $.ajax({
                    url: 'get_exam_result_details.php',
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
</body>
</html>
