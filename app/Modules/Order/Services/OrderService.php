<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Coupon\Models\Coupon;
use App\Modules\Coupon\Services\CouponService;
use App\Modules\Order\Enums\FulfillmentStatus;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CouponService $couponService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order From Cart
    |--------------------------------------------------------------------------
    */

    public function createFromCart(
        User $user,
        ?string $notes = null
    ): Order {
        return DB::transaction(function () use ($user, $notes) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->with([
                    'items.product',
                    'items.productVariant',
                ])
                ->lockForUpdate()
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                throw new RuntimeException('Your cart is empty.');
            }

            $preparedItems = [];

            foreach ($cart->items as $cartItem) {
                $product = Product::query()
                    ->whereKey($cartItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new RuntimeException(
                        'One of the products in your cart no longer exists.'
                    );
                }

                if (!$product->is_active) {
                    throw new RuntimeException(
                        "Product '{$product->name}' is no longer available."
                    );
                }

                $quantity = (int) $cartItem->quantity;

                if ($quantity < 1) {
                    throw new RuntimeException(
                        "Invalid quantity for '{$product->name}'."
                    );
                }

                $variant = null;

                if ($this->isVariableProduct($product)) {
                    if (!$cartItem->product_variant_id) {
                        throw new RuntimeException(
                            "Please select a variant for '{$product->name}'."
                        );
                    }

                    $variant = ProductVariant::query()
                        ->whereKey($cartItem->product_variant_id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$variant || !$variant->is_active) {
                        throw new RuntimeException(
                            "The selected variant for '{$product->name}' is no longer available."
                        );
                    }

                    if ((int) $variant->stock < $quantity) {
                        throw new RuntimeException(
                            "Insufficient stock for '{$product->name}'."
                        );
                    }
                }

                $unitPrice = $variant
                    ? (float) $variant->price
                    : (float) $product->price;

                if ($unitPrice < 0) {
                    throw new RuntimeException(
                        "Invalid price for '{$product->name}'."
                    );
                }

                $lineTotal = round($unitPrice * $quantity, 2);

                $preparedItems[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_price' => $lineTotal,
                ];
            }

            $subtotal = round(
                (float) collect($preparedItems)->sum('total_price'),
                2
            );

            $discountAmount = 0.00;
            $shippingAmount = 0.00;
            $taxAmount = 0.00;

            $totalAmount = round(
                $subtotal
                    - $discountAmount
                    + $shippingAmount
                    + $taxAmount,
                2
            );

            $orderNumber = $this->generateNextOrderNumber();

            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'cart_id' => $cart->id,

                'customer_name' => $user->name,
                'phone' => $user->phone ?? null,
                'email' => $user->email,

                'payment_method' => 'cod',
                'order_number' => $orderNumber,

                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'fulfillment_status' => FulfillmentStatus::UNFULFILLED,

                'currency' => 'NPR',

                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,

                'notes' => $notes,
                'placed_at' => now(),
            ]);

            foreach ($preparedItems as $item) {
                $this->createOrderItem($order, $item);

                if ($item['variant']) {
                    $item['variant']->decrement(
                        'stock',
                        $item['quantity']
                    );
                }
            }

            $cart->items()->delete();

            return $this->orderRepository->loadOrder($order);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order From Checkout Data
    |--------------------------------------------------------------------------
    */

    public function createFromCheckoutData(
        array $data,
        ?User $user = null
    ): Order {
        return DB::transaction(function () use ($data, $user) {
            /*
            |--------------------------------------------------------------------------
            | Normalize Duplicate Items
            |--------------------------------------------------------------------------
            */

            $normalizedItems = [];

            foreach ($data['items'] as $item) {
                $productUuid = $item['product_uuid'];
                $variantUuid = $item['variant_uuid'] ?? null;

                $key = $productUuid . '|' . ($variantUuid ?? 'simple');

                if (!isset($normalizedItems[$key])) {
                    $normalizedItems[$key] = [
                        'product_uuid' => $productUuid,
                        'variant_uuid' => $variantUuid,
                        'quantity' => 0,
                    ];
                }

                $normalizedItems[$key]['quantity'] +=
                    (int) $item['quantity'];
            }

            if (empty($normalizedItems)) {
                throw new RuntimeException(
                    'Your order contains no items.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Province and District
            |--------------------------------------------------------------------------
            |
            | Get names from the database rather than trusting names sent
            | by the frontend. Ensure the district belongs to the province.
            |
            */

            $provinceId = filter_var(
                $data['province_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $districtId = filter_var(
                $data['district_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$provinceId || !$districtId) {
                throw ValidationException::withMessages([
                    'province_id' => [
                        'Please select a valid province and district.',
                    ],
                ]);
            }

            $province = DB::table('provinces')
                ->where('id', $provinceId)
                ->first();

            if (!$province) {
                throw ValidationException::withMessages([
                    'province_id' => [
                        'The selected province is invalid.',
                    ],
                ]);
            }

            $district = DB::table('districts')
                ->where('id', $districtId)
                ->where('province_id', $provinceId)
                ->first();

            if (!$district) {
                throw ValidationException::withMessages([
                    'district_id' => [
                        'The selected district does not belong to the selected province.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Products and Variants
            |--------------------------------------------------------------------------
            */

            $preparedItems = [];

            foreach ($normalizedItems as $item) {
                $product = Product::query()
                    ->where('uuid', $item['product_uuid'])
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new RuntimeException(
                        'One of the products in your order could not be found.'
                    );
                }

                if (!$product->is_active) {
                    throw new RuntimeException(
                        "Product '{$product->name}' is no longer available."
                    );
                }

                $quantity = (int) $item['quantity'];

                if ($quantity < 1) {
                    throw new RuntimeException(
                        "Invalid quantity for '{$product->name}'."
                    );
                }

                $variant = null;

                if ($this->isVariableProduct($product)) {
                    if (empty($item['variant_uuid'])) {
                        throw new RuntimeException(
                            "Please select a variant for '{$product->name}'."
                        );
                    }

                    $variant = ProductVariant::query()
                        ->where('uuid', $item['variant_uuid'])
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$variant || !$variant->is_active) {
                        throw new RuntimeException(
                            "The selected variant for '{$product->name}' is no longer available."
                        );
                    }

                    if ((int) $variant->stock < $quantity) {
                        throw new RuntimeException(
                            "Insufficient stock for '{$product->name}'. Available stock: {$variant->stock}."
                        );
                    }
                }

                $unitPrice = $variant
                    ? (float) $variant->price
                    : (float) $product->price;

                if ($unitPrice < 0) {
                    throw new RuntimeException(
                        "Invalid price for '{$product->name}'."
                    );
                }

                $lineTotal = round($unitPrice * $quantity, 2);

                $preparedItems[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_price' => $lineTotal,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Subtotal
            |--------------------------------------------------------------------------
            */

            $subtotal = round(
                (float) collect($preparedItems)->sum('total_price'),
                2
            );

            $discountAmount = 0.00;
            $shippingAmount = 0.00;
            $taxAmount = 0.00;
            $appliedCoupon = null;

            /*
            |--------------------------------------------------------------------------
            | Validate and Calculate Coupon
            |--------------------------------------------------------------------------
            */

            $couponCode = strtoupper(
                trim((string) ($data['coupon_code'] ?? ''))
            );

            if ($couponCode !== '') {
                $coupon = Coupon::query()
                    ->whereRaw('UPPER(code) = ?', [$couponCode])
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {
                    throw ValidationException::withMessages([
                        'coupon_code' => ['Invalid coupon code.'],
                    ]);
                }

                $couponResult = $this->couponService->validate(
                    $couponCode,
                    $subtotal,
                    $user?->id
                );

                $appliedCoupon = $couponResult['coupon'];

                $discountAmount = round(
                    (float) $couponResult['discount'],
                    2
                );

                /*
                 * Shipping is currently zero in this checkout flow.
                 * Apply free-shipping rules when shipping charges exist.
                 */
                if (!empty($couponResult['free_shipping'])) {
                    $shippingAmount = 0.00;
                }
            }

            $totalAmount = round(
                max(
                    0,
                    $subtotal
                        - $discountAmount
                        + $shippingAmount
                        + $taxAmount
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Generate Order Number
            |--------------------------------------------------------------------------
            */

            $orderNumber = $this->generateNextOrderNumber();

            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */

            $order = $this->orderRepository->create([
                'user_id' => $user?->id,
                'cart_id' => null,

                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,

                // IDs and names are verified against the database above.
                'province_id' => $province->id,
                'province' => $province->name,

                'district_id' => $district->id,
                'district' => $district->name,

                'city' => $data['city'],
                'address' => $data['address'],

                'payment_method' => $data['payment_method'],
                'order_number' => $orderNumber,

                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'fulfillment_status' => FulfillmentStatus::UNFULFILLED,

                'currency' => 'NPR',

                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,

                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Items and Decrease Stock
            |--------------------------------------------------------------------------
            */

            foreach ($preparedItems as $item) {
                $this->createOrderItem($order, $item);

                if ($item['variant']) {
                    $item['variant']->decrement(
                        'stock',
                        $item['quantity']
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Record Coupon Usage
            |--------------------------------------------------------------------------
            */

            if ($appliedCoupon !== null) {
                $this->couponService->recordUsage(
                    coupon: $appliedCoupon,
                    userId: $user?->id,
                    orderId: $order->id,
                    discountAmount: $discountAmount
                );
            }

            return $this->orderRepository->loadOrder($order);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility
    |--------------------------------------------------------------------------
    */

    public function createGuestOrder(
        array $data,
        ?User $user = null
    ): Order {
        return $this->createFromCheckoutData($data, $user);
    }

    /*
    |--------------------------------------------------------------------------
    | Create From Existing Checkout
    |--------------------------------------------------------------------------
    */

    public function createFromCheckout(
        User $user,
        string $checkoutUuid
    ): Order {
        throw new RuntimeException(
            'The checkout-session order flow is not available.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order Item
    |--------------------------------------------------------------------------
    */

    protected function createOrderItem(
        Order $order,
        array $item
    ): void {
        $product = $item['product'];
        $variant = $item['variant'];

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,

            'product_name' => $product->display_name
                ?? $product->name,

            'variant_name' => $variant
                ? $this->buildVariantName($variant)
                : null,

            'sku' => $variant?->sku ?? $product->sku,

            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'discount_amount' => $item['discount_amount'],
            'tax_amount' => $item['tax_amount'],
            'total_price' => $item['total_price'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Order Number
    |--------------------------------------------------------------------------
    */

    protected function generateNextOrderNumber(): string
    {
        DB::table('order_sequences')->insertOrIgnore([
            'year' => now()->year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('order_sequences')
            ->where('year', now()->year)
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            throw new RuntimeException(
                'Unable to initialize order number sequence.'
            );
        }

        $nextNumber = ((int) $sequence->last_number) + 1;

        DB::table('order_sequences')
            ->where('id', $sequence->id)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return sprintf(
            'ORD-%d-%06d',
            now()->year,
            $nextNumber
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Build Variant Name
    |--------------------------------------------------------------------------
    */

    protected function buildVariantName(
        ProductVariant $variant
    ): ?string {
        $variant->loadMissing([
            'optionValues.productOption',
        ]);

        $values = $variant->optionValues
            ->map(function ($optionValue) {
                $optionName = $optionValue->productOption?->name;

                $valueName = $optionValue->value
                    ?? $optionValue->name;

                if (!$valueName) {
                    return null;
                }

                return $optionName
                    ? "{$optionName}: {$valueName}"
                    : $valueName;
            })
            ->filter()
            ->values();

        return $values->isNotEmpty()
            ? $values->implode(', ')
            : ($variant->name ?: null);
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Orders
    |--------------------------------------------------------------------------
    */

    public function getUserOrders(User $user)
    {
        return $this->orderRepository->getForUser($user);
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Order
    |--------------------------------------------------------------------------
    */

    public function getUserOrder(
        User $user,
        string $uuid
    ): Order {
        $order = $this->orderRepository->findForUser($user, $uuid);

        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        return $this->orderRepository->loadOrder($order);
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Orders
    |--------------------------------------------------------------------------
    */

    public function getAllOrders()
    {
        return $this->orderRepository->getAll();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Order
    |--------------------------------------------------------------------------
    */

    public function getOrder(string $uuid): Order
    {
        $order = $this->orderRepository->findByUuid($uuid);

        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        return $this->orderRepository->loadOrder($order);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Type Helper
    |--------------------------------------------------------------------------
    */

    protected function isVariableProduct(Product $product): bool
    {
        $productType = $product->product_type;

        if ($productType instanceof \BackedEnum) {
            return $productType->value === 'variable';
        }

        return (string) $productType === 'variable';
    }
}
