<div class="order-receipt rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ config('app.name') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Resit Pesanan') }}</flux:text>
        </div>
        <div class="text-right">
            <flux:heading>{{ $order->order_number }}</flux:heading>
            <flux:text class="mt-1">{{ $order->created_at->format('d/m/Y H:i') }}</flux:text>
        </div>
    </div>

    <flux:separator class="my-4" />

    <div class="grid grid-cols-2 gap-4">
        <div>
            <flux:text class="font-medium">{{ __('Pelanggan') }}</flux:text>
            <flux:text class="mt-1">{{ $order->customer->name }}</flux:text>
            <flux:text>{{ $order->customer->phone }}</flux:text>
            @if ($order->customer->address)
                <flux:text>{{ $order->customer->address }}</flux:text>
            @endif
        </div>
        <div class="text-right">
            <flux:text class="font-medium">{{ __('Kaedah Pembayaran') }}</flux:text>
            <flux:text class="mt-1">{{ $order->payment_method->label() }}</flux:text>
            <flux:text class="font-medium">{{ __('Status') }}</flux:text>
            <flux:badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</flux:badge>
        </div>
    </div>

    <flux:separator class="my-4" />

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                <th class="py-2">{{ __('Produk') }}</th>
                <th class="py-2 text-right">{{ __('Harga Seunit') }}</th>
                <th class="py-2 text-right">{{ __('Kuantiti') }}</th>
                <th class="py-2 text-right">{{ __('Jumlah') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="py-2">{{ $order->product_name }}</td>
                <td class="py-2 text-right">RM{{ number_format((float) $order->unit_price, 2) }}</td>
                <td class="py-2 text-right">{{ $order->quantity }}</td>
                <td class="py-2 text-right">RM{{ number_format((float) $order->total_price, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="border-t border-zinc-200 dark:border-zinc-700">
                <td colspan="3" class="py-2 text-right font-medium">{{ __('Jumlah Besar') }}</td>
                <td class="py-2 text-right font-medium">RM{{ number_format((float) $order->total_price, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($order->notes)
        <flux:separator class="my-4" />
        <flux:text class="font-medium">{{ __('Nota') }}</flux:text>
        <flux:text class="mt-1">{{ $order->notes }}</flux:text>
    @endif
</div>
