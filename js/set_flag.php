<?php
session_start();

if (!isset($_SESSION['account'])) {
  http_response_code(403);
  die('Not logged in');
}

if (!isset($_POST['id'])) {
  http_response_code(400);
  die('No ID sent');
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
$flag = !!filter_input(INPUT_POST, 'enabled', FILTER_VALIDATE_BOOL);

$result = $birthdayId && $db->updateFlag($accountId, $birthdayId, $flag);
echo json_encode(['success' => $result]);