<?php

class AccountService {

  const MIN_PASS_LENGTH = 6;
  const RESET_TOKEN_MAX_AGE_HOURS = 6;
  private const SESSION_SECRET_LENGTH = 31;

  private DatabaseConnector $db;

  function __construct(DatabaseConnector $db) {
    $this->db = $db;
  }

  function handleLogin($email, $password) {
    $id = $this->db->findAccountIdByEmail($email);
    if ($id) {
      $loginEvent = $this->db->verifyPassword($id, $password);
      $this->db->addEvent($loginEvent, null, $id);

      if ($loginEvent === EventType::LOGIN_SUCCESS) {
        $this->db->updateForSuccessfulAuth($id);
        return $id;
      } else {
        $this->db->updateForFailedAuth($id);
      }
    } else {
      $this->db->addEvent(EventType::LOGIN_FAILED, $email);
    }
    return null;
  }

  function registerInitialAdmin($emailInput, $passwordInput) {
    if (!$emailInput || strpos($emailInput, '@') === false) {
      throw new ValidationException('Please provide an email');
    }
    $this->validatePassword($passwordInput);

    $this->db->addAccount($emailInput, $this->hashPassword($passwordInput), true);
  }

  function registerUser($email, $password) {
    if (!$email || strpos($email, '@') === false) {
      throw new ValidationException('Please provide an email');
    } else if ($this->db->findAccountIdByEmail($email)) {
      throw new ValidationException('The email is already in use');
    }
    $this->validatePassword($password);

    $this->db->addAccount($email, $this->hashPassword($password), false);
  }

  function handlePasswordChange($accountId, $currentPwd, $newPwd, $confirmPwd) {
    if (!$newPwd || strlen($newPwd) < self::MIN_PASS_LENGTH) {
      return 'Please provide a new password with at least ' . self::MIN_PASS_LENGTH . ' characters';
    } else if ($newPwd !== $confirmPwd) {
      return 'The new password and the confirmation did not match';
    } else {
      $currentPwdResult = $this->db->verifyPassword($accountId, $currentPwd);
      if ($currentPwdResult === EventType::LOGIN_LOCKED) {
        // Should never happen
        return 'Your account is locked. Please contact an administrator';
      } else if ($currentPwdResult !== EventType::LOGIN_SUCCESS) {
        return 'The current password was not correct';
      }
    }

    $newHash = $this->hashPassword($newPwd);
    $this->db->updatePassword($accountId, $newHash);
    $this->db->addEvent(EventType::PASSWORD_CHANGE, null, $accountId);
    return true;
  }

  function setNewPassword($accountId, $newPwd, $confirmPwd) {
    if (!$newPwd || strlen($newPwd) < self::MIN_PASS_LENGTH) {
      return 'Please provide a new password with at least ' . self::MIN_PASS_LENGTH . ' characters';
    } else if ($newPwd !== $confirmPwd) {
      return 'The new password and the confirmation did not match';
    }

    $newHash = $this->hashPassword($newPwd);
    $this->db->updatePassword($accountId, $newHash);
    $this->db->clearResetToken($accountId);
    $this->db->addEvent(EventType::TOKEN_RESET_PASSWORD, null, $accountId);
    return true;
  }

  function getAccountOrErrorForResetToken($token) {
    $details = $this->db->getAccountFromResetToken($token);
    if (!$details) {
      return ['error' => 'no_match'];
    }
    if ($details['failed_logins'] > DatabaseConnector::MAX_FAILED_LOGINS) {
      return ['error' => 'locked'];
    }
    $tokenDate = strtotime($details['pass_token_date']);
    if ((time() - $tokenDate) > self::RESET_TOKEN_MAX_AGE_HOURS * 3600) {
      return ['error' => 'expired'];
    }

    return ['id' => $details['id'], 'email' => $details['email']];
  }

  private function hashPassword($pwd) {
    return password_hash($pwd, PASSWORD_DEFAULT);
  }

  private function validatePassword($pwd) {
    if ($pwd == null) {
      throw new ValidationException('Please provide a password');
    }
    if (strlen($pwd) < self::MIN_PASS_LENGTH) {
      throw new ValidationException('Password must be at least ' . self::MIN_PASS_LENGTH . ' characters long');
    }
  }
}