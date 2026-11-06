<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $metrics = [
            'products' => Product::count(),
            'available' => Product::where('stock', '>', 0)->count(),
            'orders' => Order::count(),
            'awaiting_payment' => Order::where('status', 'awaiting_payment')->count(),
            'sales' => Order::whereIn('status', ['processing', 'shipped', 'delivered'])->sum('total'),
        ];
        $latestOrders = Order::query()->withCount('items')->latest()->limit(8)->get();

        return view('admin.dashboard', compact('metrics', 'latestOrders'));
    }
}
