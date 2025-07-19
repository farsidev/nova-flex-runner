<?php

namespace Farsi\NovaFlexRunner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'command_name',
        'command_slug',
        'command_type',
        'category',
        'inputs',
        'output',
        'status',
        'duration',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'inputs' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['success', 'failed']);
    }

    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return '0s';
        }

        if ($this->duration < 1) {
            return round($this->duration * 1000) . 'ms';
        }

        if ($this->duration < 60) {
            return round($this->duration, 2) . 's';
        }

        $minutes = floor($this->duration / 60);
        $seconds = round($this->duration % 60, 2);

        return "{$minutes}m {$seconds}s";
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('command_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}