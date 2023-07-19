<?php
session_start();

if (!isset($_SESSION['account'])) {
  http_response_code(403);
  die('Not logged in');
}

require '../Configuration.php';
require '../class/DatabaseConnector.php';
require '../class/SessionService.php';

$accountId = $_SESSION['account'];
$db = new DatabaseConnector();
$accountInfo = $db->getValuesForSession($accountId);
if (!SessionService::isSessionValid($accountInfo['session_secret'])) {
  http_response_code(403);
  die('Not logged in');
}

header('Content-Type: application/json');

$birthdayId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if (!$birthdayId || !$name) {
  http_response_code(400);
  die('Missing ID or name');
}

$result = $name && $db->updateBirthdayName($accountId, $birthdayId, $name);
echo json_encode(['success' => $result]);