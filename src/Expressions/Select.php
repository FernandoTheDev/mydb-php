<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Cli\{
	Color,
	Printer,
	ConsoleTable
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

		$data = $this->getDatabaseAndTableName($table);
	    $tableName = $data[1] ?? '';
	    $dbName = $data[0] ?? '';

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
		$table = new ConsoleTable();
		$table->setHeaders($tableData["columns"]);
		foreach ($tableData["data"] as $row) {
			$table->addRow($row);
		}
		$table->render();
	}

	private function selectFields(array $fields, array $tableData): void
	{
		$columnIndexes = [];
		foreach ($fields as $field) {
			$index = array_search($field, $tableData['columns']);
			if ($index !== false) {
				$columnIndexes[] = $index;
			} else {
				$columnIndexes[] = -1;
			}
		}
		$table = new ConsoleTable();
		$table->setHeaders($fields);

		foreach ($tableData['data'] as $row) {
			$filteredRow = [];
			foreach ($columnIndexes as $index) {
				if ($index === -1) {
					$filteredRow[] = 'N/A';
				} else {
					$filteredRow[] = $row[$index] ?? 'N/A';
				}
			}
			$table->addRow($filteredRow);
		}

		$table->render();
	}

	private function allWithWhere(array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): void
	{
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		$whereColumnIndex = array_search($whereField, $tableData['columns']);

		if ($whereColumnIndex === false) {
			Printer::getInstance()->out(Color::Fg(88, "Field '{$whereField}' not found in columns."));
			return;
		}

		$table = new ConsoleTable();
		$table->setHeaders($tableData['columns']);

		foreach ($tableData['data'] as $row) {
			$fieldValue = $row[$whereColumnIndex];

			// Debug para verificar valores e estrutura de dados
			// echo "Campo WHERE: '{$whereField}', Valor do campo: '{$fieldValue}', Valor esperado: '{$whereValue}'\n";
			// echo "Dados da linha: " . json_encode($row) . "\n";

			if ($this->evaluateCondition($fieldValue, $whereOperator, $whereValue)) {
				$fieldFound = true;
				$table->addRow($row);
			}
		}

		if ($fieldFound) {
			$table->render();
		} else {
			Printer::getInstance()->out(Color::Fg(88, "No data was found for the condition '{$whereField} {$whereOperator} {$whereValue}' in '{$dbName}.{$tableName}'."));
		}
	}

	private function evaluateCondition(string $fieldValue, string $operator, string $value): bool
	{
		// Garante que ambos os valores sejam comparados como strings para evitar problemas de tipo
		$fieldValue = (string) $fieldValue;
		$value = (string) $value;

		switch ($operator) {
			case "=":
				return $fieldValue === $value;
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

	private function especificWithWhere(array $selects, array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): void
	{
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		$columnIndexes = [];
		foreach ($selects as $select) {
			$index = array_search($select, $tableData['columns']);
			if ($index !== false) {
				$columnIndexes[] = $index;
			} else {
				$columnIndexes[] = -1;
			}
		}

		$table = new ConsoleTable();
		$table->setHeaders($selects);

		foreach ($tableData['data'] as $row) {
			$whereColumnIndex = array_search($whereField, $tableData['columns']);

			if ($whereColumnIndex !== false) {
				$fieldValue = $row[$whereColumnIndex];

				// Debug temporário para verificar valores
				// echo "Comparando '{$fieldValue}' com '{$whereValue}' usando operador '{$whereOperator}'\n";

				if ($this->evaluateCondition($fieldValue, $whereOperator, $whereValue)) {
					$fieldFound = true;

					$filteredRow = [];
					foreach ($columnIndexes as $index) {
						if ($index === -1) {
							$filteredRow[] = 'N/A';
						} else {
							$filteredRow[] = $row[$index] ?? 'N/A';
						}
					}
					$table->addRow($filteredRow);
				}
			}
		}

		if ($fieldFound) {
			$table->render();
		} else {
			Printer::getInstance()->out(Color::Fg(88, "No data was found for the condition '{$whereField} {$whereOperator} {$whereValue}' in '{$dbName}.{$tableName}'."));
		}
	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}

	private function isValidWhereClause(array $expression, int $whereIndex): bool
	{
		return isset($expression[$whereIndex + 3]) && in_array(strtoupper($expression[$whereIndex + 2]), ['=', '<', '>', '<=', '>=']);
	}
}
