<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $couponId = null;

    public string $code = '';

    public string $type = '';

    public string $value = '';

    public ?string $startsAt = null;

    public ?string $expiresAt = null;

    public ?int $maxUses = null;

    public int $usedCount = 0;

    public bool $isActive = true;

    #[On('open-coupon-modal')]
    public function openModal(?int $couponId = null): void
    {
        $this->reset(['code', 'type', 'value', 'startsAt', 'expiresAt', 'maxUses', 'usedCount', 'isActive']);
        $this->resetErrorBag();
        $this->couponId = $couponId;
        $this->type = CouponType::Percentage->value;
        $this->isActive = true;

        if ($couponId) {
            $coupon = Coupon::findOrFail($couponId);
            $this->code = $coupon->code;
            $this->type = $coupon->type->value;
            $this->value = (string) $coupon->value;
            $this->startsAt = $coupon->starts_at?->format('Y-m-d\TH:i');
            $this->expiresAt = $coupon->expires_at?->format('Y-m-d\TH:i');
            $this->maxUses = $coupon->max_uses;
            $this->usedCount = $coupon->used_count;
            $this->isActive = $coupon->is_active;
        }

        $this->modal('coupon-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($this->couponId)],
            'type' => ['required', 'in:'.implode(',', array_column(CouponType::cases(), 'value'))],
            'value' => ['required', 'numeric', 'min:0', $this->type === CouponType::Percentage->value ? 'max:100' : 'max:999999.99'],
            'startsAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'maxUses' => ['nullable', 'integer', 'min:1'],
            'isActive' => ['boolean'],
        ]);

        $coupon = $this->couponId ? Coupon::findOrFail($this->couponId) : new Coupon;

        $coupon->fill([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'starts_at' => $validated['startsAt'] ?: null,
            'expires_at' => $validated['expiresAt'] ?: null,
            'max_uses' => $validated['maxUses'] ?: null,
            'is_active' => $validated['isActive'],
        ]);

        $coupon->save();

        $this->modal('coupon-form')->close();
        $this->dispatch('coupon-saved');

        Flux::toast(variant: 'success', text: __('Kupon berjaya disimpan.'));
    }
}; ?>

<flux:modal name="coupon-form" class="max-w-lg">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">
            {{ $couponId ? __('Kemaskini Kupon') : __('Kupon Baharu') }}
        </flux:heading>

        <flux:input wire:model="code" :label="__('Kod Kupon')" required />

        <flux:radio.group wire:model.live="type" :label="__('Jenis Diskaun')">
            @foreach (\App\Enums\CouponType::cases() as $couponType)
                <flux:radio :value="$couponType->value" :label="$couponType->label()" />
            @endforeach
        </flux:radio.group>

        <flux:input
            wire:model="value"
            type="number"
            step="0.01"
            min="0"
            :max="$type === \App\Enums\CouponType::Percentage->value ? 100 : null"
            :label="$type === \App\Enums\CouponType::Percentage->value ? __('Nilai (%)') : __('Nilai (RM)')"
            required
        />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="startsAt" type="datetime-local" :label="__('Bermula (pilihan)')" />
            <flux:input wire:model="expiresAt" type="datetime-local" :label="__('Tamat Tempoh (pilihan)')" />
        </div>

        <flux:input wire:model="maxUses" type="number" min="1" :label="__('Had Penggunaan (pilihan)')" :placeholder="__('Tanpa had')" />

        @if ($couponId)
            <flux:input :value="$usedCount" :label="__('Telah Digunakan')" disabled />
        @endif

        <flux:field variant="inline">
            <flux:label>{{ __('Aktif') }}</flux:label>
            <flux:switch wire:model="isActive" />
        </flux:field>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</flux:modal>
