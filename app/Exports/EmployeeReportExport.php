<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected User $employee;
    protected Collection $attendances;

    public function __construct(User $employee, Collection $attendances)
    {
        $this->employee = $employee;
        $this->attendances = $attendances;
    }

    public function collection()
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Day',
            'Clock In',
            'Clock Out',
            'Net Hours',
            'Status',
            'Late',
            'Late Minutes',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date->format('Y-m-d'),
            $row->date->format('l'),
            $row->clock_in ? $row->clock_in->format('H:i') : '-',
            $row->clock_out ? $row->clock_out->format('H:i') : '-',
            $row->net_minutes ? round($row->net_minutes / 60, 2) : '-',
            ucfirst($row->status),
            $row->is_late ? 'Yes' : 'No',
            $row->late_minutes ?? 0,
        ];
    }

    public function title(): string
    {
        return $this->employee->name . ' - Attendance';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
