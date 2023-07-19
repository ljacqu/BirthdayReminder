<?php
session_start();

require '../Configuration.php';
require '../model/EventType.php';
require '../class/DatabaseConnector.php';
require '../class/AgeCalculator.php';
require '../class/Mailer.php';

header('Content-Type: text/plain');

if (!empty(Configuration::CRON_SECRET)) {
  if (isset($_SERVER['argv']) && isset($_SERVER['argv'][1]) && is_scalar($_SERVER['argv'][1])) {
    $secret = $_SERVER['argv'][1];
  } else {
    $secret = filter_input(INPUT_GET, 'key', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  }

  if ($secret !== Configuration::CRON_SECRET) {
    http_response_code(403);
    die('Error: Invalid or missing key (got: ' . print_r($secret, true) . ')');
  }
}

$tomorrow = new DateTime(null, Configuration::getTimeZone());
if (Configuration::MAIL_FOR_TOMORROW) {
  $tomorrow->modify('+1 day');
}

$db = new DatabaseConnector();

$lastDailyEvent = $db->getLatestEvent(EventType::DAILY_MAIL);
if ($lastDailyEvent) {
  $date = $lastDailyEvent['date'];
  if (date('Y-M-d') === date('Y-M-d', strtotime($date))) {
    echo 'Mails have already been generated for today (at ' . $date . ')';
    if (!isset($_GET['force'])) {
      http_response_code(400);
      exit;
    }
  }
}

$mailer = new Mailer(new AgeCalculator());

// Look for tomorrow's birthdays
$upcomingBirthdays = $db->findBirthdaysForDailyMail($tomorrow);
outputIfEnabled(0x001, "Found " . count($upcomingBirthdays) . ' birthdays for tomorrow (date ' . $tomorrow->format('Y-m-d') . ')');

$birthdaysByAccountId = groupByAccountId($upcomingBirthdays);
$emailSettingsByAccountId = $db->getValuesForEmail(array_keys($birthdaysByAccountId));

$allMailSuccess = true;
foreach ($birthdaysByAccountId as $accountId => $birthdays) {
  $emailData = $emailSettingsByAccountId[$accountId];
  $allMailSuccess &= $mailer->sendTomorrowReminder($emailData['email'], $birthdays);
}

outputIfEnabled(0x002, "Sent " . count($birthdaysByAccountId) . ' daily emails.');
if (!$allMailSuccess) {
  outputIfEnabled(0x004, "! Errors occurred while sending emails.");
}

$db->addEvent(EventType::DAILY_MAIL, count($upcomingBirthdays) . ' results; ' . count($birthdaysByAccountId) . ' emails');

// Look for weekly birthdays
$endOfWeek = new DateTime(null, Configuration::getTimeZone());
$endOfWeek->modify('+7 day');
$currentWeekDay = date('w');
$weeklyBirthdays = $db->findBirthdaysForWeeklyMail($tomorrow, $endOfWeek, $currentWeekDay);
outputIfEnabled(0x010, "Found " . count($weeklyBirthdays) . ' birthdays for next week (' . $tomorrow->format('Y-m-d') . ' to ' . $endOfWeek->format('Y-m-d') . ')');

$birthdaysByAccountId = groupByAccountId($weeklyBirthdays);

$missingAccountIds = array_diff(array_keys($birthdaysByAccountId), array_keys($emailSettingsByAccountId));
if (!empty($missingAccountIds)) {
  $additionalEmailValues = $db->getValuesForEmail($missingAccountIds);
  foreach ($additionalEmailValues as $accountId => $emailInfo) {
    $emailSettingsByAccountId[$accountId] = $emailInfo;
  }
}

$allMailSuccess = true;
foreach ($birthdaysByAccountId as $accountId => $birthdays) {
  $emailData = $emailSettingsByAccountId[$accountId];
  $allMailSuccess &= $mailer->sendNextWeekReminder($emailData['email'], $birthdays, $emailData['date_format']);
}

outputIfEnabled(0x020, "Sent " . count($birthdaysByAccountId) . ' weekly emails.');
if (!$allMailSuccess) {
  outputIfEnabled(0x040, "! Errors occurred while sending emails.");
}
$db->addEvent(EventType::WEEKLY_MAIL, count($weeklyBirthdays) . ' results; ' . count($birthdaysByAccountId) . ' emails');

function groupByAccountId($birthdayEntries) {
  $birthdaysByAccount = [];
  foreach ($birthdayEntries as $entry) {
    $accountId = $entry['account_id'];
    if (!isset($birthdaysByAccount[$accountId])) {
      $birthdaysByAccount[$accountId] = [];
    }
    $birthdaysByAccount[$accountId][] = $entry;
  }
  return $birthdaysByAccount;
}

function outputIfEnabled($flag, $message) {
  if (($flag & Configuration::MAIL_SCRIPT_OUTPUTS) != 0) {
    echo "\n[" . date('H:i:s') . "] $message";
  }
}