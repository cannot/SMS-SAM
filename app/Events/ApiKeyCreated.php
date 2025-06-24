<?php

namespace App\Events;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiKeyCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ApiKey $apiKey;
    public User $createdBy;

    /**
     * Create a new event instance.
     */
    public function __construct(ApiKey $apiKey, User $createdBy)
    {
        $this->apiKey = $apiKey;
        $this->createdBy = $createdBy;
    }
}