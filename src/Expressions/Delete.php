<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Cli\{
	Color,
	Printer
};

class Delete
{
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		if (count($expression) < 1) {
			Printer::getInstance()->out(Color::Fg(200, "Invalid expression '" . implode(' ', $expression) . "'."));
			return;
		}

		$command = strtoupper($expression[0]);

		switch ($command) {
			case 'DATABASES':
			case 'DATABASE':
				array_shift($expression);
				$this->database($expression);
				break;
			case 'TABLES':
			case 'TABLE':
				array_shift($expression);
				$this->table($expression);
				break;
			default:
				Printer::getInstance()->out(Color::Fg(88, "Invalid command '{$command}'."));
				break;
		}
	}

	private function table(array $expression): void
	{

		foreach ($expression as $_ => $exp) {
			list($dbName, $tableName) = $this->getDatabaseAndTableName($exp);
			$dir = $this->dirBase . $dbName;
			$file = $dir . '/' . $tableName . '.json';

			if (!is_dir($dir)) {
				Printer::getInstance()->out(Color::Fg(200, "Database not found '{$dbName}'."));
				return;
			}

			if (!file_exists($file)) {
				Printer::getInstance()->out(Color::Fg(200, "Table not found '{$dbName}.{$tableName}'."));
				return;
			}

			unlink($file);
			Printer::getInstance()->out(Color::Fg(100, "Table deleted '{$dbName}.{$tableName}'."));
		}
	}

	private function deleteDirectory(string $dir): bool
	{
		if (!file_exists($dir)) {
			return false;
		}

		if (!is_dir($dir)) {
			return false;
		}

		$files = array_diff(scandir($dir), array('.', '..'));

		foreach ($files as $file) {
			$filePath = $dir . DIRECTORY_SEPARATOR . $file;

			if (is_dir($filePath)) {
				$this->deleteDirectory($filePath);
			} else {
				unlink($filePath);
			}
		}

		return rmdir($dir);
	}

	private function database(array $expression): void
	{

		foreach ($expression as $db => $exp) {
			$dir = $this->dirBase . $exp;

			if (!is_dir($dir)) {
				Printer::getInstance()->out(Color::Fg(200, "Database not found '{$exp}'."));
				return;
			}

			$this->deleteDirectory($dir);
			Printer::getInstance()->out(Color::Fg(100, "Database deleted '{$exp}'."));
		}
	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}

	private function parseTableData(array $expression): array
	{
		$data["columns"] = [];
		$data["data"] = [];

		for ($i = 0; $i < count($expression); $i++) {
			$part = trim(str_replace(",", "", $expression[$i]));

			if ($part === '' or $part === '(') {
				continue;
			}

			if ($part === ')') {
				break;
			}

			if ($part[-1] === ")") {
				$part = str_replace(")", "", $part);
				array_push($data["columns"], $part);
				break;
			}

			array_push($data["columns"], $part);
		}

		return $data;
	}
}