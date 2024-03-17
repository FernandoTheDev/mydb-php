<?php

use Fernando\MyDB\Parser;
use Fernando\MyDB\Utils\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

$parser = new Parser();
$logger = new Logger;

/**
* Vamos verificar se o usuário passou um arquivo como parâmetro.
* Caso tenha passado não iremos pro interpretador em terminal.
*/
if (count($argv) > 1) {
	$file = $argv[1];
	if (is_file($file)) {
		$data = file_get_contents($file);
		$parser->parserAndInterpreter($data);
		exit();
	}
	$logger->warning("File not found '{$argv[1]}'.");
	exit();
}

/**
* Iniciando interpretador em terminal.
*/

system("clear");
while (true) {
	$input = readline("mydb > ");

	if ($input == "exit") {
		die("Bye!");
	}
	
	if (in_array($input, ["", " "])) {
		continue;
	}

	$init = microtime(true);
	$logger->ln();
	$parser->parserAndInterpreter($input);
	$end = microtime(true);

	$seconds = number_format($end - $init, 5);
	$logger->ln();
	$logger->endln("Executed in {$seconds}s.");
}