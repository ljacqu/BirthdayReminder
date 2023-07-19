<?php
session_start();

require '../Configuration.php';
require '../model/EventType.php';
require '../class/DatabaseConnector.php';
require '../class/AgeCalculator.php';
require '../class/Mailer.php';

if (!empty(Configuration::CRON_SECRET)) {
  if (isset($_SERVER['argv']) && isset($_SERVER['argv'][1]) && is_scalar($_SERVER['argv'][1])) {
    $secret = $_SERVER['argv'][1];
  } else {
    $secret = filter_input(INPUT_GET, 'key', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  }

  if ($secret !== Configuration::CRON_SECRET) {
    die('Error: Invalid or missing key (' . print_r($secret, true) . ')');
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
    if (!isset($_GET['force']) || !Configuration::LOCAL_DEV) {
      exit;
    }
  }
}

// Look for tomorrow's birthdays
$upcomingBirthdays = $db->findBirthdaysForDailyMail($tomorrow);
echo '<br />Found ' . count($upcomingBirthdays) . ' birthdays for tomorrow (date ' . $tomorrow->format('Y-m-d') . ')';

$birthdaysByAccountId = groupByAccountId($upcomingBirthdays);
echo '<br />Grouped entries to ' . count($birthdaysByAccountId) . ' accounts.';
$emailSettingsByAccountId = $db->getValuesForEmail(array_keys($birthdaysByAccountId));

$mailer = new Mailer(new AgeCalculator());

echo '<br />Sending emails: ';
foreach ($birthdaysByAccountId as $accountId => $birthdays) {
  $emailData = $emailSettingsByAccountId[$accountId];
  $mailer->sendTomorrowReminder($emailData['email'], $birthdays);
  echo '*';
}

echo ' Done.';

$db->addEvent(EventType::DAILY_MAIL, count($upcomingBirthdays) . ' results; ' . count($birthdaysByAccountId) . ' emails');

// Look for weekly birthdays
$endOfWeek = new DateTime(null, Configuration::getTimeZone());
$endOfWeek->modify('+7 day');
$currentWeekDay = date('w');
$weeklyBirthdays = $db->findBirthdaysForWeeklyMail($tomorrow, $endOfWeek, $currentWeekDay);
echo '<br />Found ' . count($weeklyBirthdays) . ' birthdays for next week (' . $tomorrow->format('Y-m-d') . ' to ' . $endOfWeek->format('Y-m-d') . ')';

$birthdaysByAccountId = groupByAccountId($weeklyBirthdays);
echo '<br />Grouped entries to ' . count($birthdaysByAccountId) . ' accounts.';

$missingAccountIds = array_diff(array_keys($emailSettingsByAccountId), array_keys($birthdaysByAccountId));
if (!empty($missingAccountIds)) {
  $additionalEmailValues = $db->getValuesForEmail($missingAccountIds);
  foreach ($additionalEmailValues as $accountId => $emailInfo) {
    $emailSettingsByAccountId[$accountId] = $emailInfo;
  }
}

echo '<br />Sending emails: ';

foreach ($birthdaysByAccountId as $accountId => $birthdays) {
  $emailData = $emailSettingsByAccountId[$accountId];
  $mailer->sendNextWeekReminder($emailData['email'], $birthdays, $emailData['date_format']);
  echo '*';
}

echo ' Done.';
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

