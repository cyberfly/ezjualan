<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function pendingOrdersCount(): int
    {
        return Order::query()->where('status', OrderStatus::Pending)->count();
    }

    #[Computed]
    public function todayRevenue(): float
    {
        return (float) Order::query()
            ->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::Completed])
            ->whereDate('created_at', today())
            ->sum('total_price');
    }

    #[Computed]
    public function lowStockProducts()
    {
        return Product::active()
            ->where('stock', '<=', config('sistem_jualan.low_stock_threshold'))
            ->orderBy('stock')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentOrders()
    {
        return Order::query()->with('customer')->latest()->limit(5)->get();
    }

    #[Computed]
    public function topSellingProducts()
    {
        return Order::query()
            ->select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Pesanan Menunggu') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->pendingOrdersCount }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Jualan Hari Ini') }}</flux:text>
            <flux:heading size="xl" class="mt-1">RM{{ number_format($this->todayRevenue, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Produk Stok Rendah') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->lowStockProducts->count() }}</flux:heading>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading class="mb-4">{{ __('Pesanan Terkini') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('No. Pesanan') }}</flux:table.column>
                    <flux:table.column>{{ __('Pelanggan') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->recentOrders as $order)
                        <flux:table.row :wire:key="$order->id">
                            <flux:table.cell>
                                <flux:link :href="route('orders.show', $order)" wire:navigate>{{ $order->order_number }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->customer->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$order->status->badgeColor()" size="sm">{{ $order->status->label() }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3">{{ __('Tiada pesanan lagi.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading class="mb-4">{{ __('Produk Stok Rendah') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Produk') }}</flux:table.column>
                    <flux:table.column>{{ __('Baki Stok') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->lowStockProducts as $product)
                        <flux:table.row :wire:key="$product->id">
                            <flux:table.cell>{{ $product->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$product->isOutOfStock() ? 'red' : 'amber'" size="sm">{{ $product->stock }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="2">{{ __('Semua produk mencukupi stok.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:heading class="mb-4">{{ __('Produk Terlaris Bulan Ini') }}</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Produk') }}</flux:table.column>
                <flux:table.column>{{ __('Kuantiti Terjual') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->topSellingProducts as $row)
                    <flux:table.row>
                        <flux:table.cell>{{ $row->product_name }}</flux:table.cell>
                        <flux:table.cell>{{ $row->total_quantity }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="2">{{ __('Tiada jualan bulan ini.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
