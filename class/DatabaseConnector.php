<?php

class DatabaseConnector {

  const MAX_FAILED_LOGINS = 10;
  private const SESSION_SECRET_LENGTH = 31;

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
                and NOT (br_account.daily_flag = "ignore" AND br_birthday.flag = true
                         OR br_account.daily_flag = "filter" AND br_birthday.flag = false)
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
                and NOT (br_account.weekly_flag = "ignore" AND br_birthday.flag = true
                         OR br_account.weekly_flag = "filter" AND br_birthday.flag = false)
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

  function countBirthdays() {
    $stmt = $this->conn->query('select count(1) from br_birthday');
    return $stmt->fetch()[0];
  }

  function findNextBirthdaysForAccountId($accountId, DateTime $from, int $limit) {
    $fromMonth = (int) $from->format('m');
    $fromDay = (int) $from->format('d');

    $query = "select id, date, name, flag
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

  function findBirthdaysByAccountId($accountId, $limit=null) {
    $limitQuery = $limit ? "limit $limit" : '';
    $query = "select id, name, date, flag
              from br_birthday
              where account_id = :accountId
              order by date_2020, year(date) desc $limitQuery";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam('accountId', $accountId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function addBirthday($accountId, $name, DateTime $date) {
    $dateStr = $date->format('Y-m-d');
    $date2020 = '2020-' . $date->format('m-d');

    $query = 'insert into br_birthday (account_id, name, date, date_2020, flag) values (:accountId, :name, :date, :date2020, false)';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':date', $dateStr);
    $stmt->bindParam(':date2020', $date2020);
    $stmt->execute();
  }

  function updateBirthdayName($accountId, $id, $name) {
    $query = 'update br_birthday set name = :name where id = :id and account_id = :accountId';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->bindParam(':name', $name);
    $stmt->execute();
    return $stmt->rowCount() > 0;
  }

  function updateBirthdayDate($accountId, $id, DateTime $date) {
    $dateStr = $date->format('Y-m-d');
    $date2020 = '2020-' . $date->format('m-d');

    $query = 'update br_birthday set date = :date, date_2020 = :date2020 where id = :id and account_id = :accountId';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->bindParam(':date', $dateStr);
    $stmt->bindParam(':date2020', $date2020);
    $stmt->execute();
    return $stmt->rowCount() > 0;
  }

  function updateFlag($accountId, $id, $flag) {
    $query = 'update br_birthday set flag = :flag where id = :id and account_id = :accountId';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->bindParam(':flag', $flag);
    $stmt->execute();
    return $stmt->rowCount() > 0;
  }

  function deleteBirthday($accountId, $id) {
    $query = 'delete from br_birthday where id = :id and account_id = :accountId';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':accountId', $accountId);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->rowCount() > 0;
  }

  /* *************
   * Accounts
   * ************* */

  function addAccount($email, $pwdHash, $isAdmin) {
    $query = "insert into br_account (email, created, password, failed_logins, is_admin, daily_mail, weekly_mail, date_format, session_secret)
                              values (:email, now(), :password,             0,   :admin,           1,          0,     'd.m.Y', :sessionSecret)";
    $sessionSecret = self::createSessionSecret();

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $pwdHash);
    $stmt->bindParam(':admin', $isAdmin);
    $stmt->bindParam(':sessionSecret', $sessionSecret);
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

  function fetchEmail($id) {
    $query = 'select email from br_account where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch()[0];
  }

  function updateEmail($id, $email) {
    $query = 'update br_account set email = :email where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
  }

  function fetchMinAccountId() {
    $stmt = $this->conn->query('select min(id) from br_account');
    $stmt->execute();
    return $stmt->fetch()[0];
  }

  function fetchSessionSecret($id) {
    $stmt = $this->conn->prepare('select session_secret from br_account where id = :id');
    $stmt->bindParam(':id', $id);
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
      if ($result['failed_logins'] >= self::MAX_FAILED_LOGINS) {
        return EventType::LOGIN_LOCKED;
      } else if (password_verify($pass, $result['password'])) {
        return EventType::LOGIN_SUCCESS;
      }
    }
    return EventType::LOGIN_FAILED;
  }

  function getValuesForEmail($accountIds) {
    if (empty($accountIds)) {
      return [];
    }

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
    $query = 'update br_account set password = :password, session_secret = :sessionSecret where id = :id';
    $sessionSecret = self::createSessionSecret();

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':password', $passwordHash);
    $stmt->bindParam(':sessionSecret', $sessionSecret);
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

  function setResetToken(string $email): string|null {
    $query = 'update br_account set pass_token = :token, pass_token_date = now() where email = :email';
    $token = self::createRandomString(63);

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? $token : null;
  }

  function clearResetToken($id) {
    $query = 'update br_account set pass_token = null, pass_token_date = null where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
  }

  function getAccountFromResetToken(string $token) {
    $query = 'select id, email, pass_token_date, failed_logins from br_account where pass_token = :token';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam('token', $token);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  function getPreferences($id): UserPreference {
    $query = 'select daily_mail, daily_flag, weekly_mail, weekly_flag, date_format from br_account where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $preference = new UserPreference();
    $preference->setDailyMail($result['daily_mail']);
    $preference->setDailyFlag($result['daily_flag']);
    $preference->setWeeklyMail($result['weekly_mail']);
    $preference->setWeeklyFlag($result['weekly_flag']);
    $preference->setDateFormat($result['date_format']);
    return $preference;
  }

  function updatePreferences($id, UserPreference $preference) {
    $query = 'update br_account
              set daily_mail = :daily,
                  daily_flag = :dailyFlag,
                  weekly_mail = :weekly,
                  weekly_flag = :weeklyFlag,
                  date_format = :dateFormat
              where id = :id';
    $dailyMail = $preference->getDailyMail();
    $dailyFlag = $preference->getDailyFlag();
    $weeklyMail = $preference->getWeeklyMail();
    $weeklyFlag = $preference->getWeeklyFlag();
    $dateFormat = $preference->getDateFormat();

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':daily', $dailyMail);
    $stmt->bindParam(':dailyFlag', $dailyFlag);
    $stmt->bindParam(':weekly', $weeklyMail);
    $stmt->bindParam(':weeklyFlag', $weeklyFlag);
    $stmt->bindParam(':dateFormat', $dateFormat);
    $stmt->execute();
  }

  function getValuesForSession($accountId) {
    $query = 'select session_secret, is_admin from br_account where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $accountId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  function getAllAccountOverviews() {
    $query = 'select id, email, last_login, failed_logins, created, is_admin from br_account order by id';
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function resetFailedLoginAttempts($accountId) {
    $query = 'update br_account set failed_logins = 0 where id = :id';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $accountId);
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
    $query = 'insert into br_event (type, date, info, account_id, ip_address) values (:type, now(), :info, :account, :ipAddress)';
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':info', $info);
    $stmt->bindParam(':account', $accountId);
    $stmt->bindParam(':ipAddress', $ipAddress);
    $stmt->execute();
  }

  function getLatestEvents($limit, $offset=null) {
    $limitQuery = $offset ? "limit $limit offset $offset" : "limit $limit";
    $query = "select * from br_event order by date desc $limitQuery";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function removeEventsBefore(DateTime $date) {
    $query = 'delete from br_event where date < :date';
    $dateStr = $date->format('Y-m-d');

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':date', $dateStr);
    $stmt->execute();
    return $stmt->rowCount();
  }

  /* ***********
   * Internal
   * *********** */

  private function createTables() {
    // Create br_account
    $this->conn->exec('create table br_account (
      id int auto_increment,
      email varchar(255) not null,
      created timestamp not null,
      password varchar(255) not null,
      failed_logins int not null,
      last_login timestamp,
      session_secret varchar(31) not null,
      is_admin bool not null,
      daily_mail bool not null,
      daily_flag varchar(31) not null,
      weekly_mail int not null,
      weekly_flag varchar(31) not null,
      date_format varchar(127) not null,
      pass_token varchar(63),
      pass_token_date timestamp,
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
      flag bool not null,
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
      ip_address varchar(63),
      primary key (id),
      foreign key (account_id) references br_account(id)
      ) ENGINE = InnoDB');
  }

  private static function createSessionSecret() {
    return self::createRandomString(self::SESSION_SECRET_LENGTH);
  }

  private static function createRandomString(int $length): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
  }
}