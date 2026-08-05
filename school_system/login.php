<?php
require_once 'config.php';

if (isset($_SESSION['user'])) {
  redirect($_SESSION['user']['type'] . '/dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  $user = null;
  $user_type = '';

  $sql = "SELECT admin_id as id, username, password, username as name FROM admin WHERE username = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $user_type = 'admin';
  } else {
    $sql = "SELECT teacher_id as id, tname as name, email, password FROM teacher WHERE email = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      $user_type = 'teacher';
    } else {
      $sql = "SELECT student_id as id, stuname as name, natnum, password, gender FROM student WHERE natnum = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_type = 'student';
      } else {
        $sql = "SELECT parent_id as id, pname as name, email, password FROM parent WHERE email = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
          $user = $result->fetch_assoc();
          $user_type = 'parent';
        }
      }
    }
  }

  if ($user && $user_type) {
    if ($user_type == 'student' && empty($user['password'])) {
      if ($password === $username) {
        loginUser($user, $user_type);
        header("Location: " . BASE_URL . $user_type . '/dashboard.php');
        exit();
      }
    }
    elseif (password_verify($password, $user['password'])) {
      loginUser($user, $user_type);
      header("Location: " . BASE_URL . $user_type . '/dashboard.php');
      exit();
    }
  }

  $error = "بيانات الدخول غير صحيحة";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول | نظام المدرسة الذكي</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <style>
    :root {
      --primary-color: #4e73df;
      --secondary-color: #224abe;
      --accent-color: #1cc88a;
      --bg-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
      --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    body {
      background: #f0f2f5;
      font-family: 'Tajawal', sans-serif;
      height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }

    .bg-shape {
      position: absolute;
      z-index: -1;
      filter: blur(80px);
      opacity: 0.4;
      border-radius: 50%;
    }
    .shape-1 { width: 400px; height: 400px; background: var(--primary-color); top: -100px; right: -100px; }
    .shape-2 { width: 300px; height: 300px; background: var(--accent-color); bottom: -50px; left: -50px; }

    .login-container {
      width: 100%;
      max-width: 1000px;
      padding: 20px;
      z-index: 1;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      border: none;
      display: flex;
      flex-direction: row;
      min-height: 600px;
    }

    .login-visual {
      background: var(--bg-gradient);
      width: 45%;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
      text-align: center;
      position: relative;
    }

    .login-visual i {
      font-size: 80px;
      margin-bottom: 20px;
      filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));
    }

    .login-visual h1 {
      font-weight: 800;
      font-size: 2.2rem;
      margin-bottom: 15px;
    }

    .login-visual p {
      font-weight: 300;
      opacity: 0.9;
      font-size: 1.1rem;
    }

    .login-form-side {
      width: 55%;
      padding: 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-header {
      margin-bottom: 35px;
    }

    .form-header h2 {
      font-weight: 700;
      color: #333;
      margin-bottom: 10px;
    }

    .form-header p {
      color: #777;
    }

    .input-group {
      margin-bottom: 20px;
      position: relative;
    }

    .input-group-text {
      background: transparent;
      border: 2px solid #eee;
      border-left: none;
      color: var(--primary-color);
      border-radius: 0 12px 12px 0;
    }

    .form-control {
      border: 2px solid #eee;
      border-right: none;
      padding: 12px 15px;
      border-radius: 12px 0 0 12px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: none;
    }

    .form-control:focus + .input-group-text {
      border-color: var(--primary-color);
    }

    .form-label {
      font-weight: 600;
      color: #555;
      margin-bottom: 8px;
    }

    .btn-login {
      background: var(--bg-gradient);
      border: none;
      padding: 14px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 1.1rem;
      color: white;
      margin-top: 10px;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(78, 115, 223, 0.4);
      color: white;
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .forgot-link {
      text-align: center;
      margin-top: 20px;
    }

    .forgot-link a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .forgot-link a:hover {
      color: var(--secondary-color);
    }

    .info-box {
      background: #f8f9fc;
      border-right: 4px solid var(--primary-color);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.85rem;
    }

    .info-box i {
      color: var(--primary-color);
      margin-left: 8px;
    }

    @media (max-width: 768px) {
      .login-card {
        flex-direction: column;
        min-height: auto;
      }
      .login-visual {
        width: 100%;
        padding: 30px;
      }
      .login-form-side {
        width: 100%;
        padding: 30px;
      }
      .login-visual i { font-size: 50px; }
      .login-visual h1 { font-size: 1.5rem; }
    }

    .fade-in-up {
      animation: fadeInUp 0.8s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body>
  <div class="bg-shape shape-1"></div>
  <div class="bg-shape shape-2"></div>

  <div class="container login-container">
    <div class="login-card animate__animated animate__fadeIn">
      <div class="login-visual">
        <div class="animate__animated animate__zoomIn">
          <i class="fas fa-graduation-cap"></i>
          <h1>مرحباً بك</h1>
          <p>في منصة مدرستنا المتكاملة<br>بوابتك نحو مستقبل تعليمي أفضل</p>
        </div>
        <div style="position: absolute; bottom: 20px; font-size: 0.8rem; opacity: 0.7;">
          جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?>
        </div>
      </div>

      <div class="login-form-side">
        <div class="form-header">
          <h2>تسجيل الدخول</h2>
          <p>يرجى إدخال بياناتك للوصول إلى حسابك</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger animate__animated animate__shakeX">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="fade-in-up">
          <div class="mb-4">
            <label for="username" class="form-label">اسم المستخدم / البريد الإلكتروني</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
              <input type="text" class="form-control" id="username" name="username"
                placeholder="أدخل اسم المستخدم أو البريد"
                value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="info-box">
              <div><i class="fas fa-info-circle"></i> <strong>للمعلمين وأولياء الأمور:</strong> استخدم البريد الإلكتروني</div>
              <div class="mt-1"><i class="fas fa-info-circle"></i> <strong>للطلاب:</strong> استخدم الرقم الوطني الخاص بك</div>
            </div>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">كلمة المرور</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-key"></i></span>
              <input type="password" class="form-control" id="password" name="password" 
                placeholder="أدخل كلمة المرور الخاصة بك" required>
              <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border: 2px solid #eee; border-right: none; border-radius: 12px 0 0 12px;">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <small class="text-muted"><i class="fas fa-lightbulb me-1"></i> للطلاب الجدد: كلمة المرور الافتراضية هي رقمك الوطني</small>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-login">
              <i class="fas fa-sign-in-alt me-2"></i> دخول إلى النظام
            </button>
          </div>
        </form>

 
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      this.querySelector('i').classList.toggle('fa-eye');
      this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
      input.addEventListener('focus', () => {
        input.parentElement.classList.add('animate__animated', 'animate__pulse');
      });
      input.addEventListener('blur', () => {
        input.parentElement.classList.remove('animate__animated', 'animate__pulse');
      });
    });
  </script>
</body>

</html>
