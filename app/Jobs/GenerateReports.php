<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\ApiUsageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class GenerateReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reportType;
    protected $dateRange;
    protected $filters;

    public function __construct($reportType, $dateRange, $filters = [])
    {
        $this->reportType = $reportType;
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function handle()
    {
        try {
            switch ($this->reportType) {
                case 'delivery':
                    $this->generateDeliveryReport();
                    break;
                case 'api_usage':
                    $this->generateApiUsageReport();
                    break;
                case 'user_activity':
                    $this->generateUserActivityReport();
                    break;
                default:
                    throw new \Exception('Unknown report type: ' . $this->reportType);
            }
            
        } catch (\Exception $e) {
            Log::error('Generate Reports Job Failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    protected function generateDeliveryReport()
    {
        $query = NotificationLog::whereBetween('created_at', [
            Carbon::parse($this->dateRange['start']),
            Carbon::parse($this->dateRange['end'])
        ]);

        if (isset($this->filters['channel'])) {
            $query->where('channel', $this->filters['channel']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $data = $query->with(['notification'])
                     ->get()
                     ->map(function ($log) {
                         return [
                             'Date' => $log->created_at->format('Y-m-d H:i:s'),
                             'Notification ID' => $log->notification->uuid,
                             'Subject' => $log->notification->subject,
                             'Recipient' => $log->recipient_email,
                             'Channel' => ucfirst($log->channel),
                             'Status' => ucfirst($log->status),
                             'Sent At' => $log->sent_at?->format('Y-m-d H:i:s'),
                             'Error' => $log->error_message,
                         ];
                     });

        $filename = 'delivery_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        
        Excel::store(new \App\Exports\DeliveryReportExport($data), $filename, 'reports');
        
        Log::info("Delivery report generated: {$filename}");
    }

    protected function generateApiUsageReport()
    {
        $query = ApiUsageLog::whereBetween('requested_at', [
            Carbon::parse($this->dateRange['start']),
            Carbon::parse($this->dateRange['end'])
        ]);

        if (isset($this->filters['api_key_id'])) {
            $query->where('api_key_id', $this->filters['api_key_id']);
        }

        $data = $query->with(['apiKey'])
                     ->get()
                     ->map(function ($log) {
                         return [
                             'Date' => $log->requested_at->format('Y-m-d H:i:s'),
                             'API Key' => $log->apiKey->name,
                             'Endpoint' => $log->endpoint,
                             'Method' => $log->method,
                             'IP Address' => $log->ip_address,
                             'Response Code' => $log->response_code,
                             'Response Time (ms)' => $log->response_time_ms,
                         ];
                     });

        $filename = 'api_usage_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        
        Excel::store(new \App\Exports\ApiUsageReportExport($data), $filename, 'reports');
        
        Log::info("API usage report generated: {$filename}");
    }

    protected function generateUserActivityReport()
    {
        // This would generate user activity reports
        // Implementation depends on specific requirements
        Log::info('User activity report generation completed');
    }
}