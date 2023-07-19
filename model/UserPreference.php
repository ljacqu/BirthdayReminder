<?php

class UserPreference {

  private bool $dailyMail;
  private string $dailyFlag;
  private int $weeklyMail;
  private string $weeklyFlag;
  private string $dateFormat;

  function getDailyMail(): bool {
    return $this->dailyMail;
  }

  function setDailyMail(bool $dailyMail) {
    $this->dailyMail = $dailyMail;
  }

  function getDailyFlag(): string {
    return $this->dailyFlag;
  }

  function setDailyFlag(string $value) {
    $this->dailyFlag = $value;
  }

  function getWeeklyMail(): int {
    return $this->weeklyMail;
  }

  function setWeeklyMail(int $value) {
    $this->weeklyMail = $value;
  }

  function getWeeklyFlag(): string {
    return $this->weeklyFlag;
  }

  function setWeeklyFlag(string $value) { 
    $this->weeklyFlag = $value;
  }


  function getDateFormat(): string {
    return $this->dateFormat;
  }

  function setDateFormat(string $value) {
    $this->dateFormat = $value;
  }
}