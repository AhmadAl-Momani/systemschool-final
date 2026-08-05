<?php
// التحقق من جنس الطالب
$gender = $_SESSION['gender'] ?? 'male'; // افتراضي ذكر إذا لم يكن محدداً

// ألوان حسب الجنس
if ($gender === 'female') {
  $primary_color = '#ff66b2'; // وردي
  $secondary_color = '#ff99cc';
  $bg_color = '#fff0f5';
} else {
  $primary_color = '#4285f4'; // أزرق
  $secondary_color = '#8ab4f8';
  $bg_color = '#f0f7ff';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>نظام إدارة الطلاب</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-color:
        <?php echo $primary_color; ?>
      ;
      --secondary-color:
        <?php echo $secondary_color; ?>
      ;
      --bg-color:
        <?php echo $bg_color; ?>
      ;
    }

    body {
      background-color: var(--bg-color);
      font-family: 'Tajawal', sans-serif;
    }

    .sidebar {
      background-color: var(--primary-color);
      color: white;
    }

    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-outline-primary {
      color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-outline-primary:hover {
      background-color: var(--primary-color);
      color: white;
    }

    .card-header {
      background-color: var(--primary-color);
      color: white;
    }

    .nav-pills .nav-link.active {
      background-color: var(--primary-color);
    }

    .badge-primary {
      background-color: var(--primary-color);
    }
  </style>
</head>

<body>