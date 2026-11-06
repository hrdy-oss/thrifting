<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function show(string $token): View
    {
        $order = Order::query()->with(['items', 'events' => fn ($query) => $query->oldest()])
            ->where('tracking_token', $token)->firstOrFail();

        return view('storefront.order', compact('order'));
    }
}
