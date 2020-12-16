<?php

require_once('vendor/autoload.php');

use Krugozor\Database\Mysql\Mysql as Mysql;
$db = Mysql::create("localhost", "юзер базы", "пароль базы")->setDatabaseName("имя базы")->setCharset("utf8mb4");

$db->query("UPDATE users SET sms_day = '0'");