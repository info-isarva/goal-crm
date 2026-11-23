<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GoalMultiSheetExport implements WithMultipleSheets
{
    /**
     * @param  list<array{title: string, headings: list<string>, rows: list<array<int, mixed>>}>  $sheets
     */
    public function __construct(
        protected array $sheets
    ) {}

    public function sheets(): array
    {
        $out = [];
        foreach ($this->sheets as $sheet) {
            $out[] = new GoalSheetExport(
                $sheet['rows'] ?? [],
                $sheet['headings'] ?? [],
                (string) ($sheet['title'] ?? 'Sheet')
            );
        }

        return $out !== [] ? $out : [new GoalSheetExport([], ['Empty'], 'Sheet1')];
    }
}
