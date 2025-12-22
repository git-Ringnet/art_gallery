<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $logs;

    public function __construct($logs)
    {
        $this->logs = $logs;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->logs;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'STT',
            'Thời gian',
            'Người dùng',
            'Email',
            'Loại hoạt động',
            'Module',
            'Mô tả',
            'IP Address',
            'Đáng ngờ',
        ];
    }

    /**
     * @param mixed $log
     * @return array
     */
    public function map($log): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $log->created_at->format('d/m/Y H:i:s'),
            $log->user ? $log->user->name : 'Hệ thống',
            $log->user ? $log->user->email : '-',
            $log->getActivityTypeLabel(),
            $log->getModuleLabel(),
            $log->description,
            $log->ip_address,
            $log->is_suspicious ? 'Có' : 'Không',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
