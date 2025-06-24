<?php

namespace App\Events;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiKeyRevoked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ApiKey $apiKey;
    public User $revokedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(ApiKey $apiKey, User $revokedBy)
    {
        $this->apiKey = $apiKey;
        $this->revokedBy = $revokedBy;
    }
}