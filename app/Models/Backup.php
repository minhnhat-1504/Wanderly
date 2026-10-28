<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'type',
        'filename',
        'disk',
        'size_bytes',
        'status',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * The admin user who initiated this backup (if triggered manually).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Check if backup is completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if backup has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if backup is still pending / in progress.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this is a full backup (database + uploads).
     */
    public function isFull(): bool
    {
        return $this->type === 'full';
    }

    /**
     * Human readable file size (e.g. 1.25 MB).
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    /**
     * Check if physical backup file actually exists on storage disk.
     */
    public function fileExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->filename);
    }
}
