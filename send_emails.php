<?php

require 'Configuration.php';
require './model/EventType.php';
require './class/DatabaseConnector.php';
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


$upcomingBirthdays = $db->findBirthdays($tomorrow, $tomorrow);
echo '<br />Found ' . count($upcomingBirthdays) . ' upcoming birthdays (date ' . $tomorrow->format('Y-m-d') . ')';

$birthdaysByEmail = [];

foreach ($upcomingBirthdays as $entry) {
  $email = $entry['account_email'];
  if (!isset($birthdaysByEmail[$email])) {
    $birthdaysByEmail[$email] = [];
  }
  $birthdaysByEmail[$email][] = $entry;
}
echo '<br />Grouped entries to ' . count($birthdaysByEmail) . ' accounts.';

$mailer = new Mailer();

echo '<br />Sending emails: ';
foreach ($birthdaysByEmail as $email => $birthdays) {
  $mailer->sendTomorrowReminder($email, $birthdays);
  echo '*';
}

echo ' Done.';

$db->addEvent(EventType::DAILY_MAIL, count($upcomingBirthdays) . ' results; ' . count($birthdaysByEmail) . ' emails');