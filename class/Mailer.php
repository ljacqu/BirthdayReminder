<?php

class Mailer {

  private string $fromAddr;
  private bool $sendEmails;
  private bool $outputEmails;
  private bool $logMailFailures;
  private bool $useTomorrowTerm;


  private AgeCalculator $ageCalculator;

  function __construct(AgeCalculator $ageCalculator) {
    $this->fromAddr        = Configuration::MAIL_FROM;
    $this->sendEmails      = Configuration::MAIL_SEND;
    $this->outputEmails    = Configuration::MAIL_PRINT_CONTENTS;
    $this->logMailFailures = Configuration::MAIL_LOG_FAILURES;
    $this->useTomorrowTerm = Configuration::MAIL_FOR_TOMORROW;
    $this->ageCalculator = $ageCalculator;
  }

  function sendTomorrowReminder(string $to, array $birthdays): bool {
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

    return $this->sendMail($to, $title, $msg);
  }

  function sendNextWeekReminder(string $to, array $birthdays, string $dateFormat): bool {
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
    return $this->sendMail($to, "Next week's birthdays ($totalBirthdays)", $msg);
  }

  function sendPasswordResetEmail(string $to, string $token): bool {
    $selfLink = empty(Configuration::MAIL_LINK_TO_RESET_PAGE) 
      ?? ($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['PHP_SELF']);

    $expirationHours = AccountService::RESET_TOKEN_MAX_AGE_HOURS;
    $message = "Hello,

A password reset has been requested for your account. Click here if you want to reset your password: $selfLink?t={$token}. The link will expire in $expirationHours hours.

Please delete this email if you did not request to change your password.

Thanks!";

    return $this->sendMail($to, "Birthday reminder: password reset", $message, true);
  }

  private function sendMail(string $to, string $subject, string $message, bool $noDebugOutput=false): bool {
    if ($this->outputEmails && !$noDebugOutput) {
      echo "\n[********** Email debug **********]";
      echo "\nTo: $to, Subject: $subject";
      echo "\nMessage: $message\n[********** /Email debug **********]\n";
    }

    if ($this->sendEmails) {
      $headers = "From: {$this->fromAddr}";
      $success = @mail($to, $subject, $message, $headers);
      if (!$success && $this->logMailFailures) {
        error_log("Error sending email to $to: " . error_get_last()['message']);
      }
      return $success;
    }
    return true;
  }
}