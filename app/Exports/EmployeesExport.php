<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function collection()
    {
        return $this->employees;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Tên nhân viên',
            'Email',
            'Số điện thoại',
            'Trạng thái',
            'Ngày tạo',
            'Đăng nhập cuối'
        ];
    }

    public function map($employee): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $employee->name,
            $employee->email,
            $employee->phone ?? '-',
            $employee->is_active ? 'Hoạt động' : 'Ngừng hoạt động',
            $employee->created_at->format('d/m/Y H:i'),
            $employee->last_login_at ? $employee->last_login_at->format('d/m/Y H:i') : 'Chưa đăng nhập',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // STT
            'B' => 25,  // Tên nhân viên
            'C' => 30,  // Email
            'D' => 15,  // Số điện thoại
            'E' => 18,  // Trạng thái
            'F' => 18,  // Ngày tạo
            'G' => 18,  // Đăng nhập cuối
        ];
    }
}
