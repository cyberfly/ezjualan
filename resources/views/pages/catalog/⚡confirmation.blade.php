<?php

use App\Enums\PaymentMethod;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::catalog')] #[Title('Pesanan Diterima')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['customer', 'product']);
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <div class="text-center">
        <flux:heading size="xl">{{ __('Terima kasih! Pesanan anda telah diterima.') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Sila simpan nombor pesanan ini untuk rujukan anda.') }}</flux:text>
    </div>

    @include('partials.order-receipt', ['order' => $order])

    @if ($order->payment_method === PaymentMethod::BankTransfer)
        <flux:callout color="blue" icon="information-circle" :heading="__('Arahan Pembayaran')">
            {{ config('sistem_jualan.bank_transfer_instructions') }}
        </flux:callout>
    @endif

    <div class="flex justify-center gap-2 print:hidden">
        <flux:button variant="primary" onclick="window.print()">{{ __('Cetak Resit') }}</flux:button>
        <flux:button variant="ghost" :href="route('home')" wire:navigate>{{ __('Kembali ke Produk') }}</flux:button>
    </div>
</div>
