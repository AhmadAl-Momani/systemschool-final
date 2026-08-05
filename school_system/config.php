<?php
// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
  session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
  ]);
}

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_system');
define('BASE_URL', 'http://localhost/school_system/');

// إنشاء اتصال قاعدة البيانات
try {
  $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  $conn->set_charset("utf8mb4");
} catch (Exception $e) {
  die("Connection failed: " . $e->getMessage());
}

// إعداد اللغة والتاريخ
setlocale(LC_ALL, 'ar_AE.utf8');
date_default_timezone_set('Asia/Damascus');

// الدوال المساعدة
function sanitize($data)
{
  global $conn;
  if (is_array($data)) {
    return array_map('sanitize', $data);
  }
  return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8'));
}

function redirect($url)
{
  header("Location: " . BASE_URL . $url);
  exit();
}

function loginUser($user_data, $user_type)
{
  $_SESSION['user'] = [
    'id' => $user_data['id'],
    'type' => $user_type,
    'name' => $user_data['name'] ?? $user_data['username'] ?? '',
    'email' => $user_data['email'] ?? '',
    'gender' => $user_data['gender'] ?? null
  ];
}

function checkAuth($allowed_types = [])
{
  if (!isset($_SESSION['user'])) {
    redirect(BASE_URL . 'login.php');

  }

  if (!empty($allowed_types) && !in_array($_SESSION['user']['type'], $allowed_types)) {
    redirect('login.php?error=unauthorized');
  }

  return $_SESSION['user'];
}