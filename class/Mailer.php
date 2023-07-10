<?php

class Mailer {

  private string $fromAddr;
  private AgeCalculator $ageCalculator;

  function __construct(AgeCalculator $ageCalculator) {
    $this->fromAddr = Configuration::MAIL_FROM;
    $this->ageCalculator = $ageCalculator;
  }

  function sendTomorrowReminder($to, $birthdays) {
    $now = new DateTime();
    $listOfNames = array_reduce($birthdays, function ($carry, $bd) use ($now) {
      $age = $this->ageCalculator->calculateFutureAge($now, $bd['date']);
      return $carry . "\n- " . $bd['name'] . " (turns $age)";
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
    $now = new DateTime();
    $listOfNames = array_reduce($birthdays, function ($carry, $bd) use ($now) {
      $date = date('l, d M Y', strtotime($bd['date']));
      $age = $this->ageCalculator->calculateFutureAge($now, $bd['date']);
      return $carry . "\n- {$bd['name']} ($date, turns $age)";
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