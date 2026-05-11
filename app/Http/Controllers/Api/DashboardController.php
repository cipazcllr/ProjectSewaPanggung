<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'totalRevenue' => (float) Order::sum('total'),
            'pendingOrders' => Order::where('status', 'Pending')->count(),
            'lowStock' => Product::where('stock', '<=', 5)->count(),
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
        ]);

        $query = Order::query();

        if (! empty($validated['startDate'])) {
            $query->whereDate('date', '>=', $validated['startDate']);
        }

        if (! empty($validated['endDate'])) {
            $query->whereDate('date', '<=', $validated['endDate']);
        }

        $data = $query
            ->selectRaw('date, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn (Order $order) => [
                'date' => $order->date->format('Y-m-d'),
                'revenue' => (float) $order->revenue,
            ]);

        return response()->json($data);
    }

    public function recentOrders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::with('items.product')
            ->latest('date')
            ->latest('id')
            ->limit($validated['limit'] ?? 5)
            ->get();

        return response()->json($orders);
    }
}
