<?php

use App\Models\Customer;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Butiran Pelanggan')] class extends Component {
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function with(): array
    {
        return [
            'orders' => $this->customer->orders()->latest()->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">{{ $customer->name }}</flux:heading>

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:text>{{ __('No. Telefon') }}: {{ $customer->phone }}</flux:text>
        @if ($customer->email)
            <flux:text>{{ __('E-mel') }}: {{ $customer->email }}</flux:text>
        @endif
        @if ($customer->address)
            <flux:text>{{ __('Alamat') }}: {{ $customer->address }}</flux:text>
        @endif
    </div>

    <flux:heading>{{ __('Sejarah Pesanan') }}</flux:heading>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('No. Pesanan') }}</flux:table.column>
            <flux:table.column>{{ __('Produk') }}</flux:table.column>
            <flux:table.column>{{ __('Jumlah') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Tarikh') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($orders as $order)
                <flux:table.row :wire:key="$order->id">
                    <flux:table.cell>
                        <flux:link :href="route('orders.show', $order)" wire:navigate>{{ $order->order_number }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $order->product_name }} &times;{{ $order->quantity }}</flux:table.cell>
                    <flux:table.cell>RM{{ number_format((float) $order->total_price, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$order->status->badgeColor()" size="sm">{{ $order->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $order->created_at->format('d/m/Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('Tiada pesanan lagi.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
