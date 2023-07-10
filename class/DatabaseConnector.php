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
    if (!$this->birthdayTableExists()) {
      $this->createTables();
    }
  }

  function birthdayTableExists() {
    $stmt = $this->conn->query('select exists
(select table_name from information_schema.TABLES where table_schema = "' . $this->name . '" and table_name = "br_birthday");');
    return $stmt->fetch()[0];
  }

  /* ************
   * Birthdays
   * ************ */

  function findBirthdaysForDailyMail(DateTime $date) {
    $date2020 = '2020-' . $date->format('m-d');

    $query = 'select account_id, name, date
              from br_birthday
              inner join br_account
                on br_account.id = br_birthday.account_id
              where date_2020 = :date
                and br_account.daily_mail = true
              order by account_id, date_2020, date desc';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':date', $date2020);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function findBirthdaysForWeeklyMail(DateTime $from, DateTime $to, $weekday) {
    $from1 = '2020-' . $from->format('m-d');
    $to1   = '2020-' .   $to->format('m-d');
    $from2 = $from1;
    $to2   = $to1;

    // If we have something like from1=2020-12-29, to1=2020-01-04, need to use two intervals!
    if ($from->format('m') === '12' && $to->format('m') === '01') {
      $to2   = $to1;
      $to1   = '2020-12-31';
      $from2 = '2020-01-01';
    }

    $query = 'select account_id, name, date
              from br_birthday
              inner join br_account
                on br_account.id = br_birthday.account_id
              where (date_2020 between :from1 and :to1
                     or date_2020 between :from2 and :to2)
                and br_account.weekly_mail = :weekday
              order by case when month(date_2020) < month(:from1) then 1 else 0 end,
                       month(date_2020),
                       day(date_2020),
                       year(date) desc';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':from1', $from1);
    $stmt->bindParam(':to1', $to1);
    $stmt->bindParam(':from2', $from2);
    $stmt->bindParam(':to2', $to2);
    $stmt->bindParam(':weekday', $weekday);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function hasAnyBirthday() {
    $query = 'select exists (select 1 from br_birthday)';
    $stmt = $this->conn->query($query);
    return $stmt->fetch()[0];
  }

  function findNextBirthdaysForAccountId($accountId, DateTime $from, int $limit) {
    $fromMonth = (int) $from->format('m');
    $fromDay = (int) $from->format('d');

    $query = "select id, date, name
              from br_birthday
              where account_id = :accountId
              order by case
                            when (month(date_2020) < :fromMonth OR (month(date_2020) = :fromMonth AND day(date_2020) < :fromDay))
                            then CONCAT('2024-', LPAD(MONTH(date_2020), 2, '0'), '-', LPAD(DAY(date_2020), 2, '0'))
                            else date_2020 end,
                       year(date) desc
              limit $limit";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':fromMonth', $fromMonth);
    $stmt->bindParam(':fromDay',   $fromDay);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function findBirthdaysByAccountId($accountId) {
    $query = 'select id, name, date
              from br_birthday
              where account_id = :accountId
              order by date_2020, year(date) desc';
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
    $query = "insert into br_account (email, created, password, failed_logins, is_admin, daily_mail, weekly_mail, date_format)
                              values (:email, now(), :password,             0,   :admin,           1,          0,     'd.m.Y')";
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

  function getValuesForEmail($accountIds) {
    $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
    $query = "select id, email, date_format
              from br_account
              where id in ($placeholders)";
    $stmt = $this->conn->prepare($query);
    foreach ($accountIds as $k => $v) {
      $stmt->bindValue($k + 1, $v);
    }
    $stmt->execute();

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $results[$row['id']] = $row;
    }
    return $results;
  }

  function updatePassword($id, $passwordHash) {
    $query = 'update br_account set password = :password where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':password', $passwordHash);
    $stmt->execute();
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
    $query = 'select daily_mail, weekly_mail, date_format from br_account where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  function updatePreferences($id, bool $dailyMail, int $weeklyMail, string $dateFormat) {
    $query = 'update br_account set daily_mail = :daily, weekly_mail = :weekly, date_format = :dateFormat where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':daily', $dailyMail);
    $stmt->bindParam(':weekly', $weeklyMail);
    $stmt->bindParam(':dateFormat', $dateFormat);
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
      date_format varchar(127) not null,
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