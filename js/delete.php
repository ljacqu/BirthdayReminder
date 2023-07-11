<?php
session_start();

if (true  ||!isset($_SESSION['account'])) {
  http_response_code(403);
  die('Not logged in');
}

if (!isset($_POST['id'])) {
  http_response_code(400);
  die('Missing ID');
}

require '../Configuration.php';
require '../class/DatabaseConnector.php';
header('Content-Type: application/json');

$birthdayId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$db = new DatabaseConnector();
$result = $birthdayId && $db->deleteBirthday($_SESSION['account'], $birthdayId);
echo json_encode(['success' => $result]);