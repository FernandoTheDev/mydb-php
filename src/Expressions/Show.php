<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Cli\{
	Color,
	Printer,
	ConsoleTable
};

class Show
{
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		if (count($expression) < 1) {
			Printer::getInstance()->display("Invalid expression '{$expression}'.");
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
				Printer::getInstance()->display(Color::Fg(205, "Invalid parameter '{$command}'."));
				break;
		}
	}

	private function showDatabase(array $expression): void
	{
		$folders = scandir($this->dirBase);
		array_shift($folders);
		array_shift($folders);

		$foldersCount = count($folders);

		if ($foldersCount == 0) {
			Printer::getInstance()->out(Color::Fg(106, "We don't have databases to show."));
			return;
		}

		$table = new ConsoleTable();
		$table->setHeaders(['Database']);

		foreach ($folders as $folder) {
			$table->addRow([$folder]);
		}

		$table->render();
	}

	private function showTable(array $expression): void
	{
		$dir = $this->dirBase;
		$folders = scandir($dir);
		$filesQtd = 0;

		if (isset($expression[0])) {
			$database = $expression[0];
			if (is_dir($dir . $database)) {
				$files = scandir($dir . $database . '/');
				array_shift($files);
				array_shift($files);

				$table = new ConsoleTable();
				$table->setHeaders(['Tables']);

				foreach ($files as $file) {
					if ($file !== '.' && $file !== '..') {
						$file = str_replace(".json", "", $file);
						$table->addRow([$file]);
					}
				}

				$table->render();
				Printer::getInstance()->out(Color::Fg(100, "Tables Total '" . count($files) . "' in database '{$database}'."));
				return;
			} else {
				Printer::getInstance()->out(Color::Fg(106, "Database '{$database}' does not exist."));
				return;
			}
		}

		array_shift($folders);
		array_shift($folders);

		if (count($folders) == 0) {
			Printer::getInstance()->display(Color::Fg(106, "We don't have databases to show."));
			return;
		}

		$table = new ConsoleTable();
		$table->setHeaders(['Database', 'Tables']);

		foreach ($folders as $folder) {
			$files = scandir($dir . $folder);
			array_shift($files);
			array_shift($files);
			$filesQtd = count($files);

			if ($filesQtd > 0) {
				$table->addRow([$folder, $filesQtd]);
			} else {
				$table->addRow([$folder, 'No tables']);
			}
		}

		$table->render();
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

	private function getDatabaseName(array $expression): string
	{
		return $expression[0];
	}

	private function getDatabaseAndTableName(array $expression): array
	{
		return explode(".", $expression[0]);
	}
}
