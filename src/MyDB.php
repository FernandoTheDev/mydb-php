<?php

namespace Fernando\MyDB;

use Fernando\MyDB\Cli\{
	Color,
	Printer
};
use Fernando\MyDB\Php\Parser;

class MyDB
{
	protected string $databaseName = "";
	protected array $expression = [];
	protected array $data = [];

	private string $dirBase = __DIR__ . '/../db/';


	public function setDatabase(?string $name = ''): void
	{

		if ($name == '') {
			Printer::getInstance()->out(Color::Fg(200, "The Database name is mandatory."));
			return;
		}

		if (!is_dir($this->dirBase . $name)) {
			Printer::getInstance()->out(Color::Fg(200, "Database not found: {$name}"));
			return;
		}

		$this->databaseName = $name;
	}

	public function prepare(?string $sql = ''): void
	{

		if ($sql == '') {
			Printer::getInstance()->out(Color::Fg(200, "The SQL is required."));
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
		} catch (\Exception $e) {
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