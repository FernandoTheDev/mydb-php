<?php

namespace Fernando\MyDB;

use Fernando\MyDB\Php\Parser;
use Fernando\MyDB\Php\Utils\Logger;

final class MyDB
{
	protected string $databaseName = "";
	protected array $expression = [];
	protected array $data = [];

	private Logger $logger;
	private string $dirBase = __DIR__ . '/../db/';

	public function __construct() {
		$this->logger = new Logger;
	}

	public function setDatabase(?string $name = ''): void
	{
		$logger = new Logger;

		if ($name == '') {
			$logger->fatal("The Database name is mandatory.");
			return;
		}

		if (!is_dir($this->dirBase . $name)) {
			$logger->error("Database not found '{$name}'.");
			return;
		}

		$this->databaseName = $name;
	}

	public function prepare(?string $sql = ''): void
	{
		$logger = new Logger;

		if ($sql == '') {
			$logger->fatal("The SQL is required.");
			return;
		}

		$this->expression[] = $sql;
	}

	public function execute(): bool
	{
		$sql = implode("\n", $this->expression);
		$this->expression = [];

		try {
			$parser = new Parser();
			$this->data = $parser->parserAndInterpreter($sql, $this->databaseName);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function getData(): array
	{
		$newData = [];
		$data = $this->data;

		for ($i = 0; $i < count($data); $i++) {
			if (count($data[$i]) > 0) {
				$newData[] = $data[$i];
			}
		}

		return $newData;
	}
}