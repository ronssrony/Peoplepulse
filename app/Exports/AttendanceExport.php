<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $attendances;

    public function __construct(Collection $attendances)
    {
        $this->attendances = $attendances;
    }

    public function collection()
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Department',
            'Sub-Department',
            'Date',
            'Clock In',
            'Clock Out',
            'Net Hours',
            'Status',
            'Late?',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user->employee_id,
            $row->user->name,
            $row->user->department->name ?? '',
            $row->user->subDepartment->name ?? '',
            $row->date,
            $row->clock_in,
            $row->clock_out,
            round($row->net_minutes / 60, 2),
            $row->status,
            $row->is_late ? 'Yes' : 'No',
        ];
    }
}
