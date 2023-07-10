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
}