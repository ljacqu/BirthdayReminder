<?php

class Mailer {

  private string $fromAddr;
  private bool $sendEmails;
  private bool $outputEmails; // for debug
  private bool $useTomorrowTerm;

  private AgeCalculator $ageCalculator;

  function __construct(AgeCalculator $ageCalculator) {
    $this->fromAddr = Configuration::MAIL_FROM;
    $this->sendEmails   = strpos(Configuration::MAIL_MODE, 'S') !== false;
    $this->outputEmails = strpos(Configuration::MAIL_MODE, 'P') !== false;
    $this->useTomorrowTerm = Configuration::MAIL_FOR_TOMORROW;
    $this->ageCalculator = $ageCalculator;
  }

  function sendTomorrowReminder($to, $birthdays) {
    $now = new DateTime();
    $listOfBirthdays = array_reduce($birthdays, function ($carry, $bd) use ($now) {
      $age = $this->ageCalculator->calculateFutureAge($now, $bd['date']);
      $year = date('Y', strtotime($bd['date']));
      return $carry . "\n- " . $bd['name'] . " ($year) turns $age";
    }, '');

    $shortNameList = '';
    $first = true;
    foreach ($birthdays as $bd) {
      $sep = $first ? '' : ', ';
      $shortNameList .= $sep . $bd['name'];
      $first = false;
    }

    $tomorrowOrToday = $this->useTomorrowTerm ? 'Tomorrow' : 'Today';
    $msg = "Hi!\n\n{$tomorrowOrToday}, the following people have their birthday!\n" . $listOfBirthdays;

    $title = "Birthdays " . strtolower($tomorrowOrToday);
    if (strlen($shortNameList) < 40) {
      // Add names in mail subject if short enough
      $title .= ": $shortNameList";
    } else {
      $title .= " (" . count($birthdays) . ")";
    }

    $this->sendMail($to, $title, $msg);
  }

  function sendNextWeekReminder($to, $birthdays, $dateFormat) {
    $now = new DateTime();

    $birthdaysByDay = [];
    foreach ($birthdays as $bd) {
      $birthday = strtotime($bd['date']);
      $year = date('Y', $birthday);
      $age = $this->ageCalculator->calculateFutureAge($now, $bd['date']);
      $text = "\n- {$bd['name']} ($year) turns $age";

      $dayMonth = date('m-d', $birthday);
      if (!isset($birthdaysByDay[$dayMonth])) {
        $birthdaysByDay[$dayMonth] = '';
      }
      $birthdaysByDay[$dayMonth] .= $text;
    }

    $output = '';
    $thisYearPrefix = $now->format('Y-');
    foreach ($birthdaysByDay as $day => $text) {
      $date = $this->ageCalculator->toUpcomingBirthdayYear($now, $thisYearPrefix . $day);
      $weekday = $date->format('l');
      $output .= "\n\n$weekday, " . $date->format($dateFormat) . $text;
    }

    $msg = "Hi! These people have their birthday this coming week:\n\n" . trim($output);

    $totalBirthdays = count($birthdays);
    $this->sendMail($to, "Next week's birthdays ($totalBirthdays)", $msg);
  }

  private function sendMail($to, $subject, $message) {
    $actionWasPerformed = false;
    if ($this->outputEmails) {
      var_dump('Mail to=' . $to . ', subject=' . $subject . ', message=' . $message);
      $actionWasPerformed = true;
    }
    if ($this->sendEmails) {
      $headers = "From: {$this->fromAddr}";
      mail($to, $subject, $message, $headers);
      $actionWasPerformed = true;
    }

    if (!$actionWasPerformed) {
      throw new Error('No mail action is configured');
    }
  }
}