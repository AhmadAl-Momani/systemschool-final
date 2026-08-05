<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>نظام المدرسة الالكترونية</title>
  <!-- Bootstrap RTL -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    :root {
      <?php
      // نستخدم لون مختلف للبنات
      if (!empty($_SESSION['gender']) && $_SESSION['gender'] === 'female') {
        echo '--main-color: #ff66b2; --secondary-color: #ffccdd;';
      } else {
        echo '--main-color: #4285f4; --secondary-color: #e3f2fd;';
      }
      ?>
    }

    body {
      background-color: #f8f9fa;
      font-family: 'Tajawal', sans-serif;
    }

    .navbar {
      background-color: var(--main-color);
    }

    .sidebar {
      background-color: var(--secondary-color);
    }

    .btn-primary {
      background-color: var(--main-color);
      border-color: var(--main-color);
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php">
        <i class="fas fa-school me-2"></i>QuizzyMind
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <!-- الروابط الرئيسية -->
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="fas fa-home"></i> الرئيسية
            </a>
          </li>
        </ul>

        <!-- قائمة المستخدم -->
        <ul class="navbar-nav">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown"
              aria-expanded="false">
              <i class="fas fa-user-circle"></i>
              <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <!--
              <li>
                <a class="dropdown-item" href="profile.php">
                  <i class="fas fa-user me-2"></i> الملف الشخصي
                </a>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li>  -->
              <li>
                <a class="dropdown-item" href="../logout.php">
                  <i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row">

      <!-- تضمين مكتبات jQuery وBootstrap JS -->
      <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJ+YRFqImX+6X/4P5yp5b1/8G7Rh0pMxLg5Ps=" crossorigin="anonymous"></script>
   -->


      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-QF64Nf5c4aQ13yKrrqYlw5UnYxH6VDG9Ee6UL6DfJvE+0h97vcoI2Gwa0bjHj4/F"
        crossorigin="anonymous"></script>
</body>

</html>