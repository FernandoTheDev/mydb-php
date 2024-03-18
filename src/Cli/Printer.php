<?php

namespace Fernando\MyDB\Cli;

use function system, readline, readline_add_history, trim;

class Printer
{
    public function out(string $text): Printer
    {
        echo $text;
        return $this;
    }

    public function newLine(): Printer
    {
        return $this->out(PHP_EOL);
    }

    public function clear(): Printer
    {
        if (PHP_OS == 'WINNT') {
            system('cls');
        } else {
            system('clear');
        }

        $this->out("\e[H\e[J");
        return $this;
    }

    public function display(string $message): Printer
    {
        return $this->newLine()
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