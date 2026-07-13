<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Bayaran Tunai Semasa Penghantaran (COD)',
            self::BankTransfer => 'Pindahan Bank',
        };
    }
}
