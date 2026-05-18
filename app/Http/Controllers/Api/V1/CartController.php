<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart  = session('cart.' . $request->user()->id, []);
        $items = [];
        $total = 0;

        foreach ($cart as $id => $qty) {
            $product = Product::find($id);
            if (!$product) continue;
            $sub    = $product->effective_price * $qty;
            $total += $sub;
            $items[] = [
                'product_id'    => $id,
                'name'          => $product->name,
                'quantity'      => $qty,
                'unit_price'    => $product->effective_price,
                'subtotal'      => $sub,
                'thumbnail_url' => $product->thumbnail_url,
            ];
        }

        return response()->json([
            'data' => ['items' => $items, 'total' => round($total, 2)],
        ]);
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $key = 'cart.' . $request->user()->id . '.' . $data['product_id'];
        session([$key => session($key, 0) + $data['quantity']]);

        return response()->json(['message' => 'Item added to cart']);
    }

    public function updateItem(Request $request, $productId)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1']);
        session(['cart.' . $request->user()->id . '.' . $productId => $data['quantity']]);
        return response()->json(['message' => 'Cart updated']);
    }

    public function removeItem(Request $request, $productId)
    {
        $cart = session('cart.' . $request->user()->id, []);
        unset($cart[$productId]);
        session(['cart.' . $request->user()->id => $cart]);
        return response()->json(['message' => 'Item removed']);
    }

    public function clear(Request $request)
    {
        session()->forget('cart.' . $request->user()->id);
        return response()->json(['message' => 'Cart cleared']);
    }
}
