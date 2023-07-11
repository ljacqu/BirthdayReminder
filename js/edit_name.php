<?php
session_start();

if (!isset($_SESSION['account'])) {
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

$accountId = $_SESSION['account'];
$birthdayId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

$db = new DatabaseConnector();
$result = $name && $db->updateBirthdayName($accountId, $birthdayId, $name);
echo json_encode(['success' => $result]);