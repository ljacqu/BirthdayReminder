<?php

final class DateFormat {

  private const FORMATS = [
    'd.m.Y',
    'Y-m-d',
    'M d, Y',
    'm/d/Y'
  ];

  private function __construct() {
  }

  static function getAllFormats() {
    return self::FORMATS;
  }

  static function validateFormat($format) {
    if (!in_array($format, self::FORMATS, true)) {
      throw new ValidationException('Invalid date format');
    }
  }
}