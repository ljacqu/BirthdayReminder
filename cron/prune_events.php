<?php
session_start();

require '../Configuration.php';
require '../model/EventType.php';
require '../class/DatabaseConnector.php';

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

$eventsMinDate = new DateTime(null, Configuration::getTimeZone());
$eventsMinDate->modify('-' . Configuration::EVENTS_KEEP_DAYS . ' days');

$db = new DatabaseConnector();
$eventsDeleted = $db->removeEventsBefore($eventsMinDate);

if ($eventsDeleted > 0) {
  $db->addEvent(EventType::EVENTS_PRUNE, $eventsDeleted . ' events (cut-off date: ' . $eventsMinDate->format('Y-m-d, H:i') . ')');
}
echo 'Removed ' . $eventsDeleted . ' events';