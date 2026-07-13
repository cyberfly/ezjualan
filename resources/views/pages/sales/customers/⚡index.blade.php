<?php

use App\Models\Customer;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pelanggan')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'customers' => Customer::query()
                ->withCount('orders')
                ->when($this->search, function ($query) {
                    $query->where(function ($query) {
                        $query->where('name', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%");
                    });
                })
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">{{ __('Pelanggan') }}</flux:heading>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Cari nama atau no. telefon...')" icon="magnifying-glass" />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Nama') }}</flux:table.column>
            <flux:table.column>{{ __('No. Telefon') }}</flux:table.column>
            <flux:table.column>{{ __('Jumlah Pesanan') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($customers as $customer)
                <flux:table.row :wire:key="$customer->id">
                    <flux:table.cell>
                        <flux:link :href="route('customers.show', $customer)" wire:navigate>{{ $customer->name }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $customer->phone }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">{{ __('Tiada pelanggan lagi.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $customers->links() }}
</div>
