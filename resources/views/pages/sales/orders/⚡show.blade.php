<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Butiran Pesanan')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['customer', 'product']);
    }

    public function transitionTo(string $status): void
    {
        $this->order->transitionTo(OrderStatus::from($status));
        $this->order->refresh();

        Flux::toast(variant: 'success', text: __('Status pesanan telah dikemaskini.'));
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
        <flux:badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</flux:badge>
    </div>

    @include('partials.order-receipt', ['order' => $order])

    <div class="flex flex-wrap items-center gap-2">
        @foreach ($order->status->allowedNextStatuses() as $next)
            <flux:button
                :variant="$next === \App\Enums\OrderStatus::Cancelled ? 'danger' : 'primary'"
                wire:click="transitionTo('{{ $next->value }}')"
                wire:confirm="{{ __('Tukar status pesanan kepada :status?', ['status' => $next->label()]) }}"
            >
                {{ $next->label() }}
            </flux:button>
        @endforeach

        <flux:spacer />

        <flux:button variant="ghost" :href="route('orders.receipt', $order)" target="_blank">
            {{ __('Cetak Resit') }}
        </flux:button>
    </div>
</div>
