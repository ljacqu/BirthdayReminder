<?php

final class EventType {

  const DAILY_MAIL = 'mail.daily';
  const WEEKLY_MAIL = 'mail.weekly';

  const LOGIN_SUCCESS = 'login.success';
  const LOGIN_FAILED = 'login.failed';
  const LOGIN_LOCKED = 'login.locked';

  const PASSWORD_CHANGE = 'account.pwdChange';
  const RESET_FAILED_LOGINS = 'account.resetFailedLogins';

  private function __construct() {
  }

}