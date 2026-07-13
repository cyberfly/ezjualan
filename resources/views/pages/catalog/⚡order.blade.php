<?php

use App\Enums\PaymentMethod;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::catalog')] #[Title('Tempah Produk')] class extends Component {
    public Product $product;

    public int $quantity = 1;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $paymentMethod = PaymentMethod::Cod->value;

    public string $notes = '';

    public string $couponCode = '';

    public ?Coupon $appliedCoupon = null;

    public function mount(Product $product): void
    {
        abort_if(! $product->is_active || $product->isOutOfStock(), 404);

        $this->product = $product;
    }

    public function subtotal(): float
    {
        return (float) $this->product->price * $this->quantity;
    }

    public function discountAmount(): float
    {
        return $this->appliedCoupon?->calculateDiscount($this->subtotal()) ?? 0.0;
    }

    public function grandTotal(): float
    {
        return $this->subtotal() - $this->discountAmount();
    }

    public function applyCoupon(): void
    {
        $this->resetErrorBag('couponCode');
        $code = trim($this->couponCode);

        if ($code === '') {
            $this->addError('couponCode', 'Sila masukkan kod kupon.');

            return;
        }

        $coupon = Coupon::findByCode($code);

        if (! $coupon || ! $coupon->isValidFor()) {
            $this->appliedCoupon = null;
            $this->addError('couponCode', 'Kod kupon tidak sah atau telah tamat tempoh.');

            return;
        }

        $this->appliedCoupon = $coupon;
    }

    public function removeCoupon(): void
    {
        $this->appliedCoupon = null;
        $this->couponCode = '';
        $this->resetErrorBag('couponCode');
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$this->product->stock],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'paymentMethod' => ['required', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::placeOrder(
            product: $this->product,
            customerData: [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?: null,
                'address' => $validated['address'] ?: null,
            ],
            quantity: $validated['quantity'],
            paymentMethod: PaymentMethod::from($validated['paymentMethod']),
            notes: $validated['notes'] ?: null,
            couponCode: $this->couponCode !== '' ? $this->couponCode : null,
        );

        $this->redirect(route('orders.confirmation', $order), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <flux:heading size="xl">{{ __('Tempah Produk') }}</flux:heading>

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>{{ $product->name }}</flux:heading>
                <flux:text class="mt-1">{{ __('Baki stok') }}: {{ $product->stock }}</flux:text>
            </div>
            <flux:heading size="lg">RM{{ number_format((float) $product->price, 2) }}</flux:heading>
        </div>
    </div>

    <form wire:submit="submit" class="space-y-6">
        <flux:input wire:model="quantity" type="number" min="1" max="{{ $product->stock }}" :label="__('Kuantiti')" />

        <div class="space-y-2">
            <div class="flex items-end gap-2">
                <flux:input wire:model="couponCode" :label="__('Kod Kupon (pilihan)')" class="flex-1" />
                <flux:button type="button" wire:click="applyCoupon">{{ __('Guna') }}</flux:button>
            </div>

            @if ($appliedCoupon)
                <div class="flex items-center justify-between rounded-lg bg-green-50 px-3 py-2 dark:bg-green-900/20">
                    <flux:text class="text-green-700 dark:text-green-400">
                        {{ __(':code digunakan', ['code' => $appliedCoupon->code]) }}
                    </flux:text>
                    <flux:button size="sm" variant="ghost" type="button" wire:click="removeCoupon">
                        {{ __('Buang') }}
                    </flux:button>
                </div>
            @endif
        </div>

        <div class="space-y-1 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex justify-between">
                <flux:text>{{ __('Subjumlah') }}</flux:text>
                <flux:text>RM{{ number_format($this->subtotal(), 2) }}</flux:text>
            </div>
            @if ($appliedCoupon)
                <div class="flex justify-between">
                    <flux:text class="text-green-600">{{ __('Diskaun') }}</flux:text>
                    <flux:text class="text-green-600">-RM{{ number_format($this->discountAmount(), 2) }}</flux:text>
                </div>
            @endif
            <flux:separator class="my-1" />
            <div class="flex justify-between">
                <flux:heading size="sm">{{ __('Jumlah Besar') }}</flux:heading>
                <flux:heading size="sm">RM{{ number_format($this->grandTotal(), 2) }}</flux:heading>
            </div>
        </div>

        <flux:input wire:model="name" :label="__('Nama Penuh')" required />
        <flux:input wire:model="phone" :label="__('No. Telefon')" placeholder="012-3456789" required />
        <flux:input wire:model="email" type="email" :label="__('E-mel (pilihan)')" />
        <flux:textarea wire:model="address" :label="__('Alamat Penghantaran (pilihan)')" rows="3" />

        <flux:radio.group wire:model="paymentMethod" :label="__('Kaedah Pembayaran')">
            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                <flux:radio :value="$method->value" :label="$method->label()" />
            @endforeach
        </flux:radio.group>

        <flux:textarea wire:model="notes" :label="__('Nota Tambahan (pilihan)')" rows="2" />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('Hantar Tempahan') }}
        </flux:button>
    </form>
</div>
