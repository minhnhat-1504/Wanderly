<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'target_audience',
        'recipients_count',
        'action_url',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'recipients_count' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'broadcast_id');
    }

    public function getReadCountAttribute(): int
    {
        return $this->notifications()->whereNotNull('read_at')->count();
    }

    public function getReadRateAttribute(): float
    {
        if ($this->recipients_count <= 0) {
            return 0.0;
        }

        return round(($this->read_count / $this->recipients_count) * 100, 1);
    }
}
