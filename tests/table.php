<?php 

class ConsoleTable
{
    private $headers;
    private $rows = [];
    private $columnWidths = [];

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        $this->calculateColumnWidths($headers);
    }

    public function addRow(array $row)
    {
        $this->rows[] = $row;
        $this->calculateColumnWidths($row);
    }

    public function render()
    {
        $this->printLine();
        $this->printRow($this->headers);
        $this->printLine();
        foreach ($this->rows as $row) {
            $this->printRow($row);
        }
        $this->printLine();
    }

    private function calculateColumnWidths(array $row)
    {
        foreach ($row as $key => $cell) {
            $cellLength = strlen((string)$cell);
            if (!isset($this->columnWidths[$key]) || $cellLength > $this->columnWidths[$key]) {
                $this->columnWidths[$key] = $cellLength;
            }
        }
    }

    private function printLine()
    {
        $line = '+';
        foreach ($this->columnWidths as $width) {
            $line .= str_repeat('-', $width + 2) . '+';
        }
        echo $line . PHP_EOL;
    }

    private function printRow(array $row)
    {
        $line = '|';
        foreach ($row as $key => $cell) {
            $line .= ' ' . str_pad((string)$cell, $this->columnWidths[$key]) . ' |';
        }
        echo $line . PHP_EOL;
    }
}

// Exemplo de uso
$table = new ConsoleTable();
$table->setHeaders(['ID', 'Nome', 'Email']);
$table->addRow([1, 'Fernando', 'fernando@exaaaaaaaaample.com']);
$table->addRow([2, 'Andalgobertwaaadddddaao', 'andalgoberto@examddddddple.com']);
$table->addRow([3, 'Maria dos Santos', 'maria.santos@example.com']);
$table->render();