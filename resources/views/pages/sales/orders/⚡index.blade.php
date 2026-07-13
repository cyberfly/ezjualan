<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pesanan')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'orders' => Order::query()
                ->with('customer')
                ->when($this->status, fn ($query) => $query->where('status', $this->status))
                ->when($this->search, function ($query) {
                    $query->where(function ($query) {
                        $query->where('order_number', 'like', "%{$this->search}%")
                            ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$this->search}%"));
                    });
                })
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">{{ __('Pesanan') }}</flux:heading>

    <div class="flex gap-4">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari no. pesanan atau nama pelanggan...')" icon="magnifying-glass" class="flex-1" />

        <flux:select wire:model.live="status" :placeholder="__('Semua Status')">
            <flux:select.option value="">{{ __('Semua Status') }}</flux:select.option>
            @foreach (OrderStatus::cases() as $case)
                <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('No. Pesanan') }}</flux:table.column>
            <flux:table.column>{{ __('Pelanggan') }}</flux:table.column>
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
                    <flux:table.cell>{{ $order->customer->name }}</flux:table.cell>
                    <flux:table.cell>{{ $order->product_name }} &times;{{ $order->quantity }}</flux:table.cell>
                    <flux:table.cell>RM{{ number_format((float) $order->total_price, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$order->status->badgeColor()" size="sm">{{ $order->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $order->created_at->format('d/m/Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ __('Tiada pesanan dijumpai.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $orders->links() }}
</div>
