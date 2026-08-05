<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-container">
    <div class="sidebar-glass animate__animated animate__fadeInRight">
        <div class="sidebar-header">
            <div class="logo-box"><i class="fas fa-chalkboard-teacher"></i></div>
            <h5 class="fw-bold mb-0">لوحة المعلم</h5>
            <p class="small text-muted"><?= htmlspecialchars($_SESSION['user']['name']) ?></p>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link-custom <?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> لوحة التحكم</a>
            <a href="teacher_exams.php" class="nav-link-custom <?= $current_page == 'teacher_exams.php' ? 'active' : '' ?>"><i class="fas fa-edit"></i> الامتحانات</a>
            <a href="teacher_worksheets.php" class="nav-link-custom <?= $current_page == 'teacher_worksheets.php' ? 'active' : '' ?>"><i class="fas fa-file-pdf"></i> أوراق العمل</a>
	            <a href="worksheet_tracking.php" class="nav-link-custom <?= $current_page == 'worksheet_tracking.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> تتبع التحميل</a>
            <a href="teacher_students.php" class="nav-link-custom <?= $current_page == 'teacher_students.php' ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> طلابي</a>
            <a href="teacher_results.php" class="nav-link-custom <?= $current_page == 'teacher_results.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> النتائج</a>
            <a href="teacher_profile.php" class="nav-link-custom <?= $current_page == 'teacher_profile.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> الملف الشخصي</a>
        </div>
        <div class="p-4">
            <a href="../logout.php" class="nav-link-custom text-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </div>
    </div>
</div>
