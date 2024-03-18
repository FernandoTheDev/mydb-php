<?php

namespace Fernando\MyDB\Cli;

use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;

class Color
{
	protected static ?ConsoleColor $consoleColor = null;

	private static function getInstance(): ConsoleColor
	{
		if (!self::$consoleColor instanceof ConsoleColor) {
			self::$consoleColor = new ConsoleColor();
		}

		return self::$consoleColor;
	}

	public static function apply(string $style, string $text): string
	{
		return self::getInstance()->apply($style, $text);
	}

	public static function Bg(int $colorCode, string $text): string
	{
		return self::apply('bg_color_' . $colorCode, $text);
	}

	public static function Fg(int $colorCode, string $text): string
	{
		return self::apply('color_' . $colorCode, $text);
	}
}
