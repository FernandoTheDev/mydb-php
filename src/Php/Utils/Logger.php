<?php

namespace Fernando\MyDB\Php\Utils;

use Fernando\MyDB\Php\Utils\Colorize;

final class Logger
{
	public function fatal(string $message): void
	{
		$message = $message = Colorize::get("red") . $message . Colorize::get();
		die($message . PHP_EOL);
	}
	
	public function ln(): void 
	{
		echo PHP_EOL;
	}
	
	public function endln(string $message): void
	{
		$message = Colorize::get("blue") . $message . Colorize::get();
		$this->println($message);
	}
	
	public function success(string $message): void
	{
		$message = Colorize::get("green") . $message . Colorize::get();
		$this->println($message);
	}

	public function error(string $message): void
	{
		$message = Colorize::get("red") . $message . Colorize::get();
		$this->println($message);
	}

	public function warning(string $message): void
	{
		$message = Colorize::get("yellow") . $message . Colorize::get();
		$this->println($message);
	}

	public function println(mixed $message): void
	{
		echo $message . PHP_EOL;
	}
}