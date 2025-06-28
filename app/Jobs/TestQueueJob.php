<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    private $testData;

    /**
     * Create a new job instance.
     */
    public function __construct($testData = null)
    {
        $this->testData = $testData ?? [
            'message' => 'Test queue job executed successfully',
            'timestamp' => now()->toISOString(),
            'random_id' => uniqid()
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('TestQueueJob started', [
            'job_id' => $this->job->getJobId(),
            'queue' => $this->job->getQueue(),
            'test_data' => $this->testData
        ]);

        // Simulate some work
        sleep(2);

        // Log success
        Log::info('TestQueueJob completed successfully', [
            'job_id' => $this->job->getJobId(),
            'execution_time' => 2,
            'test_data' => $this->testData
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TestQueueJob failed', [
            'job_id' => $this->job->getJobId(),
            'error' => $exception->getMessage(),
            'test_data' => $this->testData,
            'attempts' => $this->attempts()
        ]);
    }
}