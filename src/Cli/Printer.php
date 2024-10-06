<?php

namespace Fernando\MyDB\Cli;

use function system, readline, readline_add_history, trim;

class Printer
{

    protected static ?Printer $printer = null;

	public static function getInstance(): Printer
	{
		if (!self::$printer instanceof Printer) {
			self::$printer = new Printer();
		}

		return self::$printer;
	}

    public function out(string $text): Printer
    {
        echo $text . PHP_EOL;
        return self::getInstance();
    }

    public function newLine(): Printer
    {
        return self::getInstance()->out(PHP_EOL);
    }

    public function clear(): Printer
    {
        if (PHP_OS == 'WINNT') {
            system('cls');
        } else {
            system('clear');
        }

        self::getInstance()->out("\e[H\e[J");
        return self::getInstance();
    }

    public function display(string $message): Printer
    {
        return self::getInstance()->newLine()
            ->out($message)
            ->newLine()
            ->newLine();
    }

    /**
     * Read user input from terminal
     */
    public function read(?string $message = null): string
    {
        $txt = readline($message);
        readline_add_history($txt);
        return trim($txt);
    }
}