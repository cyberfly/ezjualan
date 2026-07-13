<?php

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property CouponType $type
 * @property string $value
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int|null $max_uses
 * @property int $used_count
 * @property bool $is_active
 */
#[Fillable(['code', 'type', 'value', 'starts_at', 'expires_at', 'max_uses', 'used_count', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            $coupon->code = Str::upper(trim($coupon->code));
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @param  Builder<Coupon>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isExpired(): bool
    {
        $now = now();

        return ($this->expires_at !== null && $now->greaterThan($this->expires_at))
            || ($this->starts_at !== null && $now->lessThan($this->starts_at));
    }

    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isValidFor(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isExhausted();
    }

    public function calculateDiscount(float $subtotal): float
    {
        $discount = match ($this->type) {
            CouponType::Percentage => $subtotal * (min(max((float) $this->value, 0), 100) / 100),
            CouponType::Fixed => (float) $this->value,
        };

        return round(min(max($discount, 0), $subtotal), 2);
    }

    public static function findByCode(string $code): ?self
    {
        return static::query()->where('code', Str::upper(trim($code)))->first();
    }
}
