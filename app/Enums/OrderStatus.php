<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Confirmed => 'Disahkan',
            self::Shipped => 'Dihantar',
            self::Completed => 'Selesai',
            self::Cancelled => 'Batal',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Confirmed => 'blue',
            self::Shipped => 'indigo',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNextStatuses(), strict: true);
    }
}
