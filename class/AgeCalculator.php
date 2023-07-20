<?php

class AgeCalculator {

  function calculateFutureAge(DateTime $now, string $futureBirthday): int {
    $nowYear   = $now->format('Y');
    $nowMonth  = $now->format('m');
    $then = strtotime($futureBirthday);
    $thenYear  = date('Y', $then);
    $thenMonth = date('m', $then);

    $age = $nowYear - $thenYear;
    if ($nowMonth === '12' && $thenMonth === '01') {
      return $age + 1;
    }
    return $age;
  }

  function toUpcomingBirthdayYear(DateTime $now, string $futureBirthday): DateTime {
    $nowMonth = $now->format('m');
    $upcomingBirthday = new DateTime($futureBirthday);
    $upcomingMonth = $upcomingBirthday->format('m');

    if ($nowMonth === '12' && $upcomingMonth === '01') {
      $upcomingBirthday->modify('+1 year');
    }
    return $upcomingBirthday;
  }
}