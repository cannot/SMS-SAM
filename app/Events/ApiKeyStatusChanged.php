<?php

namespace App\Events;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiKeyStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ApiKey $apiKey;
    public User $changedBy;
    public bool $oldStatus;
    public bool $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(ApiKey $apiKey, User $changedBy, bool $oldStatus, bool $newStatus)
    {
        $this->apiKey = $apiKey;
        $this->changedBy = $changedBy;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}