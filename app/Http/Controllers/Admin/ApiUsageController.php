<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiUsageLogs;
use Illuminate\Http\Request;

class ApiUsageController extends Controller
{
    /**
     * Display the specified usage log
     */
    public function show(ApiUsageLogs $log)
    {
        $log->load(['apiKey', 'notification']);

        return response()->json([
            'id' => $log->id,
            'created_at' => $log->created_at->format('M j, Y H:i:s'),
            'method' => $log->method,
            'endpoint' => $log->endpoint,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'response_code' => $log->response_code,
            'response_time' => $log->response_time,
            'request_id' => $log->request_id,
            'error_message' => $log->error_message,
            'request_data' => $log->getSanitizedRequestData(),
            'response_data' => $log->response_data,
            'api_key' => $log->apiKey ? [
                'id' => $log->apiKey->id,
                'name' => $log->apiKey->name,
                'masked_key' => $log->apiKey->masked_key
            ] : null,
            'notification' => $log->notification ? [
                'id' => $log->notification->id,
                'subject' => $log->notification->subject
            ] : null
        ]);
    }
}