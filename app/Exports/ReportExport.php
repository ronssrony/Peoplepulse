<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected Collection $summaries;
    protected string $month;
    protected int $year;

    public function __construct(Collection $summaries, string $month, int $year)
    {
        $this->summaries = $summaries;
        $this->month = $month;
        $this->year = $year;
    }

    public function collection(): Collection
    {
        return $this->summaries->map(function ($summary) {
            return [
                'Employee ID' => $summary['employee_id'],
                'Name' => $summary['name'],
                'Email' => $summary['email'],
                'Phone' => $summary['phone'],
                'Department' => $summary['department'],
                'Sub Department' => $summary['sub_department'],
                'Designation' => $summary['designation'],
                'Attendance Days' => $summary['attendance_count'],
                'On Time Days' => $summary['on_time_count'],
                'Late Days' => $summary['late_count'],
                'Total Working Hours' => $summary['total_hours'],
                'Avg Hours/Day' => $summary['avg_hours_per_day'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Email',
            'Phone',
            'Department',
            'Sub Department',
            'Designation',
            'Attendance Days',
            'On Time Days',
            'Late Days',
            'Total Working Hours',
            'Avg Hours/Day',
        ];
    }

    public function title(): string
    {
        return "Attendance Report - {$this->month} {$this->year}";
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
