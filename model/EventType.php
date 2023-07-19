<?php

final class EventType {

  const DAILY_MAIL = 'mail.daily';
  const WEEKLY_MAIL = 'mail.weekly';

  const LOGIN_SUCCESS = 'login.success';
  const LOGIN_FAILED = 'login.failed';
  const LOGIN_LOCKED = 'login.locked';

  const PASSWORD_CHANGE = 'account.pwdChange';
  const EMAIL_CHANGE = 'account.emailChange';
  const RESET_FAILED_LOGINS = 'account.resetFailedLogins';

  const EVENTS_PRUNE = 'events.prune';

  private function __construct() {
  }

}