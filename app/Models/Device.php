<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'status',
        'signal',
        'operator',
        'last_seen',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
            'signal' => 'integer',
        ];
    }

    /**
     * Relationship: A Device has many incoming SMS.
     */
    public function sms(): HasMany
    {
        return $this->hasMany(Sms::class);
    }

    /**
     * Generate a unique 32-character random device token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Helper to check if device is actively online (last seen within 5 minutes).
     */
    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_seen) {
            return false;
        }

        return $this->status === 'online' && $this->last_seen->diffInMinutes(now()) <= 5;
    }
}
