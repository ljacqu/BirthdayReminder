<?php
session_start();

require '../Configuration.php';
require '../model/EventType.php';
require '../class/DatabaseConnector.php';

$cronSecret = filter_input(INPUT_GET, 'key', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($cronSecret !== Configuration::CRON_SECRET && !empty(Configuration::CRON_SECRET)) {
  die('Invalid or missing key');
}

$eventsMinDate = new DateTime(null, Configuration::getTimeZone());
$eventsMinDate->modify('-' . Configuration::EVENTS_KEEP_DAYS . ' days');

$db = new DatabaseConnector();
$eventsDeleted = $db->removeEventsBefore($eventsMinDate);

if ($eventsDeleted > 0) {
  $db->addEvent(EventType::EVENTS_PRUNE, $eventsDeleted . ' events (cut-off date: ' . $eventsMinDate->format('Y-m-d, H:i') . ')');
}
echo 'Removed ' . $eventsDeleted . ' events';