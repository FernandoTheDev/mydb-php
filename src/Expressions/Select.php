<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Cli\{
	Color,
	Printer
};

class Select
{
	private string $reservedWord = "FROM";
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		if (empty($expression)) {
			Printer::getInstance()->out(Color::Fg(200, "Invalid expression '{$expression}'."));
			return;
		}

		$fromIndex = array_search(strtoupper($this->reservedWord), array_map('strtoupper', $expression));
		if ($fromIndex === false) {
			Printer::getInstance()->out(Color::Fg(200, "'FROM' not found in expression '{$expression}'."));
			return;
		}

		if (!isset($expression[$fromIndex + 1])) {
			Printer::getInstance()->out(Color::Fg(200, "The table was not passed '{$expression}'."));
			return;
		}

		$table = $expression[$fromIndex + 1];
		$selects = $this->parseSelects($expression, $fromIndex);

		$this->processSelect($selects, $table, $expression);
	}

	private function parseSelects(array $expression, int $fromIndex): array
	{
		$selects = [];
		for ($index = 0; $index < $fromIndex; $index++) {
			if ($expression[$index] !== ",") {
				$selects[] = str_replace(',', '', $expression[$index]);
			}
		}
		return $selects;
	}

	private function processSelect(array $selects, string $table, array $expression): void
	{
		if (empty($selects)) {
			Printer::getInstance()->out(Color::Fg(200, "The selection type was not passed '" . implode(" ", $expression) . "'."));
			return;
		}

		list($dbName, $tableName) = $this->getDatabaseAndTableName($table);

		if (!is_dir($this->dirBase . $dbName)) {
			Printer::getInstance()->out(Color::Fg(88, "Database not exists '{$dbName}'."));
			return;
		}

		$tableFile = $this->dirBase . $dbName . '/' . $tableName . '.json';

		if (!file_exists($tableFile)) {
			Printer::getInstance()->out(Color::Fg(88, "Table not exists '{$dbName}.{$tableName}'."));
			return;
		}

		$tableData = json_decode(file_get_contents($tableFile), true);

		$whereIndex = array_search(strtoupper("WHERE"), array_map('strtoupper', $expression));
		if (count($selects) === 1 && $selects[0] === "*") {
			if ($whereIndex !== false && $this->isValidWhereClause($expression, $whereIndex)) {
				$whereField = $expression[$whereIndex + 1];
				$whereOperator = $expression[$whereIndex + 2];
				$whereValue = $expression[$whereIndex + 3];

				$this->allWithWhere([$dbName, $tableName], $tableData, $whereField, $whereOperator, $whereValue);
				return;
			}
			$this->all([$dbName, $tableName], $expression, $tableData);
			return;
		}

		if ($whereIndex !== false && $this->isValidWhereClause($expression, $whereIndex)) {
			$whereField = $expression[$whereIndex + 1];
			$whereOperator = $expression[$whereIndex + 2];
			$whereValue = $expression[$whereIndex + 3];

			$this->especificWithWhere($selects, [$dbName, $tableName], $tableData, $whereField, $whereOperator, $whereValue);
		} else {
			$this->selectFields($selects, $tableData);
		}
	}

	private function all(array $name, array $expression, array $tableData): void
	{
		list($dbName, $tableName) = $name;

		foreach ($tableData["data"] as $columnName => $columnData) {
			Printer::getInstance()->out(Color::Bg(100, "{$columnName}:") . PHP_EOL);
			foreach ($columnData as $key => $value) {
				echo " - {$key}: {$value}\n";
			}
			echo PHP_EOL;
		}

		Printer::getInstance()->out(Color::Bg(100, "All table data from '{$dbName}.{$tableName}'."));
		Printer::getInstance()->newLine();
	}

	private function selectFields(array $fields, array $tableData): void
	{

		foreach ($tableData["data"] as $columnData) {
			$selectedData = array_intersect_key($columnData, array_flip($fields));

			if (!empty($selectedData)) {
				foreach ($selectedData as $columnName => $fieldValue) {
					Printer::getInstance()->out(Color::Bg(100, "$columnName:"));
					Printer::getInstance()->out(Color::Bg(95, " {$fieldValue}"));
					Printer::getInstance()->newLine();
				}
				Printer::getInstance()->newLine();
			}
		}
	}

	private function allWithWhere(array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): void
	{
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		foreach ($tableData["data"] as $columnName => $columnData) {
			if (isset($columnData[$whereField]) && $this->evaluateCondition($columnData[$whereField], $whereOperator, $whereValue)) {
				$fieldFound = true;
				Printer::getInstance()->out(Color::Bg(100, "{$columnName}:"));
				foreach ($columnData as $cK => $cD) {
					echo "  - {$cK}: {$cD}\n";
				}
				echo PHP_EOL;
			}
		}

		if (!$fieldFound) {
			Printer::getInstance()->out(Color::Fg(88, "No data was found for the condition '{$whereField} {$whereOperator} {$whereValue}' in '{$dbName}.{$tableName}'."));
		}
	}

	private function especificWithWhere(array $selects, array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): void
	{
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		foreach ($tableData["data"] as $columnName => $columnData) {
			if (isset($columnData[$whereField]) && $this->evaluateCondition($columnData[$whereField], $whereOperator, $whereValue)) {
				$fieldFound = true;
				Printer::getInstance()->out(Color::Bg(100, "{$columnName}:") . PHP_EOL);
				foreach ($selects as $select) {
					if (isset($columnData[$select])) {
						echo "  - {$select}: {$columnData[$select]}\n";
					} else {
						echo "Campo '{$select}' não encontrado em '{$dbName}.{$tableName}'.\n";
					}
				}
				echo PHP_EOL;
			}
		}

		if (!$fieldFound) {
			Printer::getInstance()->out(Color::Fg(88, "No data was found for the condition '{$whereField} {$whereOperator} {$whereValue}' in '{$dbName}.{$tableName}'."));
		}
	}

	private function evaluateCondition(string $fieldValue, string $operator, string $value): bool
	{
		switch ($operator) {
			case "=":
				return $fieldValue == $value;
			case "<":
				return $fieldValue < $value;
			case ">":
				return $fieldValue > $value;
			case "<=":
				return $fieldValue <= $value;
			case ">=":
				return $fieldValue >= $value;
			default:
				return false;
		}
	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}

	private function isValidWhereClause(array $expression, int $whereIndex): bool
	{
		// Verifica se há ao menos 3 tokens após WHERE e se o segundo e terceiro tokens formam uma expressão válida
		return isset($expression[$whereIndex + 3]) && in_array(strtoupper($expression[$whereIndex + 2]), ['=', '<', '>', '<=', '>=']);
	}
}
