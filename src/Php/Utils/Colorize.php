<?php

namespace Fernando\MyDB\Php\Utils;

abstract class Colorize
{
	private const colors = [
			'white' => "\033[97m",
			'red' => "\033[91m",
			'green' => "\033[92m",
			'yellow' => "\033[93m",
			'blue' => "\033[94m",
			'magenta' => "\033[95m",
			'cyan' => "\033[96m",
			'light_gray' => "\033[37m",
			'dark_gray' => "\033[90m",
			'reset' => "\033[0m",
		];

	public static function get(?string $color = 'white'): string
	{
		return (isset(self::colors[$color]) !== NULL) ? self::colors[$color] : self::colors['white'];
	}
}