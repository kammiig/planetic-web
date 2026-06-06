<?php

namespace App\Models;

use App\Enums\ProvisioningJobType;
use App\Enums\ProvisioningStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisioningJob extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'order_id', 'job_type', 'status', 'attempts', 'max_attempts',
        'started_at', 'completed_at', 'failed_at', 'error_message',
        'request_payload', 'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'job_type' => ProvisioningJobType::class,
            'status' => ProvisioningStatus::class,
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function markRunning(): void
    {
        $this->forceFill([
            'status' => ProvisioningStatus::Running,
            'started_at' => $this->started_at ?? now(),
            'attempts' => $this->attempts + 1,
        ])->save();
    }

    public function markCompleted(?array $response = null): void
    {
        $this->forceFill([
            'status' => ProvisioningStatus::Completed,
            'completed_at' => now(),
            'error_message' => null,
            'response_payload' => $response ?? $this->response_payload,
        ])->save();
    }

    public function markFailed(string $message, ?array $response = null, bool $manualReview = false): void
    {
        $this->forceFill([
            'status' => $manualReview ? ProvisioningStatus::ManualReview : ProvisioningStatus::Failed,
            'failed_at' => now(),
            'error_message' => $message,
            'response_payload' => $response ?? $this->response_payload,
        ])->save();
    }

    public function canRetry(): bool
    {
        return $this->status->isRetryable();
    }
}
