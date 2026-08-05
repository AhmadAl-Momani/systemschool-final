<?php
require_once '../config.php';
header('Content-Type: application/json');

if (isset($_GET['number'])) {
  $number = (int) $_GET['number'];

  $stmt = $conn->prepare("SELECT number_id FROM numbers WHERE number_value = ?");
  $stmt->bind_param("i", $number);
  $stmt->execute();
  $result = $stmt->get_result();

  echo json_encode(['exists' => $result->num_rows > 0]);
  exit();
}

echo json_encode(['exists' => false]);