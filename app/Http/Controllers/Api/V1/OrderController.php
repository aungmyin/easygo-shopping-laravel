<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\{Order, Product};
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, string $number)
    {
        $order = $request->user()
            ->orders()
            ->with('items.product')
            ->where('order_number', $number)
            ->firstOrFail();

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'delivery_address' => 'required|string',
            'notes'            => 'nullable|string',
        ]);

        $cart = session('cart.' . $request->user()->id, []);
        if (empty($cart)) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        return DB::transaction(function () use ($request, $cart) {
            $subtotal = 0;
            $items    = [];

            foreach ($cart as $productId => $qty) {
                $product = Product::lockForUpdate()->findOrFail($productId);

                if ($product->stock < $qty) {
                    return response()->json([
                        'message' => "Insufficient stock for {$product->name}",
                    ], 422);
                }

                $sub       = $product->effective_price * $qty;
                $subtotal += $sub;
                $items[]   = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $qty,
                    'unit_price'   => $product->effective_price,
                    'subtotal'     => $sub,
                ];

                $product->decrement('stock', $qty);
            }

            $fee   = $subtotal >= 500 ? 0 : 50;
            $order = $request->user()->orders()->create([
                'subtotal'                   => $subtotal,
                'delivery_fee'               => $fee,
                'total'                      => $subtotal + $fee,
                'delivery_address_snapshot'  => $request->delivery_address,
                'notes'                      => $request->notes,
            ]);

            $order->items()->createMany($items);
            session()->forget('cart.' . $request->user()->id);

            return response()->json([
                'data'    => new OrderResource($order->load('items')),
                'message' => 'Order placed successfully',
            ], 201);
        });
    }

    public function cancel(Request $request, string $number)
    {
        $order = $request->user()
            ->orders()
            ->where('order_number', $number)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can be cancelled'], 422);
        }

        $order->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Order cancelled']);
    }
}
