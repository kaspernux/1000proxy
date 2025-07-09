<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderServerClient extends Model
{
    use HasFactory;

    protected $table = 'order_server_clients';

    protected $fillable = [
        'order_id',
        'server_client_id',
        'order_item_id',
        'provision_status',
        'provision_error',
        'provision_attempts',
        'provision_started_at',
        'provision_completed_at',
        'provision_config',
        'provision_log',
        'provision_duration_seconds',
        'qa_passed',
        'qa_notes',
        'qa_completed_at',
    ];

    protected $casts = [
        'provision_started_at' => 'datetime',
        'provision_completed_at' => 'datetime',
        'qa_completed_at' => 'datetime',
        'provision_config' => 'array',
        'provision_log' => 'array',
        'provision_duration_seconds' => 'decimal:2',
        'qa_passed' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serverClient(): BelongsTo
    {
        return $this->belongsTo(ServerClient::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Mark provision as started
     */
    public function markProvisionStarted(array $config = []): void
    {
        $this->update([
            'provision_status' => 'provisioning',
            'provision_started_at' => now(),
            'provision_config' => $config,
            'provision_attempts' => $this->provision_attempts + 1,
        ]);
    }

    /**
     * Mark provision as completed
     */
    public function markProvisionCompleted(array $log = []): void
    {
        $duration = $this->provision_started_at
            ? now()->diffInSeconds($this->provision_started_at, true)
            : null;

        $this->update([
            'provision_status' => 'completed',
            'provision_completed_at' => now(),
            'provision_log' => array_merge($this->provision_log ?? [], $log),
            'provision_duration_seconds' => $duration,
            'provision_error' => null,
        ]);
    }

    /**
     * Mark provision as failed
     */
    public function markProvisionFailed(string $error, array $log = []): void
    {
        $this->update([
            'provision_status' => 'failed',
            'provision_error' => $error,
            'provision_log' => array_merge($this->provision_log ?? [], $log),
        ]);
    }

    /**
     * Add log entry
     */
    public function addLogEntry(string $message, array $data = []): void
    {
        $log = $this->provision_log ?? [];
        $log[] = [
            'timestamp' => now()->toISOString(),
            'message' => $message,
            'data' => $data,
        ];

        $this->update(['provision_log' => $log]);
    }

    /**
     * Check if provision can be retried
     */
    public function canRetry(int $maxAttempts = 3): bool
    {
        return $this->provision_status === 'failed' &&
               $this->provision_attempts < $maxAttempts;
    }

    /**
     * Pass QA
     */
    public function passQA(string $notes = null): void
    {
        $this->update([
            'qa_passed' => true,
            'qa_notes' => $notes,
            'qa_completed_at' => now(),
        ]);
    }

    /**
     * Fail QA
     */
    public function failQA(string $notes): void
    {
        $this->update([
            'qa_passed' => false,
            'qa_notes' => $notes,
            'qa_completed_at' => now(),
        ]);
    }
}
