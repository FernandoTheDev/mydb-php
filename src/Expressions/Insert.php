<?php

namespace Fernando\MyDB\Expressions;

final class Insert
{
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		if (count($expression) < 3) {
			echo "Sintaxe inválida. Uso correto: INSERT INTO <banco_de_dados.tabela> VALUES (<valores>).\n";
			return;
		}

		if (strtoupper($expression[0]) !== "INTO") {
			echo "Invalid expression '$expression[0]'.\n";
			return;
		}

		if (!str_contains($expression[1], '.')) {
			echo "Invalid expression '$expression[1]'.\n";
			return;
		}

		if (strtoupper($expression[2]) !== "VALUES") {
			echo "Invalid expression '$expression[2]'.\n";
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
			echo "Database não existe '{$dbName}'.\n";
			return;
		}

		$tableFile = $this->dirBase . $dbName . '/' . $tableName . '.json';

		if (!file_exists($tableFile)) {
			echo "Tabela não existe '{$tableName}'.\n";
			return;
		}

		$tableData = json_decode(file_get_contents($tableFile), true);
		$columns = $tableData["columns"];
		$expression = array_filter($expression, function ($i) {
			return $i != "" and $i != "(";
		});
		$data = [];

		$newExpression = [];

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

			if ($part[$i + 1] === ")") {
				$part = str_replace(")", "", $part);
				$data[$columns[$i]] = $part;
				break;
			}

			$data[$columns[$i]] = $part;
		}

		if (count($data) !== count($columns)) {
			echo "Amount of data is different from " . count($columns) . PHP_EOL;
			return;
		}

		$tableData['data'][] = $data;
		file_put_contents($tableFile, json_encode($tableData, JSON_PRETTY_PRINT));
		echo "Valores inseridos em '{$dbName}.{$tableName}'.\n";
	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}
}