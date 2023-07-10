<?php

require 'Configuration.php';
require './class/DatabaseConnector.php';
require './class/Mailer.php';

$tomorrow = new DateTime(null, Configuration::getTimeZone());
$tomorrow->modify('+1 day');

$db = new DatabaseConnector();

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
  echo '.';
}

echo ' Done.';
