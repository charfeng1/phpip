<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_tokens';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Related user for the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active (non-expired) tokens.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Find an active token from a plain text value.
     */
    public static function findActiveToken(?string $plainTextToken): ?self
    {
        if (empty($plainTextToken)) {
            return null;
        }

        $hashedToken = hash('sha256', $plainTextToken);

        return self::query()
            ->where('token', $hashedToken)
            ->active()
            ->first();
    }

    /**
     * Check if the token has a given ability.
     */
    public function hasAbility(string $ability): bool
    {
        $abilities = $this->abilities ?? ['*'];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /**
     * Determine if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at instanceof CarbonInterface
            && $this->expires_at->isPast();
    }
}
