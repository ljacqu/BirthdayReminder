<?php
session_start();

if (!isset($_SESSION['account'])) {
  http_response_code(403);
  die('Not logged in');
}

require '../Configuration.php';
require '../model/UserPreference.php';
require '../class/DatabaseConnector.php';
require '../class/SessionService.php';

$accountId = $_SESSION['account'];

$db = new DatabaseConnector();
$accountInfo = $db->getValuesForSession($accountId);
if (!SessionService::isSessionValid($accountInfo['session_secret'])) {
  http_response_code(403);
  die('Not logged in');
}

$birthdayId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$date = filter_input(INPUT_POST, 'date', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if (!$birthdayId || !$date) {
  http_response_code(400);
  die('Missing ID or date');
}

header('Content-Type: application/json');

$dateTime = new DateTime($date);
$result = $db->updateBirthdayDate($accountId, $birthdayId, $dateTime);
$dateFormat = $db->getPreferences($accountId)->getDateFormat();
$newText = $dateTime->format($dateFormat);

echo json_encode(['success' => $result, 'newText' => $newText]);