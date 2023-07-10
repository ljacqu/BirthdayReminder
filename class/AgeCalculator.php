<?php

class AgeCalculator {

  function calculateFutureAge(DateTime $now, string $futureBirthday) {
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

  function toUpcomingBirthdayYear(DateTime $now, string $futureBirthday) {
    $nowYear   = $now->format('Y');
    $nowMonth  = $now->format('m');
    $upcomingBirthday = new DateTime($futureBirthday);
    $upcomingMonth = $upcomingBirthday->format('m');

    $upcomingYear = $nowYear;
    if ($nowMonth === '12' && $upcomingMonth === '01') {
      ++$upcomingYear;
    }
    
    // This seems to also work with leap days
    return new DateTime($upcomingYear . $upcomingBirthday->format('-m-d'));
  }
}