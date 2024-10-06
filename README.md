# MyDB

![Logo](https://github.com/FernandoTheDev/mydb/assets/133247564/1e41fc90-e44a-44ab-9999-595d610b74f3)
MyDB is a relational database based on JSON and Open Source, written 100% in PHP and from absolute zero, aims to guarantee speed and simplicity in its execution, with some SQL expressions changed.

## Usage - SQL

### SELECT

```sql
SELECT * FROM db.table
SELECT column1 FROM db.table
SELECT column1, column2 FROM db.table

-- Support WHERE 
SELECT * FROM db.table WHERE name = Fernando 
```

### CREATE

```sql
-- Typing and other things removed
CREATE DATABASE name

CREATE TABLE name.table (column)

CREATE TABLE name.table (
    column1,
    column2
)
```

### INSERT

```sql
INSERT INTO database.table VALUES (bla)

INSERT INTO database.table VALUES (
    bla,
    blabla
)
```

### UPDATE

```sql
UPDATE database.table SET column1 = value1 WHERE column2 = value2
-- Complete as far as I could do
UPDATE database.table SET column1 = value1, column4 = value4 WHERE column2 = value2 AND column3 = value3
```

### DELETE

```sql  
-- We still cannot delete specific data in the table
DELETE DATABASE name
DELETE TABLE name.table
```

## Usage - PHP

### Namespace

```php
use Fernando\MyDB\MyDB;
```

### Basic

```php
use Fernando\MyDB\MyDB;

require_once __DIR__ . '/../vendor/autoload.php';

$mydb = new MyDB();
// If it exists, if not, create and add this line of code after executing the query
$mydb->setDatabase("fernando");

$mydb->prepare("SELECT * FROM users WHERE name = Mateus");
$mydb->execute();

print_r($mydb->getData());
```

Use in terminal, just run the parameterless **./mydb.php** file without parameters.

```bash
./mydb
```

### Tests

Open the tests folder, you will see 3 tests that I did where I create Database and tables among other things.

### Additional Information

Because it is an interpreter, it executes line by line, and you can prepare several queries before finally executing, as I demonstrated in **tests/three.php**.

## Authors:

- [@fernandothedev](t.me/fernandothedev)
- [@thevenrex](t.me/thevenrex)

## Suport

If you want specific improvements in optimization, security and others, contact the **Authors**

## Installation

### Composer

```bash
composer require fernandothedev/mydb-php
```
