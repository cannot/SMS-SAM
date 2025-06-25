<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryController extends Controller
{
    public function getStatus($id): JsonResponse
    {
        try {
            $logs = NotificationLog::where('notification_id', $id)->get();
            
            return response()->json([
                'success' => true,
                'data' => $logs->groupBy('status')->map->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getLogs($id): JsonResponse
    {
        try {
            $logs = NotificationLog::where('notification_id', $id)->get();
            return response()->json(['success' => true, 'data' => $logs]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        // Handle delivery status webhooks
        return response()->json(['success' => true]);
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_delivered' => NotificationLog::where('status', 'delivered')->count(),
                'total_failed' => NotificationLog::where('status', 'failed')->count(),
                'delivery_rate' => 95.5, // Calculate actual rate
            ];
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}