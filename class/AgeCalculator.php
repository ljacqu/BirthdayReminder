<?php

class AgeCalculator {

  function calculateFutureAge(DateTime $now, string $futureBirthday) {
    $nowYear   = $now->format('Y');
    $nowMonth  = $now->format('m');
    $then = strtotime($futureBirthday);
    $thenYear  = date('Y', $then);
    $thenMonth = date('m', $then);

    $age = $nowYear - $thenYear;
    if ($nowMonth === 12 && $thenMonth === 1) {
      return $age + 1;
    }
    return $age;
  }
}