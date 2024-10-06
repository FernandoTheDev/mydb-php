<?php

use Fernando\MyDB\MyDB;

require_once __DIR__ . '/../vendor/autoload.php';

$mydb = new MyDB();

$mydb->prepare('
-- Totalmente baseado em JSON
-- 100% escrito em PHP puro

CREATE DATABASE bot

CREATE TABLE users (
   name,
   hash
)

SELECT * FROM users 

INSERT INTO users VALUES (Fernando, 256)

INSERT INTO users VALUES (
   Jonas,
   256
)

INSERT INTO users VALUES (
   João,
   512
)

SELECT * FROM users WHERE hash < 512

SELECT name FROM users WHERE hash = 256

SHOW DATABASES

SHOW TABLES

DELETE TABLE bot.users

SHOW TABLES

DELETE DATABASE bot 
');
$mydb->execute();

/*$mydb->prepare("CREATE DATABASE test");
$mydb->prepare("CREATE TABLE games (name)");
$mydb->execute();

$mydb->setDatabase("test");

$mydb->prepare("INSERT INTO games VALUES (FiFa)");
$mydb->prepare("SELECT * FROM games");
$mydb->execute();
*/

print_r($mydb->getData());