<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\FulfillmentStatus;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusHistory;
use App\Modules\Order\Models\Shipment;
use App\Modules\Order\Models\ShipmentEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    /**
     * List and filter orders.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in(array_column(OrderStatus::cases(), 'value')),
            ],
            'payment_status' => [
                'nullable',
                Rule::in(['pending', 'paid', 'partially_paid', 'failed', 'refunded']),
            ],
            'fulfillment_status' => [
                'nullable',
                Rule::in(array_column(FulfillmentStatus::cases(), 'value')),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Order::query()
            ->with(['items', 'user'])
            ->withCount('items')
            ->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'payment_status', 'fulfillment_status'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        $orders = $query->paginate($filters['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => $orders,
        ]);
    }

    /**
     * Show order details, shipment tracking, and status history.
     */
    public function show(string $uuid): JsonResponse
    {
        $order = Order::query()
            ->where('uuid', $uuid)
            ->with([
                'items',
                'user',
                'statusHistories.changedBy',
                'shipments.events.createdBy',
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => $order,
        ]);
    }

    /**
     * Update order and/or fulfillment status.
     */
    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'status' => [
                'nullable',
                Rule::in(array_column(OrderStatus::cases(), 'value')),
            ],
            'fulfillment_status' => [
                'nullable',
                Rule::in(array_column(FulfillmentStatus::cases(), 'value')),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (
            !array_key_exists('status', $data)
            && !array_key_exists('fulfillment_status', $data)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Provide an order status or fulfillment status.',
                'data' => null,
            ], 422);
        }

        $order = DB::transaction(function () use ($uuid, $data, $request) {
            $order = Order::query()
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $this->enumValue($order->status);
            $oldFulfillment = $this->enumValue($order->fulfillment_status);

            $newStatus = $data['status'] ?? $oldStatus;
            $newFulfillment = $data['fulfillment_status'] ?? $oldFulfillment;

            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                abort(422, 'A cancelled order cannot be reopened through this endpoint.');
            }

            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                abort(422, 'A completed order cannot be reopened through this endpoint.');
            }

            if (
                $newStatus === 'delivered'
                && $newFulfillment !== 'delivered'
            ) {
                abort(422, 'Set fulfillment_status to delivered when marking the order delivered.');
            }

            if (
                $newFulfillment === 'delivered'
                && $newStatus !== 'delivered'
            ) {
                abort(422, 'Set status to delivered when marking fulfillment delivered.');
            }

            $order->status = $newStatus;
            $order->fulfillment_status = $newFulfillment;
            $order->save();

            if (
                $oldStatus !== $newStatus
                || $oldFulfillment !== $newFulfillment
            ) {
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'from_fulfillment_status' => $oldFulfillment,
                    'to_fulfillment_status' => $newFulfillment,
                    'changed_by' => $request->user()?->getAuthIdentifier(),
                    'note' => $data['note'] ?? null,
                ]);
            }

            return $order->load([
                'items',
                'statusHistories.changedBy',
                'shipments.events',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order,
        ]);
    }

    /**
     * Create a shipment for an order.
     */
    public function storeShipment(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $shipment = DB::transaction(function () use ($uuid, $data, $request) {
            $order = Order::query()
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($this->enumValue($order->status), ['cancelled', 'completed', 'delivered'], true)) {
                abort(422, 'A shipment cannot be created for this order status.');
            }

            $shipment = $order->shipments()->create([
                ...$data,
                'status' => 'pending',
            ]);

            ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => 'pending',
                'description' => 'Shipment created.',
                'event_at' => now(),
                'created_by' => $request->user()?->getAuthIdentifier(),
            ]);

            return $shipment->load('events.createdBy');
        });

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully.',
            'data' => $shipment,
        ], 201);
    }

    /**
     * Add a shipment tracking event.
     */
    public function storeShipmentEvent(
        Request $request,
        string $uuid
    ): JsonResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'shipped',
                    'in_transit',
                    'out_for_delivery',
                    'delivered',
                    'failed_delivery',
                    'returned',
                ]),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_at' => ['nullable', 'date'],
        ]);

        $result = DB::transaction(function () use ($uuid, $data, $request) {
            $shipment = Shipment::query()
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->firstOrFail();

            $order = Order::query()
                ->whereKey($shipment->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $eventStatus = $data['status'];

            if (
                $this->enumValue($order->status) === 'cancelled'
                || $this->enumValue($order->status) === 'completed'
            ) {
                abort(422, 'Tracking cannot update a cancelled or completed order.');
            }

            if (
                $this->enumValue($order->status) === 'delivered'
                && $eventStatus !== 'returned'
            ) {
                abort(422, 'The order has already been delivered.');
            }

            $eventAt = $data['event_at'] ?? now();

            $event = ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $eventStatus,
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'event_at' => $eventAt,
                'created_by' => $request->user()?->getAuthIdentifier(),
            ]);

            $shipment->status = $eventStatus;

            if ($eventStatus === 'shipped' && !$shipment->shipped_at) {
                $shipment->shipped_at = $eventAt;
            }

            if ($eventStatus === 'delivered') {
                $shipment->delivered_at = $eventAt;
            }

            $shipment->save();

            $oldStatus = $this->enumValue($order->status);
            $oldFulfillment = $this->enumValue($order->fulfillment_status);

            if ($eventStatus === 'shipped') {
                $order->status = OrderStatus::SHIPPED->value;
                $order->fulfillment_status = FulfillmentStatus::SHIPPED->value;
            } elseif ($eventStatus === 'delivered') {
                $order->status = OrderStatus::DELIVERED->value;
                $order->fulfillment_status = FulfillmentStatus::DELIVERED->value;
            }

            $order->save();

            $newStatus = $this->enumValue($order->status);
            $newFulfillment = $this->enumValue($order->fulfillment_status);

            if (
                $oldStatus !== $newStatus
                || $oldFulfillment !== $newFulfillment
            ) {
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'from_fulfillment_status' => $oldFulfillment,
                    'to_fulfillment_status' => $newFulfillment,
                    'changed_by' => $request->user()?->getAuthIdentifier(),
                    'note' => 'Shipment tracking update: ' . $eventStatus,
                    'metadata' => ['shipment_uuid' => $shipment->uuid],
                ]);
            }

            return [
                'event' => $event->load('createdBy'),
                'shipment' => $shipment->load('events.createdBy'),
                'order' => $order,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Shipment tracking event added successfully.',
            'data' => $result,
        ], 201);
    }

    /**
     * Convert enum-cast values and strings to plain strings.
     */
    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}