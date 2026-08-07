<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sms extends Model
{
    use HasFactory;

    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'sms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'phone',
        'message',
        'received_at',
        'processed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'processed' => 'boolean',
        ];
    }

    /**
     * Relationship: SMS belongs to a Device.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Scope a query to search by phone number or message content.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('phone', 'like', "%{$search}%")
              ->orWhere('message', 'like', "%{$search}%")
              ->orWhereHas('device', function (Builder $dq) use ($search) {
                  $dq->where('name', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Scope a query to filter by device ID.
     */
    public function scopeFilterByDevice(Builder $query, ?int $deviceId): Builder
    {
        if (blank($deviceId)) {
            return $query;
        }

        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope a query to include only SMS received today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('received_at', now()->toDateString())
            ->orWhere(function (Builder $q) {
                $q->whereNull('received_at')
                  ->whereDate('created_at', now()->toDateString());
            });
    }
}
