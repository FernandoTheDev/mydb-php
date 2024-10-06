<?php
namespace Fernando\MyDB\Cli;

class ConsoleTable
{
    private array $headers;
    private array $rows = [];
    private array $columnWidths = [];

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
        $this->calculateColumnWidths($headers);
    }

    public function addRow(array $row): void
    {
        $this->rows[] = $row;
        $this->calculateColumnWidths($row);
    }

    public function render(): void
    {
        $this->printLine();
        $this->printRow($this->headers);
        $this->printLine();
        foreach ($this->rows as $row) {
            $this->printRow($row);
        }
        $this->printLine();
    }

    private function calculateColumnWidths(array $row): void
    {
        foreach ($row as $key => $cell) {
            $cellLength = strlen((string)$cell);
            if (!isset($this->columnWidths[$key]) || $cellLength > $this->columnWidths[$key]) {
                $this->columnWidths[$key] = $cellLength;
            }
        }
    }

    private function printLine(): void
    {
        $line = '+';
        foreach ($this->columnWidths as $width) {
            $line .= str_repeat('-', $width + 2) . '+';
        }
        echo $line . PHP_EOL;
    }

    private function printRow(array $row): void
    {
        $line = '|';
        foreach ($row as $key => $cell) {
            $line .= ' ' . str_pad((string)$cell, $this->columnWidths[$key]) . ' |';
        }
        echo $line . PHP_EOL;
    }
}