<?php

class AccountService {

  const MIN_PASS_LENGTH = 6;

  private DatabaseConnector $db;

  function __construct(DatabaseConnector $db) {
    $this->db = $db;
  }

  function handleLogin($email, $password) {
    $id = $this->db->findAccountIdByEmail($email);
    if ($id) {
      $loginEvent = $this->db->verifyPassword($id, $password);
      $this->db->addEvent($loginEvent, $_SERVER['REMOTE_ADDR'], $id);

      if ($loginEvent === EventType::LOGIN_SUCCESS) {
        $this->db->updateForSuccessfulAuth($id);
        return $id;
      } else {
        $this->db->updateForFailedAuth($id);
      }
    } else {
      $this->db->addEvent(EventType::LOGIN_FAILED, $_SERVER['REMOTE_ADDR']);
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