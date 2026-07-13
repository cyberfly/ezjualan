<?php

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::catalog')] #[Title('Produk')] class extends Component {
    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Product>
     */
    public function products()
    {
        return Product::active()->latest()->paginate(12);
    }

    public function with(): array
    {
        return [
            'products' => $this->products(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Produk Kami') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Pilih produk dan buat tempahan terus tanpa perlu daftar akaun.') }}</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($products as $product)
            <div class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="relative aspect-video overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                    @if ($product->imageUrl())
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="size-full object-cover" />
                    @else
                        <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                    @endif

                    @if ($product->isOutOfStock())
                        <flux:badge color="red" class="absolute top-2 right-2">{{ __('Stok Kosong') }}</flux:badge>
                    @elseif ($product->isLowStock())
                        <flux:badge color="amber" class="absolute top-2 right-2">{{ __('Stok Terhad') }}</flux:badge>
                    @endif
                </div>

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <flux:heading>{{ $product->name }}</flux:heading>
                    @if ($product->description)
                        <flux:text class="line-clamp-2">{{ $product->description }}</flux:text>
                    @endif

                    <div class="mt-auto flex items-center justify-between pt-2">
                        <flux:heading size="lg">RM{{ number_format((float) $product->price, 2) }}</flux:heading>

                        @if ($product->isOutOfStock())
                            <flux:button disabled>{{ __('Tempah') }}</flux:button>
                        @else
                            <flux:button variant="primary" :href="route('orders.create', $product)" wire:navigate>
                                {{ __('Tempah') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($products->isEmpty())
        <flux:text class="text-center">{{ __('Tiada produk tersedia pada masa ini.') }}</flux:text>
    @endif

    {{ $products->links() }}
</div>
