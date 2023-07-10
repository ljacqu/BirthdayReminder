<?php

class DatabaseConnector {

  private $conn;
  private $name;

  function __construct() {
    $host = Configuration::DB_HOST;
    $this->name = Configuration::DB_NAME;
    $this->conn = new PDO("mysql:host={$host};
    dbname={$this->name}", Configuration::DB_USER, Configuration::DB_PASS,
      array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
  }

  /* **************
   * Initialization
   * ************** */

  function initTablesIfNeeded() {
    $stmt = $this->conn->query('select exists
(select table_name from information_schema.TABLES where table_schema = "' . $this->name . '" and table_name = "br_birthday");');
    $tableExists = $stmt->fetch()[0];
    if (!$tableExists) {
      $this->createTables();
    }
  }

  /* ************
   * Birthdays
   * ************ */

  function findBirthdays(DateTime $from, DateTime $to) {
    $from2020 = '2020-' . $from->format('m-d');
    $to2020   = '2020-' .   $to->format('m-d');

    $query = 'select account_id, email as account_email, name, date
              from br_birthday
              inner join br_account
                on br_account.id = br_birthday.account_id
              where date_2020 between :from and :to
              order by account_id, date';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':from', $from2020);
    $stmt->bindParam(':to', $to2020);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function hasAnyBirthday() {
    $query = 'select exists (select 1 from br_birthday)';
    $stmt = $this->conn->query($query);
    return $stmt->fetch()[0];
  }

  function findBirthdaysByAccountId($accountId) {
    $query = 'select id, name, date from br_birthday where account_id = :accountId order by date_2020';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam('accountId', $accountId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function addBirthday($accountId, $name, DateTime $date) {
    $dateStr = $date->format('Y-m-d');
    $date2020 = '2020-' . $date->format('m-d');

    $query = 'insert into br_birthday (account_id, name, date, date_2020) values (:accountId, :name, :date, :date2020)';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':date', $dateStr);
    $stmt->bindParam(':date2020', $date2020);
    $stmt->execute();
  }

  function deleteBirthday($accountId, $id) {
    $query = 'delete from br_birthday where id = :id and account_id = :accountId';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
  }

  /* *************
   * Accounts
   * ************* */

  function addAccount($email, $pwdHash, $isAdmin=false) {
    $query = 'insert into br_account (email, created, password, failed_logins, is_admin) values (:email, now(), :password, 0, :admin)';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $pwdHash);
    $stmt->bindParam(':admin', $isAdmin);
    $stmt->execute();
  }

  function hasAnyAccount() {
    $query = 'select exists(select 1 from br_account)';
    $stmt = $this->conn->query($query);
    return (bool) $stmt->fetchColumn();
  }

  function findAccountIdByEmail($email) {
    $query = 'select id from br_account where email = :email';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
      return $result['id'];
    }
    return null;
  }

  function fetchMinAccountId() {
    $stmt = $this->conn->query('select min(id) from br_account');
    $stmt->execute();
    return $stmt->fetch()[0];
  }

  function verifyPassword($id, $pass) {
    $query = 'select password, failed_logins from br_account where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      if ($result['failed_logins'] >= 10) {
        return EventType::LOGIN_LOCKED;
      } else if (password_verify($pass, $result['password'])) {
        return EventType::LOGIN_SUCCESS;
      }
    }
    return EventType::LOGIN_FAILED;
  }

  function updateForSuccessfulAuth($id) {
    $query = 'update br_account set last_login = now(), failed_logins = 0 where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
  }

  function updateForFailedAuth($id) {
    $query = 'update br_account set failed_logins = failed_logins + 1 where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
  }

  function getPreferences($id) {
    $query = 'select daily_mail, weekly_mail from br_account where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  function updatePreferences($id, bool $dailyMail, int $weeklyMail) {
    $query = 'update br_account set daily_mail = :daily, weekly_mail = :weekly where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':daily', $dailyMail);
    $stmt->bindParam(':weekly', $weeklyMail);
    $stmt->execute();
  }

  /* *************
   * Events
   * ************* */

  function getLatestEvent($type) {
    $query = 'select * from br_event where type = :type order by date desc limit 1';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':type', $type);
    $stmt->execute();
    return $stmt->fetch();
  }

  function addEvent($type, $info, $accountId=null) {
    $query = 'insert into br_event (type, date, info, account_id) values (:type, now(), :info, :account)';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':info', $info);
    $stmt->bindParam(':account', $accountId);
    $stmt->execute();
  }

  /* ***********
   * Internal
   * *********** */

  private function createTables() {
    // Create br_account
    $this->conn->exec('create table br_account (
      id int auto_increment,
      email varchar(255) not null,
      created date not null,
      password varchar(255) not null,
      failed_logins int not null,
      last_login timestamp,
      is_admin bool not null,
      daily_mail bool not null,
      weekly_mail int not null,
      primary key (id),
      unique (email)
      ) ENGINE = InnoDB');

    // Create br_birthday
    $this->conn->exec('create table br_birthday (
      id int auto_increment,
      account_id int not null,
      date date not null,
      date_2020 date not null,
      name varchar(255) not null,
      primary key (id),
      foreign key (account_id) references br_account(id)
      ) ENGINE = InnoDB');

    // Create br_event
    $this->conn->exec('create table br_event (
      id int auto_increment,
      account_id int,
      type varchar(127) not null,
      date timestamp not null,
      info varchar(255),
      primary key (id),
      foreign key (account_id) references br_account(id)
      ) ENGINE = InnoDB');
  }
}