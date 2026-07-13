<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Resit') . ' - ' . $order->order_number])

        <style>
            @media print {
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body class="min-h-screen bg-white p-6 dark:bg-white">
        <div class="no-print mb-4">
            <button onclick="window.print()" type="button" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-white">
                {{ __('Cetak') }}
            </button>
        </div>

        @include('partials.order-receipt', ['order' => $order])
    </body>
</html>
