<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NotificationStatisticsExport implements WithMultipleSheets
{
    protected $data;
    protected $dateRange;

    public function __construct($data, $dateRange)
    {
        $this->data = $data;
        $this->dateRange = $dateRange;
    }

    public function sheets(): array
    {
        return [
            0 => new SummarySheet($this->data, $this->dateRange),
            1 => new DailyStatsSheet($this->data['daily']),
            2 => new ChannelStatsSheet($this->data['channels']),
            3 => new TemplateStatsSheet($this->data['templates']),
            4 => new GroupStatsSheet($this->data['groups']),
            5 => new DeliveryStatsSheet($this->data['delivery'])
        ];
    }
}

// Summary Sheet
class SummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $data;
    protected $dateRange;

    public function __construct($data, $dateRange)
    {
        $this->data = $data;
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        $basic = $this->data['basic'];
        
        return collect([
            ['ข้อมูลสรุป', ''],
            ['ช่วงเวลา', $this->getDateRangeLabel()],
            ['วันที่ส่งออก', now()->format('d/m/Y H:i:s')],
            ['', ''],
            ['สถิติพื้นฐาน', ''],
            ['การแจ้งเตือนทั้งหมด', number_format($basic['total_notifications'])],
            ['ส่งสำเร็จ', number_format($basic['total_sent'])],
            ['รอดำเนินการ', number_format($basic['total_pending'])],
            ['ส่งไม่สำเร็จ', number_format($basic['total_failed'])],
            ['อัตราความสำเร็จ (%)', $basic['success_rate']],
            ['', ''],
            ['ข้อมูลระบบ', ''],
            ['ผู้ใช้งานทั้งหมด', number_format($basic['total_users'])],
            ['Template ทั้งหมด', number_format($basic['total_templates'])],
            ['กลุ่มทั้งหมด', number_format($basic['total_groups'])]
        ]);
    }

    public function headings(): array
    {
        return ['รายการ', 'ค่า'];
    }

    public function title(): string
    {
        return 'สรุปผล';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'A1:B1' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ],
            'A' => ['font' => ['bold' => true]],
            'A1:A5' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F5F5F5']]],
            'A6:A11' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E8']]],
            'A13:A16' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFF3E0']]]
        ];
    }

    private function getDateRangeLabel()
    {
        $labels = [
            'today' => 'วันนี้',
            'yesterday' => 'เมื่อวาน',
            'last_7_days' => '7 วันที่ผ่านมา',
            'last_30_days' => '30 วันที่ผ่านมา',
            'last_3_months' => '3 เดือนที่ผ่านมา',
            'last_6_months' => '6 เดือนที่ผ่านมา',
            'last_year' => '1 ปีที่ผ่านมา'
        ];

        return $labels[$this->dateRange] ?? $this->dateRange;
    }
}

// Daily Stats Sheet
class DailyStatsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $dailyStats;

    public function __construct($dailyStats)
    {
        $this->dailyStats = collect($dailyStats);
    }

    public function collection()
    {
        return $this->dailyStats;
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['total'],
            $row['sent'],
            $row['failed'],
            $row['total'] > 0 ? round(($row['sent'] / $row['total']) * 100, 2) . '%' : '0%'
        ];
    }

    public function headings(): array
    {
        return ['วันที่', 'ทั้งหมด', 'ส่งสำเร็จ', 'ส่งไม่สำเร็จ', 'อัตราความสำเร็จ'];
    }

    public function title(): string
    {
        return 'สถิติรายวัน';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFEBEE']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }
}E3F2FD']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }
}

// Channel Stats Sheet
class ChannelStatsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $channelStats;

    public function __construct($channelStats)
    {
        $this->channelStats = collect($channelStats);
    }

    public function collection()
    {
        return $this->channelStats;
    }

    public function map($row): array
    {
        return [
            $row['label'],
            $row['total'],
            number_format($row['total'])
        ];
    }

    public function headings(): array
    {
        return ['ช่องทาง', 'จำนวน', 'จำนวน (จัดรูปแบบ)'];
    }

    public function title(): string
    {
        return 'สถิติตามช่องทาง';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }
}

// Template Stats Sheet
class TemplateStatsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $templateStats;

    public function __construct($templateStats)
    {
        $this->templateStats = collect($templateStats);
    }

    public function collection()
    {
        return $this->templateStats;
    }

    public function map($row): array
    {
        return [
            $row->template_name ?? $row['template_name'],
            $row->usage_count ?? $row['usage_count'],
            number_format($row->usage_count ?? $row['usage_count'])
        ];
    }

    public function headings(): array
    {
        return ['ชื่อ Template', 'จำนวนการใช้', 'จำนวนการใช้ (จัดรูปแบบ)'];
    }

    public function title(): string
    {
        return 'สถิติ Template';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFF3E0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }
}

// Group Stats Sheet
class GroupStatsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $groupStats;

    public function __construct($groupStats)
    {
        $this->groupStats = collect($groupStats);
    }

    public function collection()
    {
        return $this->groupStats;
    }

    public function map($row): array
    {
        return [
            $row->group_name ?? $row['group_name'],
            $row->notification_count ?? $row['notification_count'],
            number_format($row->notification_count ?? $row['notification_count'])
        ];
    }

    public function headings(): array
    {
        return ['ชื่อกลุ่ม', 'จำนวนการใช้', 'จำนวนการใช้ (จัดรูปแบบ)'];
    }

    public function title(): string
    {
        return 'สถิติกลุ่ม';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F3E5F5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }
}

// Delivery Stats Sheet
class DeliveryStatsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $deliveryStats;

    public function __construct($deliveryStats)
    {
        $this->deliveryStats = collect($deliveryStats);
    }

    public function collection()
    {
        return $this->deliveryStats->map(function($item) {
            return (object) $item;
        });
    }

    public function map($row): array
    {
        $total = $row->sent + $row->failed + $row->pending;
        $successRate = $total > 0 ? round(($row->sent / $total) * 100, 2) : 0;
        
        return [
            $row->label,
            $row->sent,
            $row->failed,
            $row->pending,
            $total,
            $successRate . '%'
        ];
    }

    public function headings(): array
    {
        return ['ช่องทาง', 'ส่งสำเร็จ', 'ส่งไม่สำเร็จ', 'รอดำเนินการ', 'รวม', 'อัตราความสำเร็จ'];
    }

    public function title(): string
    {
        return 'สถิติการส่ง';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '