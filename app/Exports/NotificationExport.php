<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NotificationExport implements FromCollection, WithHeadings, WithMapping
{
    protected $notifications;

    public function __construct($notifications)
    {
        $this->notifications = $notifications;
    }

    public function collection()
    {
        return $this->notifications;
    }

    public function headings(): array
    {
        return [
            'UUID',
            'Subject',
            'Priority',
            'Status',
            'Channels',
            'Total Recipients',
            'Delivered',
            'Failed',
            'Success Rate (%)',
            'Created By',
            'Template',
            'Created At',
            'Sent At'
        ];
    }

    public function map($notification): array
    {
        return [
            $notification->uuid,
            $notification->subject,
            $notification->priority,
            $notification->status,
            implode(', ', $notification->channels),
            $notification->total_recipients,
            $notification->delivered_count,
            $notification->failed_count,
            $notification->success_rate,
            $notification->creator->display_name ?? 'System',
            $notification->template->name ?? 'Custom',
            $notification->created_at->format('Y-m-d H:i:s'),
            $notification->sent_at?->format('Y-m-d H:i:s') ?? '-'
        ];
    }
}
