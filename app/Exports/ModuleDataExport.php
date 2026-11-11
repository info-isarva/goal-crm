<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ModuleDataExport implements FromArray, WithHeadings, WithTitle
{
    protected array $rows;
    protected array $headings;
    protected string $sheetTitle;

    public function __construct(array $rows, array $headings, string $sheetTitle = 'Export')
    {
        $this->rows = $rows;
        $this->headings = $headings;
        $this->sheetTitle = mb_substr($sheetTitle, 0, 31);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
