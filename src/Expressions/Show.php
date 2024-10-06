<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Cli\{
	Color,
	Printer
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
				Printer::getInstance()->display(Color::Bg(205, "Invalid parameter '{$command}'."));
				break;
		}
	}

	private function showDatabase(array $expression): void
	{
		$folders = scandir($this->dirBase);
		array_shift($folders);
		array_shift($folders);

		if (count($folders) == 0) {
			Printer::getInstance()->display(Color::Bg(106, "We don't have databases to show."));
			return;
		}

		foreach ($folders as $folder) {
			Printer::getInstance()->out(Color::Bg(201, "- " . $folder));
			Printer::getInstance()->newLine();
		}

		Printer::getInstance()->newLine();
		Printer::getInstance()->out(Color::Bg(100, "Databases Total '" . count($folders) . "'."));
	}

	private function showTable(array $expression): void
	{
		$dir = $this->dirBase;
		$folders = scandir($dir);
		$filesQtd = 0;

		if (!count($expression) < 1) {
			$files = scandir($dir . $expression[0] . '/');
			array_shift($files);
			array_shift($files);

			if (count($files) == 0) {
				Printer::getInstance()->display(Color::Bg(106, "We don't have tables to show."));
				return;
			}

			Printer::getInstance()->display(Color::Bg(2, $expression[0]));

			foreach ($files as $file) {
				$file = str_replace(".json", "", $file);
				Printer::getInstance()->display(Color::Bg(205, "   - " . $file));
			}
			Printer::getInstance()->newLine();
			Printer::getInstance()->display(Color::Bg(100, "Tables Total '" . count($files) . "'."));
			return;
		}

		array_shift($folders);
		array_shift($folders);

		if (count($folders) == 0) {
			Printer::getInstance()->display(Color::Bg(106, "We don't have databases to show."));
			return;
		}

		foreach ($folders as $folder) {
			$files = scandir($dir . $folder);
			array_shift($files);
			array_shift($files);
			$filesQtd += count($files);

			if (count($files) < 1) {
				Printer::getInstance()->out(Color::Bg(201, "- " . $folder));
				Printer::getInstance()->newLine();
				continue;
			}

			Printer::getInstance()->out(Color::Bg(201, "- " . $folder));

			foreach ($files as $file) {
				if ($file == "") {
					continue;
				}
				$file = str_replace(".json", "", $file);
				Printer::getInstance()->newLine();
				Printer::getInstance()->out(Color::Bg(205, "   - " . $file));
			}
			Printer::getInstance()->newLine();
		}

		$foldersCount = count($folders);

		Printer::getInstance()->newLine();
		Printer::getInstance()->out(Color::Bg(100, "Tables Total '{$filesQtd}'."));
		Printer::getInstance()->newLine();
		Printer::getInstance()->out(Color::Bg(100, "Databases Total '{$foldersCount}'."));
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
