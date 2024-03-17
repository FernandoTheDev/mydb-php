<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Utils\Logger;

final class Show
{
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		$logger = new Logger;
		if (count($expression) < 1) {
			$logger->error("Invalid expression '{$expression}'.");
			return;
		}

		$command = strtoupper($expression[0]);

		switch ($command) {
			case 'DATABASES':
			case 'DATABASE':
				array_shift($expression);
				$this->showDatabase($expression);
				break;
			case 'TABLES':
			case 'TABLE':
				array_shift($expression);
				$this->showTable($expression);
				break;
				default:
			$logger->warning("Comando inválido: '{$command}'.");
			break;
		}
	}

	private function showDatabase(array $expression): void
	{
		$logger = new Logger;
		$folders = scandir($this->dirBase);
		array_shift($folders);
		array_shift($folders);

		if (count($folders) == 0) {
			$logger->println("We don't have databases to show.");
			return;
		}

		foreach ($folders as $folder) {
			$logger->println("- " . $folder);
		}

		$logger->ln();
		$logger->success("Database Total '" . count($folders) . "'.");
	}

	private function showTable(array $expression): void
	{
		$dir = $this->dirBase;
		$folders = scandir($dir);
		$filesQtd = 0;
		$logger = new Logger;

		if (!count($expression) < 1) {
			$files = scandir($dir . $expression[0] . '/');
			array_shift($files);
			array_shift($files);

			if (count($files) == 0) {
				$logger->println("We don't have tables to show.");
				return;
			}

			$logger->success($expression[0]);
			foreach ($files as $file) {
				$file = str_replace(".json", "", $file);
				$logger->println("   - " . $file);
			}
			$logger->ln();
			$logger->success("Tables Total '" . count($files) . "'.");
			return;
		}

		array_shift($folders);
		array_shift($folders);

		if (count($folders) == 0) {
			$logger->println("We don't have databases to show the tables.");
			return;
		}

		foreach ($folders as $folder) {
			$files = scandir($dir . $folder);
			array_shift($files);
			array_shift($files);
			$filesQtd += count($files);

			if (count($files) < 1) {
				$logger->println($folder);
				$logger->ln();
				continue;
			}

			$logger->success($folder);
			foreach ($files as $file) {
				if ($file == "") {
					continue;
				}
				$file = str_replace(".json", "", $file);
				$logger->println("   - " . $file);
			}
			$logger->ln();
		}

		$logger->success("Tables Total '{$filesQtd}'.");
		$logger->success("Database Total '" . count($folders) . "'.");
	}

	private function getDatabaseName(array $expression): string
	{
		return $expression[0];
	}

	private function getDatabaseAndTableName(array $expression): array
	{
		return explode(".", $expression[0]);
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