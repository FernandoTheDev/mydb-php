<?php

namespace Fernando\MyDB\Php\Expressions;

use Fernando\MyDB\Php\Utils\Logger;

final class Show
{
	private string $dirBase = __DIR__ . '/../../../db/';

	public function run(array $expression): array
	{
		$logger = new Logger;
		if (count($expression) < 1) {
			$logger->error("Invalid expression '{$expression}'.");
			return [];
		}

		$command = strtoupper($expression[0]);

		switch ($command) {
			case 'DATABASES':
			case 'DATABASE':
				array_shift($expression);
				return $this->showDatabase($expression);
			case 'TABLES':
			case 'TABLE':
				array_shift($expression);
				return $this->showTable($expression);
			default:
				$logger->warning("Comando inválido: '{$command}'.");
				return [];
		}
	}

	private function showDatabase(array $expression): array
	{
		$data = [];
		$logger = new Logger;
		$folders = scandir($this->dirBase);
		array_shift($folders);
		array_shift($folders);

		if (count($folders) == 0) {
			return $data;
		}

		foreach ($folders as $folder) {
			$data[] = $folder;
		}

		return $data;
	}

	private function showTable(array $expression): array
	{
		$data = [];
		$dir = $this->dirBase;
		$folders = scandir($dir);
		$filesQtd = 0;
		$logger = new Logger;

		if (!count($expression) < 1) {
			$files = scandir($dir . $expression[0] . '/');
			array_shift($files);
			array_shift($files);

			if (count($files) == 0) {
				return $data;
			}

			$logger->success($expression[0]);
			foreach ($files as $file) {
				$file = str_replace(".json", "", $file);
				$data[] = $file;
			}
			return $data;
		}

		array_shift($folders);
		array_shift($folders);

		if (count($folders) == 0) {
			return $data;
		}

		foreach ($folders as $folder) {
			$data[] = $folder;
			$files = scandir($dir . $folder);
			array_shift($files);
			array_shift($files);

			if (count($files) < 1) {
				continue;
			}

			foreach ($files as $file) {
				if ($file == "") {
					continue;
				}
				$file = str_replace(".json", "", $file);
				$data[][$folder] = $file;
			}
		}

		return $data;
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