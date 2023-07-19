<?php

class Configuration {

  // ------
  // Database connection details
  // ------

  /** Database host. */
  const DB_HOST = 'localhost';
  /** Database name. */
  const DB_NAME = 'birthday_reminder';
  /** Database user. */
  const DB_USER = 'root';
  /** Database password. */
  const DB_PASS = '';


  // ------
  // Email details
  // ------

  /**
   * When emails are sent to users, from which email should it be sent from?
   */
  const MAIL_FROM = 'birthdays@example.org';
  /** If false, no emails are ever actually sent. Useful for local development. */
  const MAIL_SEND = false;
  /** If true, the contents of all emails are also output on the page sending them. Useful for local development. */
  const MAIL_PRINT_CONTENTS = false;

  /** Write mail errors to the PHP error log? If false, mail errors are not logged anywhere. */
  const MAIL_LOG_FAILURES = true;

  /**
   * If set to true, when sending out the daily mail, the date that will be used is the next day and
   * the email will refer to the date as "tomorrow". This makes sense to set to true if you run
   * send_emails.php daily at 11 PM or so. If you run the job at 3 AM, this should be false to get
   * the same day's birthdays per mail.
   */
  const MAIL_FOR_TOMORROW = true;


  // ------
  // System details
  // ------

  /**
   * Specifies the timezone that should be used; empty for server default.
   * You can see the current time as configured in the 'System' area.
   * Possible values: https://www.php.net/manual/en/timezones.php
   */
  const TIME_ZONE = '';

  /**
   * Secret that must be provided to pages in the cron/ folder so that someone cannot manually send out emails.
   * In the system area, you will see a preview of the CRON commands you can define.
   */
  const CRON_SECRET = 'oKErMTsh2';

  /** After how many seconds does a user have to log back in? (1 day = 24*3600) */
  const SESSION_TIMEOUT_SECONDS = 2 * 24 * 3600;

  /** After how many days can an event be pruned by prune_events.php? */
  const EVENTS_KEEP_DAYS = 45;

  private function __construct() {
  }

  static function getTimeZone() {
    if (self::TIME_ZONE) {
      return new DateTimeZone(self::TIME_ZONE);
    }
    return null;
  }
}