<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Cli\{
	Color,
	Printer
};

class Insert
{
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		if (count($expression) < 3) {
			Printer::getInstance()->out(Color::Fg(88, "Sintaxe inválida. Uso correto: INSERT INTO <banco_de_dados.tabela> VALUES (<valores>)."));
			Printer::getInstance()->newLine();
			return;
		}

		if (strtoupper($expression[0]) !== "INTO") {
			Printer::getInstance()->out(Color::Fg(200, "Invalid expression '$expression[0]'"));
			Printer::getInstance()->newLine();
			return;
		}

		if (!str_contains($expression[1], '.')) {
			Printer::getInstance()->out(Color::Fg(200, "Invalid expression '$expression[1]'"));
			Printer::getInstance()->newLine();
			return;
		}

		if (strtoupper($expression[2]) !== "VALUES") {
			Printer::getInstance()->out(Color::Fg(200, "Invalid expression '$expression[2]'"));
			Printer::getInstance()->newLine();
			return;
		}

		if (!$expression[3] == "(") {
			if ($expression[3][0] == "(") {
				$expression[3] = str_replace("(", "", $expression[2]);
			}
		}

		list($dbName, $tableName) = $this->getDatabaseAndTableName($expression[1]);
		array_shift($expression);
		array_shift($expression);
		array_shift($expression);


		if (!is_dir($this->dirBase . $dbName)) {
			Printer::getInstance()->out(Color::Fg(88, "Database '$dbName' does not exist."));
			Printer::getInstance()->newLine();
			return;
		}

		$tableFile = $this->dirBase . $dbName . '/' . $tableName . '.json';

		if (!file_exists($tableFile)) {
			Printer::getInstance()->out(Color::Fg(88, "Table '{$tableName}' does not exist."));
			Printer::getInstance()->newLine();
			return;
		}

		$tableData = json_decode(file_get_contents($tableFile), true);
		$columns = $tableData["columns"];
		$expression = array_filter($expression, function ($i) {
			return $i != "" and $i != "(";
		});
		$data = [];

		$newExpression = [];

		array_shift($columns);
		array_shift($columns);
		array_shift($columns);

		foreach ($expression as $index) {
			array_push($newExpression, $index);
		}

		for ($i = 0; $i < count($newExpression); $i++) {
			if ($newExpression[$i] === NULL) {
				continue;
			}

			$part = str_replace([",", "("], "", $newExpression[$i]);

			if ($part === '' or $part === '(') {
				continue;
			}

			if ($part === ')') {
				break;
			}

			if (strlen($part) > 1 and $part[$i + 1] === ")") {
				$data[$columns[$i]] = str_replace(")", "", $part);
				break;
			}

			$data[$columns[$i]] = str_replace(")", "", $part);
		}


		var_dump($columns);

		if (count($data) !== count($columns)) {
			Printer::getInstance()->out(Color::Fg(88, "Amount of data is different from " . count($columns) . "."));
			Printer::getInstance()->newLine();
			return;
		}

		$tableData['data'][] = $data;
		file_put_contents($tableFile, json_encode($tableData, JSON_PRETTY_PRINT));
		Printer::getInstance()->out(Color::Fg(200, "Inserted value in table '{$tableName}'."));
		Printer::getInstance()->newLine();

	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}
}
