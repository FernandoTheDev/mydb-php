<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Cli\{
	Color,
	Printer
};

class Update
{
	private string $reservedWordSet = "SET";
	private string $reservedWordWhere = "WHERE";
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression, ?string $databaseName = ""): void
	{
		// echo "Expression: " . var_export($expression, true) . "\n";
		if (empty($expression)) {
			Printer::getInstance()->out("Invalid expression.");
			return;
		}

		$setData = [];
		$whereData = [];
		$table = "";
		$currentIndex = 0;

		while ($currentIndex < count($expression)) {
			$keyword = strtoupper($expression[$currentIndex]);

			switch ($keyword) {
				case $this->reservedWordSet:
					$setData = $this->parseSetData($expression, $currentIndex);
					$currentIndex += count($setData) * 3 + 1; // Ajustar incremento
					break;
				case $this->reservedWordWhere:
					$whereData = $this->parseWhereData($expression, $currentIndex);
					$currentIndex += count($whereData) * 4 + 1; // Ajustar incremento
					break;
				default:
					if (empty($table)) {
						$table = $keyword;
					}
					$currentIndex++;
					break;
			}
		}

		$d = $this->getDatabaseAndTableName($expression[0]);
		$this->processUpdate($d[1], $setData, $whereData, $d[0]);
	}

	private function parseSetData(array $expression, int $setIndex): array
	{
		$setData = [];
		$currentIndex = $setIndex + 1;

		while ($currentIndex < count($expression) && strtoupper($expression[$currentIndex]) !== $this->reservedWordWhere) {
			$field = $expression[$currentIndex];

			if (isset($expression[$currentIndex + 1]) && strtoupper($expression[$currentIndex + 1]) === '=') {
				$value = $expression[$currentIndex + 2];
				$value = rtrim($value, ',');
				$setData[$field] = $value;
				$currentIndex += 3;

				if ($currentIndex < count($expression)) {
					while ($currentIndex < count($expression) && strtoupper($expression[$currentIndex]) === ',') {
						$currentIndex++;
					}
				}
			} else {
				break;
			}
		}

		return $setData;
	}

	private function parseWhereData(array $expression, int $whereIndex): array
	{
		$whereData = [];
		$currentIndex = $whereIndex + 1;

		while ($currentIndex < count($expression)) {
			$field = $expression[$currentIndex];
			$operator = $expression[$currentIndex + 1];
			$value = $expression[$currentIndex + 2];

			// Debugging
			// Printer::getInstance()->out("Parsed where data: $field $operator $value");

			$whereData[] = [$field, $operator, $value];
			$currentIndex += 4;

			if ($currentIndex < count($expression) && strtoupper($expression[$currentIndex]) === 'AND') {
				$currentIndex++;
			} else {
				break;
			}
		}

		return $whereData;
	}

	private function processUpdate(string $tableName, array $setData, array $whereData, ?string $databaseName = ""): void
	{
		if (empty($databaseName)) {
			list($dbName, $tableName) = $this->getDatabaseAndTableName($tableName);

			if (!is_dir($this->dirBase . $dbName)) {
				Printer::getInstance()->out(Color::Bg(200, "Database does not exist '{$dbName}'."));
				return;
			}
		} else {
			$dbName = $databaseName;

			if (!is_dir($this->dirBase . $dbName)) {
				Printer::getInstance()->out(Color::Bg(200, "Database does not exist '{$dbName}'."));
				return;
			}
		}

		$tableFile = $this->dirBase . $dbName . '/' . $tableName . '.json';

		if (!file_exists($tableFile)) {
			Printer::getInstance()->out(Color::Bg(200, "Table does not exist '{$dbName}.{$tableName}'."));
			return;
		}

		$tableData = json_decode(file_get_contents($tableFile), true);

		if ($tableData === null) {
			Printer::getInstance()->out(Color::Bg(200, "Error decoding JSON from table file '{$tableFile}'."));
			return;
		}

		$this->performUpdate($tableData, $setData, $whereData, $tableFile);
	}

	private function getColumnMapping(array $tableData): array
	{
		$mapping = [];
		foreach ($tableData["columns"] as $index => $column) {
			$mapping[$column] = $index;
		}
		return $mapping;
	}

	private function performUpdate(array &$tableData, array $setData, array $whereData, string $tableFile): void
	{
		$columnMapping = $this->getColumnMapping($tableData);

		foreach ($tableData["data"] as &$row) {
			$updateRow = true;
			foreach ($whereData as $condition) {
				list($field, $operator, $value) = $condition;

				if (!isset($columnMapping[$field])) {
					$updateRow = false;
					break;
				}

				$fieldValue = $row[$columnMapping[$field]];

				if (!$this->evaluateCondition($fieldValue, $operator, $value)) {
					$updateRow = false;
					break;
				}
			}
			if ($updateRow) {
				foreach ($setData as $field => $value) {
					if (isset($columnMapping[$field])) {
						$row[$columnMapping[$field]] = $value;
					}
				}
			}
		}

		Printer::getInstance()->out(Color::Bg(100, "Updated successfully."));
		file_put_contents($tableFile, json_encode($tableData, JSON_PRETTY_PRINT));
	}

	private function evaluateCondition($fieldValue, string $operator, $value): bool
	{
		// Debugging
		// Printer::getInstance()->out("Evaluating condition: $fieldValue $operator $value");

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
}
