<?php

use App\Models\Coupon;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Kupon')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('coupon-saved')]
    public function refreshCoupons(): void
    {
        // Re-render is triggered automatically when a listened event fires.
    }

    public function deleteCoupon(int $couponId): void
    {
        Coupon::findOrFail($couponId)->delete();

        Flux::toast(variant: 'success', text: __('Kupon telah dipadam.'));
    }

    public function with(): array
    {
        return [
            'coupons' => Coupon::query()
                ->when($this->search, fn ($query) => $query->where('code', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Kupon') }}</flux:heading>
        <flux:button variant="primary" wire:click="$dispatch('open-coupon-modal')">
            {{ __('Kupon Baharu') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari kod kupon...')" icon="magnifying-glass" />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Kod') }}</flux:table.column>
            <flux:table.column>{{ __('Jenis') }}</flux:table.column>
            <flux:table.column>{{ __('Nilai') }}</flux:table.column>
            <flux:table.column>{{ __('Penggunaan') }}</flux:table.column>
            <flux:table.column>{{ __('Tempoh') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Tindakan') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($coupons as $coupon)
                <flux:table.row :wire:key="$coupon->id">
                    <flux:table.cell>{{ $coupon->code }}</flux:table.cell>
                    <flux:table.cell>{{ $coupon->type->label() }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($coupon->type === \App\Enums\CouponType::Percentage)
                            {{ rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.') }}%
                        @else
                            RM{{ number_format((float) $coupon->value, 2) }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $coupon->used_count }} / {{ $coupon->max_uses ?? '∞' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($coupon->starts_at || $coupon->expires_at)
                            {{ $coupon->starts_at?->format('d/m/Y') ?? '—' }} - {{ $coupon->expires_at?->format('d/m/Y') ?? '—' }}
                        @else
                            {{ __('Tiada had') }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if (! $coupon->is_active)
                            <flux:badge color="zinc" size="sm">{{ __('Tidak Aktif') }}</flux:badge>
                        @elseif ($coupon->isExpired())
                            <flux:badge color="amber" size="sm">{{ __('Tamat Tempoh') }}</flux:badge>
                        @elseif ($coupon->isExhausted())
                            <flux:badge color="red" size="sm">{{ __('Habis Digunakan') }}</flux:badge>
                        @else
                            <flux:badge color="green" size="sm">{{ __('Aktif') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" wire:click="$dispatch('open-coupon-modal', { couponId: {{ $coupon->id }} })">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" variant="danger" wire:click="deleteCoupon({{ $coupon->id }})" wire:confirm="{{ __('Padam kupon ini?') }}">
                                {{ __('Padam') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ __('Tiada kupon lagi.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $coupons->links() }}

    <livewire:pages::sales.coupons.form-modal />
</div>
