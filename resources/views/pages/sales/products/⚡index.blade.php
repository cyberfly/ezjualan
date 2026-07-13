<?php

use App\Models\Product;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Produk')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('product-saved')]
    public function refreshProducts(): void
    {
        // Re-render is triggered automatically when a listened event fires.
    }

    public function deleteProduct(int $productId): void
    {
        Product::findOrFail($productId)->delete();

        \Flux\Flux::toast(variant: 'success', text: __('Produk telah dipadam.'));
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Produk') }}</flux:heading>
        <flux:button variant="primary" wire:click="$dispatch('open-product-modal')">
            {{ __('Produk Baharu') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari produk...')" icon="magnifying-glass" />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Produk') }}</flux:table.column>
            <flux:table.column>{{ __('Harga') }}</flux:table.column>
            <flux:table.column>{{ __('Stok') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Tindakan') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($products as $product)
                <flux:table.row :wire:key="$product->id">
                    <flux:table.cell>{{ $product->name }}</flux:table.cell>
                    <flux:table.cell>RM{{ number_format((float) $product->price, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$product->isOutOfStock() ? 'red' : ($product->isLowStock() ? 'amber' : 'zinc')" size="sm">
                            {{ $product->stock }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$product->is_active ? 'green' : 'zinc'" size="sm">
                            {{ $product->is_active ? __('Aktif') : __('Tidak Aktif') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" wire:click="$dispatch('open-product-modal', { productId: {{ $product->id }} })">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" variant="danger" wire:click="deleteProduct({{ $product->id }})" wire:confirm="{{ __('Padam produk ini?') }}">
                                {{ __('Padam') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('Tiada produk lagi.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $products->links() }}

    <livewire:pages::sales.products.form-modal />
</div>
