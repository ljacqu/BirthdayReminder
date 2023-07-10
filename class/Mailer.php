<?php

class Mailer {

  private $fromAddr;

  function __construct() {
    $this->fromAddr = Configuration::MAIL_FROM;
  }

  function sendTomorrowReminder($to, $birthdays) {
    $listOfNames = array_reduce($birthdays, function ($carry, $bd) {
      return $carry . "\n- " . $bd['name'];
    }, '');

    $msg = "Hi!\n\nTomorrow, the following people have their birthday!\n" . $listOfNames;

    $title = "Birthdays tomorrow";
    if (strlen($listOfNames) < 40) {
      // Add names in mail subject if short enough
      $title .= ": $listOfNames";
    } else {
      $title .= " (" . count($birthdays) . ")";
    }

    $this->sendMail($to, $title, $msg);
  }

  function sendNextWeekReminder($to, $birthdays) {
    $listOfNames = array_reduce($birthdays, function ($carry, $bd) {
      $date = date('l, d M Y', strtotime($bd['date']));
      return $carry . "\n- {$bd['name']} ({$date})";
    }, '');

    $msg = "Hi!\n\nThese people have their birthday in the following week:\n" . $listOfNames;

    $totalBirthdays = count($birthdays);
    $this->sendMail($to, "Next week's birthdays ($totalBirthdays)", $msg);
  }

  private function sendMail($to, $subject, $message) {
    $actionWasPerformed = false;
    if (strpos(Configuration::MAIL_MODE, 'P') !== false) {
      var_dump('Mail to=' . $to . ', subject=' . $subject . ', message=' . $message);
      $actionWasPerformed = true;
    }
    if (strpos(Configuration::MAIL_MODE, 'S') !== false) {
      $headers = "From: {$this->fromAddr}";
      mail($to, $subject, $message, $headers);
      $actionWasPerformed = true;
    }

    if (!$actionWasPerformed) {
      throw new Error('No mail action is configured');
    }
  }
}