<?php

use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?int $productId = null;

    public string $name = '';

    public string $description = '';

    public string $price = '';

    public int $stock = 0;

    public bool $isActive = true;

    public $image = null;

    public ?string $existingImagePath = null;

    #[On('open-product-modal')]
    public function openModal(?int $productId = null): void
    {
        $this->reset(['name', 'description', 'price', 'stock', 'isActive', 'image', 'existingImagePath']);
        $this->resetErrorBag();
        $this->productId = $productId;

        if ($productId) {
            $product = Product::findOrFail($productId);
            $this->name = $product->name;
            $this->description = (string) $product->description;
            $this->price = (string) $product->price;
            $this->stock = $product->stock;
            $this->isActive = $product->is_active;
            $this->existingImagePath = $product->image_path;
        } else {
            $this->isActive = true;
        }

        $this->modal('product-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $product = $this->productId ? Product::findOrFail($this->productId) : new Product;

        $product->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $validated['isActive'],
        ]);

        if ($this->image) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->image_path = $this->image->store('products', 'public');
        }

        $product->save();

        $this->modal('product-form')->close();
        $this->dispatch('product-saved');

        Flux::toast(variant: 'success', text: __('Produk berjaya disimpan.'));
    }
}; ?>

<flux:modal name="product-form" class="max-w-lg">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">
            {{ $productId ? __('Kemaskini Produk') : __('Produk Baharu') }}
        </flux:heading>

        <flux:input wire:model="name" :label="__('Nama Produk')" required />
        <flux:textarea wire:model="description" :label="__('Penerangan')" rows="3" />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="price" type="number" step="0.01" min="0" :label="__('Harga (RM)')" required />
            <flux:input wire:model="stock" type="number" min="0" :label="__('Stok')" required />
        </div>

        <flux:field variant="inline">
            <flux:label>{{ __('Aktif') }}</flux:label>
            <flux:switch wire:model="isActive" />
        </flux:field>

        <flux:input wire:model="image" type="file" accept="image/*" :label="__('Gambar Produk (pilihan)')" />

        @if ($image)
            <img src="{{ $image->temporaryUrl() }}" class="h-24 rounded-lg object-cover" />
        @elseif ($existingImagePath)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingImagePath) }}" class="h-24 rounded-lg object-cover" />
        @endif

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</flux:modal>
