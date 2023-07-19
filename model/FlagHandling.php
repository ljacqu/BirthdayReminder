<?php

final class FlagHandling {

  private const OPTIONS = [
    'none',
    'ignore',
    'filter'
  ];

  private function __construct() {
  }

  static function validate($handling) {
    if (!in_array($handling, self::OPTIONS, true)) {
      throw new ValidationException('Invalid flag handling value');
    }
  }

  static function getFlagText(UserPreference $pref) {
    $ignoreCount = 0;
    $filterCount = 0;
    $dailyFlagUsed = false;
    $weeklyFlagUsed = false;

    if ($pref->getDailyMail()) {
      if ($pref->getDailyFlag() === 'ignore') {
        ++$ignoreCount;
        $dailyFlagUsed = true;
      } else if ($pref->getDailyFlag() === 'filter') {
        ++$filterCount;
        $dailyFlagUsed = true;
      }
    }

    if ($pref->getWeeklyMail() != -1) {
      if ($pref->getWeeklyFlag() === 'ignore') {
        ++$ignoreCount;
        $weeklyFlagUsed = true;
      } else if ($pref->getWeeklyFlag() === 'filter') {
        ++$filterCount;
        $weeklyFlagUsed = true;
      }
    }

    if ($ignoreCount + $filterCount === 1) {
      $verb = ($ignoreCount > 0) ? 'Ignore' : 'Include';
      $mailType = $dailyFlagUsed ? 'daily' : 'weekly';
      return [
        'text' => "$verb in $mailType",
        'help' => 'Defines if the birthday should be included in your ' . $mailType . ' mail (flag behavior can be changed in settings)'
      ];
    } else if ($ignoreCount === 2 || $filterCount === 2) {
      $verb = $ignoreCount > 0 ? 'Ignore' : 'Include';
      return [
        'text' => "$verb in mails",
        'help' => 'Defines if the birthday should be included in your mails (flag behavior can be changed in settings)'
      ];
    } else {
      $help = ($ignoreCount + $filterCount) > 0
        ? 'Ignored/included in mails as per your settings.'
        : 'You can use this as whitelist or blacklist for emails. Configurable in the settings.';
      return [
        'text' => 'Flag',
        'help' => $help
      ];
    }
  }
}