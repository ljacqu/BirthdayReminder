<?php

require 'Configuration.php';
require './model/EventType.php';
require './class/DatabaseConnector.php';
require './class/AgeCalculator.php';
require './class/Mailer.php';

$tomorrow = new DateTime(null, Configuration::getTimeZone());
$tomorrow->modify('+1 day');

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

$birthdaysByEmail = groupByAccountEmail($upcomingBirthdays);
echo '<br />Grouped entries to ' . count($birthdaysByEmail) . ' accounts.';

$mailer = new Mailer(new AgeCalculator());

echo '<br />Sending emails: ';
foreach ($birthdaysByEmail as $email => $birthdays) {
  $mailer->sendTomorrowReminder($email, $birthdays);
  echo '*';
}

echo ' Done.';

$db->addEvent(EventType::DAILY_MAIL, count($upcomingBirthdays) . ' results; ' . count($birthdaysByEmail) . ' emails');

// Look for weekly birthdays
$endOfWeek = new DateTime(null, Configuration::getTimeZone());
$endOfWeek->modify('+7 day');
$currentWeekDay = date('w');
$weeklyBirthdays = $db->findBirthdaysForWeeklyMail($tomorrow, $endOfWeek, $currentWeekDay);
echo '<br />Found ' . count($weeklyBirthdays) . ' birthdays for next week (' . $tomorrow->format('Y-m-d') . ' to ' . $endOfWeek->format('Y-m-d') . ')';

$birthdaysByEmail = groupByAccountEmail($weeklyBirthdays);
echo '<br />Grouped entries to ' . count($birthdaysByEmail) . ' accounts.';

echo '<br />Sending emails: ';
foreach ($birthdaysByEmail as $email => $birthdays) {
  $mailer->sendNextWeekReminder($email, $birthdays);
  echo '*';
}

echo ' Done.';
$db->addEvent(EventType::WEEKLY_MAIL, count($weeklyBirthdays) . ' results; ' . count($birthdaysByEmail) . ' emails');

function groupByAccountEmail($birthdayEntries) {
  $birthdaysByEmail = [];
  foreach ($birthdayEntries as $entry) {
    $email = $entry['account_email'];
    if (!isset($birthdaysByEmail[$email])) {
      $birthdaysByEmail[$email] = [];
    }
    $birthdaysByEmail[$email][] = $entry;
  }
  return $birthdaysByEmail;
}