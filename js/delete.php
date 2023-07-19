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
require '../class/SessionService.php';

$db = new DatabaseConnector();
$accountId = $_SESSION['account'];

$accountInfo = $db->getValuesForSession($accountId);
if (!SessionService::isSessionValid($accountInfo['session_secret'])) {
  http_response_code(403);
  die('Not logged in');
}

header('Content-Type: application/json');

$birthdayId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$result = $birthdayId && $db->deleteBirthday($accountId, $birthdayId);
echo json_encode(['success' => $result]);