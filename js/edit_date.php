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
$date = filter_input(INPUT_POST, 'date', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

$db = new DatabaseConnector();

$newText = '';
$result = false;
if ($date) {
  $dateTime = new DateTime($date);
  $result = $db->updateBirthdayDate($accountId, $birthdayId, $dateTime);
  $dateFormat = $db->getPreferences($accountId)['date_format'];
  $newText = $dateTime->format($dateFormat);
}

echo json_encode(['success' => $result, 'newText' => $newText]);