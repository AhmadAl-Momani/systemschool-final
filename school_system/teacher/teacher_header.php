<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'لوحة المعلم' ?> | نظام المدرسة</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { 
            --glass-bg: rgba(255, 255, 255, 0.85 ); 
            --glass-border: rgba(255, 255, 255, 0.4); 
            --primary-accent: #6366f1; 
            --secondary-accent: #a855f7; 
            --dark-text: #1e293b; 
            --light-text: #64748b; 
            --sidebar-width: 280px; 
        }
        body { 
            background: #f8fafc; 
            font-family: 'Tajawal', sans-serif; 
            min-height: 100vh; 
            margin: 0; 
            overflow-x: hidden; 
        }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar-container { width: var(--sidebar-width); padding: 20px; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar-glass { background: var(--glass-bg); backdrop-filter: blur(15px); border: 1px solid var(--glass-border); height: 100%; border-radius: 24px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .sidebar-header { padding: 30px 20px; text-align: center; }
        .logo-box { background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: white; font-size: 1.5rem; }
        .nav-menu { flex-grow: 1; padding: 10px; }
        .nav-link-custom { display: flex; align-items: center; padding: 14px 20px; color: var(--light-text); text-decoration: none; border-radius: 16px; margin-bottom: 8px; transition: 0.3s; font-weight: 500; }
        .nav-link-custom i { margin-left: 15px; font-size: 1.2rem; }
        .nav-link-custom:hover { background: rgba(99, 102, 241, 0.08); color: var(--primary-accent); }
        .nav-link-custom.active { background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent)); color: white; }
        .main-content { flex-grow: 1; margin-right: var(--sidebar-width); padding: 30px; width: calc(100% - var(--sidebar-width)); }
        .content-card { background: white; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .stat-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .table-modern thead th { background: #f8fafc; border: none; padding: 15px; color: var(--light-text); font-weight: 700; font-size: 0.85rem; }
        .table-modern tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .badge-soft-primary { background: rgba(99, 102, 241, 0.1); color: var(--primary-accent); }
        .student-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .no-photo-circle { width: 45px; height: 45px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include 'teacher_sidebar.php'; ?>
    <div class="main-content">
