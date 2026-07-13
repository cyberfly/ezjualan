<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidOrderStatusTransition;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string $unit_price
 * @property int $quantity
 * @property string|null $subtotal
 * @property int|null $coupon_id
 * @property string|null $coupon_code
 * @property string $discount_amount
 * @property string $total_price
 * @property PaymentMethod $payment_method
 * @property OrderStatus $status
 * @property string|null $notes
 * @property Carbon $created_at
 */
#[Fillable(['order_number', 'customer_id', 'product_id', 'product_name', 'unit_price', 'quantity', 'subtotal', 'coupon_id', 'coupon_code', 'discount_amount', 'total_price', 'payment_method', 'status', 'notes'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public static function generateOrderNumber(): string
    {
        do {
            $candidate = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
        } while (static::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, address?: string|null}  $customerData
     */
    public static function placeOrder(
        Product $product,
        array $customerData,
        int $quantity,
        PaymentMethod $paymentMethod,
        ?string $notes,
        ?string $couponCode = null,
    ): self {
        return DB::transaction(function () use ($product, $customerData, $quantity, $paymentMethod, $notes, $couponCode) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if (! $lockedProduct->is_active || $lockedProduct->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok produk tidak mencukupi untuk kuantiti yang diminta.',
                ]);
            }

            $subtotal = (float) $lockedProduct->price * $quantity;
            $discountAmount = 0.0;
            $coupon = null;

            if ($couponCode !== null && trim($couponCode) !== '') {
                $coupon = Coupon::query()->where('code', Str::upper(trim($couponCode)))->lockForUpdate()->first();

                if (! $coupon || ! $coupon->isValidFor()) {
                    throw ValidationException::withMessages([
                        'couponCode' => 'Kod kupon tidak sah atau telah tamat tempoh.',
                    ]);
                }

                $discountAmount = $coupon->calculateDiscount($subtotal);
            }

            $customer = Customer::findOrCreateFromOrderForm($customerData);

            $order = static::create([
                'order_number' => static::generateOrderNumber(),
                'customer_id' => $customer->id,
                'product_id' => $lockedProduct->id,
                'product_name' => $lockedProduct->name,
                'unit_price' => $lockedProduct->price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'discount_amount' => $discountAmount,
                'total_price' => $subtotal - $discountAmount,
                'payment_method' => $paymentMethod,
                'status' => OrderStatus::Pending,
                'notes' => $notes,
            ]);

            $lockedProduct->decrement('stock', $quantity);
            $coupon?->increment('used_count');

            return $order;
        });
    }

    public function transitionTo(OrderStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidOrderStatusTransition($this->status, $target);
        }

        DB::transaction(function () use ($target) {
            if ($target === OrderStatus::Cancelled && in_array($this->status, [OrderStatus::Pending, OrderStatus::Confirmed], strict: true)) {
                $this->product?->increment('stock', $this->quantity);

                if ($this->coupon_id !== null) {
                    $this->coupon?->decrement('used_count');
                }
            }

            $this->update(['status' => $target]);
        });
    }
}
